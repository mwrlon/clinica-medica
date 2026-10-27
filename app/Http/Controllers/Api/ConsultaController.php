<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use App\Models\Atendimento;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Sala;
use App\Support\AgendaClinica;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsultaController extends Controller
{
    /**
     * Lista consultas com base no usuário logado:
     * paciente = as dele; médico = a agenda dele (aceita ?data=AAAA-MM-DD); admin = todas.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'data' => 'nullable|date_format:Y-m-d',
        ]);

        $user = $request->user();

        $consultas = Consulta::query()
            ->with([
                'medico:id,nome,crm,especialidade_id',
                'medico.especialidade',
                'paciente:id,nome,cpf,data_nascimento,sexo,telefone,email,convenio_id',
                'paciente.convenio:id,nome',
                'sala',
                'atendimento:id,consulta_id,diagnostico,receita,observacoes',
            ]);

        if ($user instanceof Paciente) {
            $consultas->where('paciente_id', $user->id);
        } elseif ($user instanceof Medico) {
            $consultas->where('medico_id', $user->id);
        } elseif (!($user instanceof Administrador)) {
            return response()->json(['mensagem' => 'Acesso negado para o seu perfil.'], 403);
        }

        if ($request->filled('data')) {
            $consultas->where('data', $request->query('data'));
        }

        return response()->json(
            $consultas->orderBy('data')->orderBy('hora')->get()
        );
    }

    /**
     * Agendar uma nova consulta.
     *
     * O paciente só agenda para si mesmo e o médico só na própria agenda: os ids vindos do
     * cliente são ignorados nesses casos. A sala é opcional: sem ela, a primeira sala livre
     * no horário é escolhida. O paciente só pode marcar nos horários do expediente da clínica
     * (config/clinica.php); o médico pode fazer encaixes fora da grade.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Paciente) {
            $request->merge(['paciente_id' => $user->id]);
        } elseif ($user instanceof Medico) {
            $request->merge(['medico_id' => $user->id]);
        }

        $validated = $request->validate([
            'medico_id'   => ['required', Rule::exists('medicos', 'id')->where('status', 'ativo')],
            'paciente_id' => 'required|exists:pacientes,id',
            'sala_id'     => 'nullable|exists:salas,id',
            'data'        => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora'        => 'required|date_format:H:i',
            'observacoes' => 'nullable|string|max:2000',
        ]);

        if ($erro = $this->horarioInvalido($user, $validated['data'], $validated['hora'])) {
            return response()->json(['mensagem' => $erro], 422);
        }

        try {
            $consulta = $this->agendar($validated);
        } catch (UniqueConstraintViolationException) {
            // Duas requisições disputaram o mesmo horário: o índice único do banco barrou a segunda
            return response()->json(['mensagem' => 'Este horário acabou de ser ocupado. Escolha outro horário.'], 422);
        }

        if (is_string($consulta)) {
            return response()->json(['mensagem' => $consulta], 422);
        }

        return response()->json([
            'mensagem' => 'Consulta agendada com sucesso!',
            'consulta' => $this->completa($consulta),
        ], 201);
    }

    /**
     * Mensagem de erro se o horário não puder ser usado por este usuário (ou null se estiver ok).
     */
    private function horarioInvalido($user, string $data, string $hora): ?string
    {
        if (AgendaClinica::jaPassou($data, $hora)) {
            return 'Este horário já passou. Escolha uma data e hora futuras.';
        }

        if ($user instanceof Paciente && !AgendaClinica::dentroDoExpediente($data, $hora)) {
            return 'Horário fora do atendimento da clínica. Escolha um dos horários disponíveis.';
        }

        return null;
    }

    private function completa(Consulta $consulta): Consulta
    {
        return $consulta->load(['medico:id,nome,crm,especialidade_id', 'medico.especialidade', 'paciente:id,nome', 'sala']);
    }

    /**
     * Cria a consulta ou devolve (string) o motivo de o horário estar indisponível.
     */
    private function agendar(array $validated): Consulta|string
    {
        return DB::transaction(function () use ($validated) {
            if ($erro = $this->conflito($validated['medico_id'], $validated['paciente_id'], $validated['data'], $validated['hora'])) {
                return $erro;
            }

            $sala = $this->escolherSala($validated['data'], $validated['hora'], $validated['sala_id'] ?? null);
            if (is_string($sala)) {
                return $sala;
            }

            // refresh(): traz os valores padrão do banco (status "agendada", criado_em) para a resposta
            return Consulta::create(array_merge($validated, ['sala_id' => $sala->id]))->refresh();
        });
    }

    /**
     * Choque de horário do médico e do paciente (consultas canceladas liberam o horário).
     * $ignorarConsultaId = consulta que está sendo reagendada.
     */
    private function conflito(int $medicoId, int $pacienteId, string $data, string $hora, ?int $ignorarConsultaId = null): ?string
    {
        $ativa = fn () => Consulta::where('data', $data)
            ->where('hora', $hora)
            ->where('status', '!=', 'cancelada')
            ->when($ignorarConsultaId, fn ($q) => $q->where('id', '!=', $ignorarConsultaId))
            ->lockForUpdate();

        if ($ativa()->where('medico_id', $medicoId)->exists()) {
            return 'O médico já possui uma consulta agendada para este dia e horário.';
        }

        if ($ativa()->where('paciente_id', $pacienteId)->exists()) {
            return 'O paciente já possui uma consulta agendada para este mesmo horário.';
        }

        return null;
    }

    /** A sala pedida (se estiver livre) ou a primeira sala livre; texto de erro se não houver. */
    private function escolherSala(string $data, string $hora, ?int $preferidaId, ?int $ignorarConsultaId = null): Sala|string
    {
        $ocupadas = AgendaClinica::salasOcupadas($data, $hora, $ignorarConsultaId, travar: true);

        if ($preferidaId && in_array($preferidaId, $ocupadas, true)) {
            return 'A sala escolhida já está ocupada neste horário.';
        }

        return AgendaClinica::salaLivre($data, $hora, $preferidaId, $ignorarConsultaId)
            ?? 'Não há sala disponível neste horário. Escolha outro horário.';
    }

    /**
     * Cancelar Consulta (só o paciente ou o médico da consulta, ou um administrador).
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $consulta = Consulta::findOrFail($id);

        if (!$this->podeGerenciar($user, $consulta)) {
            return response()->json(['mensagem' => 'Você não tem permissão para cancelar esta consulta.'], 403);
        }

        if ($consulta->status === 'realizada') {
            return response()->json(['mensagem' => 'Uma consulta já realizada não pode ser cancelada.'], 422);
        }

        $consulta->update(['status' => 'cancelada']);

        return response()->json(['mensagem' => 'Consulta cancelada com sucesso.']);
    }

    /**
     * Reagendar uma consulta ainda agendada para outro dia/horário (mesmo médico).
     * Quem pode: o paciente ou o médico da consulta, ou um administrador.
     * Mantém a sala atual se ela estiver livre no novo horário; senão escolhe outra.
     */
    public function reagendar(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $consulta = Consulta::findOrFail($id);

        if (!$this->podeGerenciar($user, $consulta)) {
            return response()->json(['mensagem' => 'Você não tem permissão para reagendar esta consulta.'], 403);
        }

        $validated = $request->validate([
            'data' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
        ]);

        if ($consulta->status !== 'agendada') {
            return response()->json(['mensagem' => 'Só é possível reagendar consultas que ainda estão agendadas.'], 422);
        }

        $mesmoHorario = $consulta->data->format('Y-m-d') === $validated['data']
            && substr($consulta->hora, 0, 5) === $validated['hora'];

        if ($mesmoHorario) {
            return response()->json(['mensagem' => 'Escolha um dia ou horário diferente do atual.'], 422);
        }

        if ($erro = $this->horarioInvalido($user, $validated['data'], $validated['hora'])) {
            return response()->json(['mensagem' => $erro], 422);
        }

        try {
            $resultado = DB::transaction(function () use ($consulta, $validated) {
                if ($erro = $this->conflito($consulta->medico_id, $consulta->paciente_id, $validated['data'], $validated['hora'], $consulta->id)) {
                    return $erro;
                }

                // Prefere continuar na mesma sala; se ela estiver ocupada, usa a primeira livre
                $sala = AgendaClinica::salaLivre($validated['data'], $validated['hora'], $consulta->sala_id, $consulta->id, travar: true);
                if (!$sala) {
                    return 'Não há sala disponível neste horário. Escolha outro horário.';
                }

                $consulta->update([
                    'data'    => $validated['data'],
                    'hora'    => $validated['hora'],
                    'sala_id' => $sala->id,
                ]);

                return $consulta;
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['mensagem' => 'Este horário acabou de ser ocupado. Escolha outro horário.'], 422);
        }

        if (is_string($resultado)) {
            return response()->json(['mensagem' => $resultado], 422);
        }

        return response()->json([
            'mensagem' => 'Consulta reagendada com sucesso!',
            'consulta' => $this->completa($resultado->refresh()),
        ]);
    }

    /** Paciente ou médico da consulta, ou administrador. */
    private function podeGerenciar($user, Consulta $consulta): bool
    {
        return match (true) {
            $user instanceof Administrador => true,
            $user instanceof Medico        => $consulta->medico_id === $user->id,
            $user instanceof Paciente      => $consulta->paciente_id === $user->id,
            default                        => false,
        };
    }

    /**
     * Registrar o atendimento de uma consulta (exclusivo do médico responsável).
     * Opcionalmente já solicita exames: "exames": [{"nome": "Hemograma completo"}].
     */
    public function registrarAtendimento(Request $request): JsonResponse
    {
        $medico = $request->user();

        $validated = $request->validate([
            'consulta_id'  => 'required|exists:consultas,id',
            'diagnostico'  => 'required|string|max:5000',
            'receita'      => 'nullable|string|max:5000',
            'observacoes'  => 'nullable|string|max:5000',
            'exames'       => 'nullable|array|max:20',
            'exames.*.nome' => 'required|string|max:150',
        ]);

        $consulta = Consulta::findOrFail($validated['consulta_id']);

        if ($consulta->medico_id !== $medico->id) {
            return response()->json(['mensagem' => 'Esta consulta pertence a outro médico.'], 403);
        }

        if ($consulta->status === 'cancelada') {
            return response()->json(['mensagem' => 'Não é possível registrar atendimento de uma consulta cancelada.'], 422);
        }

        if (Atendimento::where('consulta_id', $consulta->id)->exists()) {
            return response()->json(['mensagem' => 'Esta consulta já possui um atendimento registrado.'], 422);
        }

        $atendimento = DB::transaction(function () use ($validated, $consulta) {
            $atendimento = Atendimento::create([
                'consulta_id' => $consulta->id,
                'diagnostico' => $validated['diagnostico'],
                'receita'     => $validated['receita'] ?? null,
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            foreach ($validated['exames'] ?? [] as $exame) {
                $atendimento->exames()->create([
                    'paciente_id'   => $consulta->paciente_id,
                    'nome'          => $exame['nome'],
                    'status'        => 'solicitado',
                    'solicitado_em' => now(),
                ]);
            }

            $consulta->update(['status' => 'realizada']);

            return $atendimento;
        });

        return response()->json([
            'mensagem'    => 'Atendimento registrado com sucesso!',
            'atendimento' => $atendimento->load('exames'),
        ], 201);
    }
}

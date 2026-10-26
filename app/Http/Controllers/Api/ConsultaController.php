<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use App\Models\Atendimento;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Paciente;
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
     * O paciente só agenda para si mesmo e o médico só agenda na própria agenda:
     * os ids vindos do cliente são ignorados nesses casos.
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
            'sala_id'     => 'required|exists:salas,id',
            'data'        => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora'        => 'required|date_format:H:i',
            'observacoes' => 'nullable|string|max:2000',
        ]);

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
            'consulta' => $consulta->load(['medico:id,nome,crm,especialidade_id', 'paciente:id,nome', 'sala']),
        ], 201);
    }

    /**
     * Cria a consulta ou devolve (string) o motivo de o horário estar indisponível.
     */
    private function agendar(array $validated): Consulta|string
    {
        return DB::transaction(function () use ($validated) {
            // Choque de horário do médico (consultas canceladas liberam o horário)
            $medicoOcupado = Consulta::where('medico_id', $validated['medico_id'])
                ->where('data', $validated['data'])
                ->where('hora', $validated['hora'])
                ->where('status', '!=', 'cancelada')
                ->lockForUpdate()
                ->exists();

            if ($medicoOcupado) {
                return 'O médico já possui uma consulta agendada para este dia e horário.';
            }

            // Choque de horário do paciente
            $pacienteOcupado = Consulta::where('paciente_id', $validated['paciente_id'])
                ->where('data', $validated['data'])
                ->where('hora', $validated['hora'])
                ->where('status', '!=', 'cancelada')
                ->lockForUpdate()
                ->exists();

            if ($pacienteOcupado) {
                return 'O paciente já possui uma consulta agendada para este mesmo horário.';
            }

            // refresh(): traz os valores padrão do banco (status "agendada", criado_em) para a resposta
            return Consulta::create($validated)->refresh();
        });
    }

    /**
     * Cancelar Consulta (só o paciente ou o médico da consulta, ou um administrador).
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $consulta = Consulta::findOrFail($id);

        $permitido = match (true) {
            $user instanceof Administrador => true,
            $user instanceof Medico        => $consulta->medico_id === $user->id,
            $user instanceof Paciente      => $consulta->paciente_id === $user->id,
            default                        => false,
        };

        if (!$permitido) {
            return response()->json(['mensagem' => 'Você não tem permissão para cancelar esta consulta.'], 403);
        }

        if ($consulta->status === 'realizada') {
            return response()->json(['mensagem' => 'Uma consulta já realizada não pode ser cancelada.'], 422);
        }

        $consulta->update(['status' => 'cancelada']);

        return response()->json(['mensagem' => 'Consulta cancelada com sucesso.']);
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

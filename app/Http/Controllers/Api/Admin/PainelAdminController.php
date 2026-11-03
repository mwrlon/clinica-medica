<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Sala;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dados do painel do administrador: visão geral, consultas (com filtros) e pacientes.
 * Os cadastros (médicos, especialidades, convênios e salas) ficam nos outros controllers desta pasta.
 */
class PainelAdminController extends Controller
{
    private const POR_PAGINA_PADRAO = 15;

    /**
     * GET /api/admin/resumo — números da visão geral.
     * "Mês" = mês corrente (fuso da aplicação).
     */
    public function resumo(): JsonResponse
    {
        $hoje      = now()->toDateString();
        $inicioMes = now()->startOfMonth()->toDateString();
        $fimMes    = now()->endOfMonth()->toDateString();

        $doMes = fn () => Consulta::whereBetween('data', [$inicioMes, $fimMes]);

        $porEspecialidade = DB::table('consultas')
            ->join('medicos', 'medicos.id', '=', 'consultas.medico_id')
            ->join('especialidades', 'especialidades.id', '=', 'medicos.especialidade_id')
            ->whereBetween('consultas.data', [$inicioMes, $fimMes])
            ->where('consultas.status', '!=', 'cancelada')
            ->groupBy('especialidades.id', 'especialidades.nome')
            ->orderByDesc('total')
            ->orderBy('especialidades.nome')
            ->limit(6)
            ->get(['especialidades.nome', DB::raw('COUNT(*) as total')]);

        $proximas = Consulta::query()
            ->with(['medico:id,nome', 'paciente:id,nome', 'sala:id,identificacao'])
            ->where('status', 'agendada')
            ->where(fn ($q) => $q->where('data', '>', $hoje)
                ->orWhere(fn ($q) => $q->where('data', $hoje)->where('hora', '>=', now()->format('H:i:s'))))
            ->orderBy('data')
            ->orderBy('hora')
            ->limit(5)
            ->get();

        return response()->json([
            'medicos' => [
                'ativos'   => Medico::where('status', 'ativo')->count(),
                'inativos' => Medico::where('status', 'inativo')->count(),
            ],
            'pacientes' => [
                'total'        => Paciente::count(),
                'novos_30_dias' => Paciente::where('criado_em', '>=', now()->subDays(30))->count(),
            ],
            'consultas' => [
                'hoje'            => Consulta::where('data', $hoje)->where('status', '!=', 'cancelada')->count(),
                'agendadas'       => Consulta::where('status', 'agendada')->where('data', '>=', $hoje)->count(),
                'mes_agendadas'   => $doMes()->where('status', 'agendada')->count(),
                'mes_realizadas'  => $doMes()->where('status', 'realizada')->count(),
                'mes_canceladas'  => $doMes()->where('status', 'cancelada')->count(),
            ],
            'cadastros' => [
                'especialidades' => Especialidade::count(),
                'convenios'      => Convenio::count(),
                'salas'          => Sala::count(),
            ],
            'por_especialidade' => $porEspecialidade,
            'proximas'          => $proximas,
        ]);
    }

    /**
     * GET /api/admin/consultas — todas as consultas, paginadas.
     * Filtros: status, data_inicio, data_fim, medico_id e busca (nome do paciente, CPF ou nome do médico).
     */
    public function consultas(Request $request): JsonResponse
    {
        $request->validate([
            'status'      => 'nullable|in:agendada,realizada,cancelada',
            'data_inicio' => 'nullable|date_format:Y-m-d',
            'data_fim'    => 'nullable|date_format:Y-m-d',
            'medico_id'   => 'nullable|exists:medicos,id',
            'busca'       => 'nullable|string|max:100',
            'por_pagina'  => 'nullable|integer|min:1|max:50',
        ]);

        $consultas = Consulta::query()
            ->with([
                'medico:id,nome,crm,especialidade_id',
                'medico.especialidade',
                'paciente:id,nome,cpf,telefone',
                'sala:id,identificacao',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('data_inicio'), fn ($q) => $q->where('data', '>=', $request->query('data_inicio')))
            ->when($request->filled('data_fim'), fn ($q) => $q->where('data', '<=', $request->query('data_fim')))
            ->when($request->filled('medico_id'), fn ($q) => $q->where('medico_id', $request->query('medico_id')))
            ->when($request->filled('busca'), function ($q) use ($request) {
                $termo = $this->termoLike($request->query('busca'));

                $q->where(fn ($q) => $q
                    ->whereHas('paciente', fn ($p) => $p->where('nome', 'like', $termo)->orWhere('cpf', 'like', $termo))
                    ->orWhereHas('medico', fn ($m) => $m->where('nome', 'like', $termo)));
            })
            ->orderByDesc('data')
            ->orderByDesc('hora')
            ->orderByDesc('id')
            ->paginate((int) $request->query('por_pagina', self::POR_PAGINA_PADRAO));

        return response()->json($consultas);
    }

    /**
     * GET /api/admin/pacientes — pacientes cadastrados, paginados. Filtro: busca (nome, CPF ou e-mail).
     */
    public function pacientes(Request $request): JsonResponse
    {
        $request->validate([
            'busca'      => 'nullable|string|max:100',
            'por_pagina' => 'nullable|integer|min:1|max:50',
        ]);

        $pacientes = Paciente::query()
            ->with('convenio:id,nome')
            ->withCount('consultas')
            ->when($request->filled('busca'), function ($q) use ($request) {
                $termo = $this->termoLike($request->query('busca'));

                $q->where(fn ($q) => $q
                    ->where('nome', 'like', $termo)
                    ->orWhere('cpf', 'like', $termo)
                    ->orWhere('email', 'like', $termo));
            })
            ->orderBy('nome')
            ->paginate((int) $request->query('por_pagina', self::POR_PAGINA_PADRAO));

        return response()->json($pacientes);
    }

    /** Escapa % e _ para que a busca trate o texto digitado como texto comum. */
    private function termoLike(string $busca): string
    {
        return '%' . addcslashes(trim($busca), '%_\\') . '%';
    }
}

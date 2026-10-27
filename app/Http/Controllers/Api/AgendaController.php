<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Support\AgendaClinica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * Horários livres de um médico em um dia: GET /api/medicos/{id}/horarios?data=AAAA-MM-DD
     *
     * Para o paciente, também exclui os horários em que ele já tem outra consulta.
     * "ignorar" = id de uma consulta que está sendo reagendada (o horário dela conta como livre).
     */
    public function horarios(Request $request, $id): JsonResponse
    {
        $request->validate([
            'data'    => 'required|date_format:Y-m-d|after_or_equal:today',
            'ignorar' => 'nullable|integer',
        ]);

        $medico = Medico::where('id', $id)->where('status', 'ativo')->firstOrFail();
        $user = $request->user();

        $data = $request->query('data');
        $pacienteId = $user instanceof Paciente ? $user->id : null;

        // Só desconsidera a consulta indicada se ela for do próprio usuário (ou ele for administrador)
        $ignorar = null;
        if ($request->filled('ignorar')) {
            $consulta = Consulta::find($request->query('ignorar'));
            $dono = $consulta && match (true) {
                $user instanceof Administrador => true,
                $user instanceof Paciente      => $consulta->paciente_id === $user->id,
                $user instanceof Medico        => $consulta->medico_id === $user->id,
                default                        => false,
            };
            $ignorar = $dono ? $consulta->id : null;
        }

        return response()->json([
            'data'      => $data,
            'medico_id' => $medico->id,
            'fechado'   => AgendaClinica::horariosDoDia($data) === [],
            'horarios'  => AgendaClinica::livres($medico, $data, $pacienteId, $ignorar),
        ]);
    }
}

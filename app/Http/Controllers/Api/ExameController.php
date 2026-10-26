<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use App\Models\Exame;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExameController extends Controller
{
    /**
     * Paciente: seus exames. Médico: exames pedidos nos atendimentos dele. Admin: todos.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $exames = Exame::query()->with('paciente:id,nome');

        if ($user instanceof Paciente) {
            $exames->where('paciente_id', $user->id);
        } elseif ($user instanceof Medico) {
            $exames->whereHas('atendimento.consulta', fn ($q) => $q->where('medico_id', $user->id));
        } elseif (!($user instanceof Administrador)) {
            return response()->json(['mensagem' => 'Acesso negado para o seu perfil.'], 403);
        }

        return response()->json(
            $exames->orderByDesc('solicitado_em')->get()
        );
    }
}

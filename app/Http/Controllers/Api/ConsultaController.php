<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    /**
     * Lista consultas com base no usuário logado (Se for paciente traz as dele, se médico traz a agenda dele)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user instanceof \App\Models\Paciente) {
            $consultas = Consulta::with(['medico', 'sala'])
                ->where('paciente_id', $user->id)
                ->orderBy('data', 'asc')
                ->get();
        } else if ($user instanceof \App\Models\Medico) {
            $consultas = Consulta::with(['paciente', 'sala'])
                ->where('medico_id', $user->id)
                ->orderBy('data', 'asc')
                ->get();
        } else {
            // Se for Admin, retorna todas
            $consultas = Consulta::with(['medico', 'paciente', 'sala'])->get();
        }

        return response()->json($consultas);
    }

    /**
     * Agendar uma nova consulta
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'medico_id'   => 'required|exists:medicos,id',
            'paciente_id' => 'required|exists:pacientes,id',
            'sala_id'     => 'required|exists:salas,id',
            'data'        => 'required|date',
            'hora'        => 'required|date_format:H:i',
            'observacoes' => 'nullable|string',
        ]);

        // Validação de choque de horário do Médico
        $medicoOcupado = Consulta::where('medico_id', $validated['medico_id'])
            ->where('data', $validated['data'])
            ->where('hora', $validated['hora'])
            ->where('status', '!=', 'cancelada')
            ->exists();

        if ($medicoOcupado) {
            return response()->json(['mensagem' => 'O médico já possui uma consulta agendada para este dia e horário.'], 422);
        }

        // Validação de choque de horário do Paciente
        $pacienteOcupado = Consulta::where('paciente_id', $validated['paciente_id'])
            ->where('data', $validated['data'])
            ->where('hora', $validated['hora'])
            ->where('status', '!=', 'cancelada')
            ->exists();

        if ($pacienteOcupado) {
            return response()->json(['mensagem' => 'O paciente já possui uma consulta agendada para este mesmo horário.'], 422);
        }

        $consulta = Consulta::create($validated);

        return response()->json([
            'mensagem' => 'Consulta agendada com sucesso!',
            'consulta' => $consulta
        ], 201);
    }

    /**
     * Cancelar Consulta
     */
    public function cancelar($id)
    {
        $consulta = Consulta::findOrFail($id);
        $consulta->update(['status' => 'cancelada']);

        return response()->json(['mensagem' => 'Consulta cancelada com sucesso.']);
    }
}
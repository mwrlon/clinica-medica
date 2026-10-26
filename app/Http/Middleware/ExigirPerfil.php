<?php

namespace App\Http\Middleware;

use App\Models\Administrador;
use App\Models\Medico;
use App\Models\Paciente;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe uma rota a um ou mais perfis de usuário autenticado (via Sanctum).
 *
 * Uso: ->middleware('perfil:admin')  ou  ->middleware('perfil:medico,admin')
 *
 * Perfis: admin, medico, paciente.
 */
class ExigirPerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $usuario = $request->user();

        $perfilAtual = match (true) {
            $usuario instanceof Administrador => 'admin',
            $usuario instanceof Medico        => 'medico',
            $usuario instanceof Paciente      => 'paciente',
            default                           => null,
        };

        if ($perfilAtual === null || !in_array($perfilAtual, $perfis, true)) {
            return response()->json([
                'mensagem' => 'Acesso negado para o seu perfil.',
            ], 403);
        }

        // Médico inativado depois do login não pode continuar usando o token antigo
        if ($usuario instanceof Medico && $usuario->status !== 'ativo') {
            return response()->json([
                'mensagem' => 'Acesso negado. Cadastro inativo.',
            ], 403);
        }

        return $next($request);
    }
}

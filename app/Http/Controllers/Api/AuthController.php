<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginPaciente(Request $request): JsonResponse
    {
        return $this->autenticar($request, Paciente::class, 'paciente');
    }

    public function loginMedico(Request $request): JsonResponse
    {
        return $this->autenticar($request, Medico::class, 'medico');
    }

    public function loginAdmin(Request $request): JsonResponse
    {
        return $this->autenticar($request, Administrador::class, 'admin');
    }

    /**
     * Login genérico (o perfil vem no campo "tipo"). Mantido por compatibilidade.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|in:paciente,medico',
        ]);

        return $request->tipo === 'medico'
            ? $this->loginMedico($request)
            : $this->loginPaciente($request);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoga o token atual do usuário logado
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensagem' => 'Sessão encerrada com sucesso!'
        ]);
    }

    /**
     * Dados do usuário autenticado (usado para preencher os painéis).
     */
    public function perfil(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario instanceof Medico) {
            $usuario->load('especialidade');
        } elseif ($usuario instanceof Paciente) {
            $usuario->load('convenio');
        }

        return response()->json([
            'perfil'  => $this->nomeDoPerfil($usuario),
            'usuario' => $usuario,
        ]);
    }

    /**
     * Valida as credenciais no model informado e emite o token de acesso.
     *
     * Credenciais inválidas respondem 422 (campo "email"), que é o que o front-end
     * já espera; médico inativo responde 403.
     */
    private function autenticar(Request $request, string $model, string $perfil): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'senha' => 'required',
        ]);

        $usuario = $model::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->senha, $usuario->senha_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas. Verifique e-mail e senha.'],
            ]);
        }

        if ($usuario instanceof Medico && $usuario->status !== 'ativo') {
            return response()->json([
                'mensagem' => 'Acesso negado. Cadastro inativo.'
            ], 403);
        }

        if ($usuario instanceof Medico) {
            $usuario->load('especialidade');
        }

        $token = $usuario->createToken($perfil . '_token')->plainTextToken;

        return response()->json([
            'mensagem'     => 'Login realizado com sucesso!',
            'perfil'       => $perfil,
            'usuario'      => $usuario,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    private function nomeDoPerfil($usuario): ?string
    {
        return match (true) {
            $usuario instanceof Administrador => 'admin',
            $usuario instanceof Medico        => 'medico',
            $usuario instanceof Paciente      => 'paciente',
            default                           => null,
        };
    }
}

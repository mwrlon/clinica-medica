<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Administrador;

class AuthController extends Controller {
    public function loginAdmin(Request $request){
    $request->validate([
        'email' => 'required|email',
        'senha' => 'required',
    ]);

    $admin = Administrador::where('email', $request->email)->first();

    if (!$admin || !Hash::check($request->senha, $admin->senha_hash)) {
        return response()->json([
            'mensagem' => 'Credenciais administrativas inválidas.'
        ], 412);
    }

    $token = $admin->createToken('admin_token')->plainTextToken;

    return response()->json([
        'mensagem'     => 'Bem-vindo(a), ' . $admin->nome,
        'perfil'       => 'admin',
        'usuario'      => $admin,
        'access_token' => $token,
        'token_type'   => 'Bearer',
    ]);
    }

    public function login(Request $request){
        // 1. Validar os dados de entrada
        $request->validate([
            'email' => 'required|email',
            'senha' => 'required',
            'tipo'  => 'required|in:paciente,medico', // Define quem está tentando logar
        ]);

        // 2. Identificar a tabela com base no tipo
        $model = $request->tipo === 'medico' ? Medico::class : Paciente::class;
        $usuario = $model::where('email', $request->email)->first();

        // 3. Verificar se o usuário existe e se a senha confere
        if (!$usuario || !Hash::check($request->senha, $usuario->senha_hash)) {
            return response()->json([
                'mensagem' => 'Credenciais inválidas. Verifique e-mail e senha.'
            ], 412);
        }

        // 4. Se for médico, verificar se está ativo
        if ($request->tipo === 'medico' && $usuario->status !== 'ativo') {
            return response()->json([
                'mensagem' => 'Acesso negado. Cadastro inativo.'
            ], 403);
        }

        // 5. Criar o Token de Acesso da API
        $token = $usuario->createToken('auth_token')->plainTextToken;

        // 6. Retornar resposta JSON
        return response()->json([
            'mensagem' => 'Login realizado com sucesso!',
            'tipo'     => $request->tipo,
            'usuario'  => [
                'id'    => $usuario->id,
                'nome'  => $usuario->nome,
                'email' => $usuario->email,
            ],
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ], 200);
    }

    public function logout(Request $request){
        // Revoga o token atual do usuário logado
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensagem' => 'Sessão encerrada com sucesso!'
        ]);
    }
}




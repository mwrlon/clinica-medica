<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * "Meu perfil": cada usuário edita os próprios dados e troca a própria senha.
 *
 * O que não aparece aqui (CPF, CRM, especialidade, situação do cadastro) só o administrador
 * muda, pelo painel dele.
 */
class PerfilController extends Controller
{
    /**
     * PUT /api/perfil — atualiza os dados do usuário logado.
     */
    public function atualizar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $validated = $request->validate($this->regras($usuario));

        $usuario->update($validated);

        if ($usuario instanceof Paciente) {
            $usuario->load('convenio');
        } elseif ($usuario instanceof Medico) {
            $usuario->load('especialidade');
        }

        return response()->json([
            'mensagem' => 'Dados atualizados com sucesso!',
            'usuario'  => $usuario,
        ]);
    }

    /**
     * PUT /api/perfil/senha — troca a senha exigindo a senha atual.
     * As outras sessões abertas (outros aparelhos) são encerradas; a atual continua.
     */
    public function alterarSenha(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $dados = $request->validate([
            'senha_atual'     => 'required|string',
            'senha'           => 'required|string|min:8',
            'confirmar_senha' => 'required|same:senha',
        ]);

        if (!Hash::check($dados['senha_atual'], $usuario->senha_hash)) {
            throw ValidationException::withMessages([
                'senha_atual' => ['A senha atual está incorreta.'],
            ]);
        }

        if (Hash::check($dados['senha'], $usuario->senha_hash)) {
            throw ValidationException::withMessages([
                'senha' => ['A nova senha deve ser diferente da senha atual.'],
            ]);
        }

        $usuario->update(['senha_hash' => Hash::make($dados['senha'])]);

        $atual = $usuario->currentAccessToken();
        $usuario->tokens()
            ->when($atual instanceof PersonalAccessToken, fn ($q) => $q->where('id', '!=', $atual->id))
            ->delete();

        return response()->json(['mensagem' => 'Senha alterada com sucesso!']);
    }

    /** Campos que cada perfil pode editar. */
    private function regras($usuario): array
    {
        return match (true) {
            $usuario instanceof Paciente => [
                'nome'            => 'required|string|max:150',
                'data_nascimento' => 'required|date|before:today',
                'sexo'            => 'required|in:M,F,Outro',
                'telefone'        => 'required|string|max:20',
                'email'           => ['required', 'email', 'max:150', Rule::unique('pacientes', 'email')->ignore($usuario->id)],
                'endereco'        => 'nullable|string|max:255',
                'convenio_id'     => 'nullable|exists:convenios,id',
            ],
            $usuario instanceof Medico => [
                'nome'     => 'required|string|max:150',
                'telefone' => 'required|string|max:20',
                'email'    => ['required', 'email', 'max:150', Rule::unique('medicos', 'email')->ignore($usuario->id)],
            ],
            $usuario instanceof Administrador => [
                'nome'  => 'required|string|max:150',
                'email' => ['required', 'email', 'max:150', Rule::unique('administradores', 'email')->ignore($usuario->id)],
            ],
        };
    }
}

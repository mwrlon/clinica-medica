<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Cadastro de Novo Paciente
     */
    public function cadastrarPaciente(Request $request)
    {
        // O formulário envia "confirmar_senha"; clientes que seguem o padrão do Laravel
        // enviam "senha_confirmation". Os dois nomes são aceitos.
        if (blank($request->input('confirmar_senha')) && $request->filled('senha_confirmation')) {
            $request->merge(['confirmar_senha' => $request->input('senha_confirmation')]);
        }

        // 1. Validação dos dados recebidos
        $validated = $request->validate([
            'nome'            => 'required|string|max:150',
            'cpf'             => 'required|string|max:14|unique:pacientes,cpf',
            'data_nascimento' => 'required|date|before:today',
            'sexo'            => 'required|in:M,F,Outro',
            'telefone'        => 'required|string|max:20',
            'email'           => 'required|email|max:150|unique:pacientes,email',
            'senha'           => 'required|string|min:8',
            'confirmar_senha' => 'required|same:senha',
            'termos'          => 'sometimes|accepted',
            'endereco'        => 'nullable|string|max:255',
            'convenio_id'     => 'nullable|exists:convenios,id',
        ]);

        // 2. Persistência no banco com criptografia de senha (bcrypt)
        $paciente = Paciente::create([
            'nome'            => $validated['nome'],
            'cpf'             => $validated['cpf'],
            'data_nascimento' => $validated['data_nascimento'],
            'sexo'            => $validated['sexo'],
            'telefone'        => $validated['telefone'],
            'email'           => $validated['email'],
            'senha_hash'      => Hash::make($validated['senha']),
            'endereco'        => $validated['endereco'] ?? null,
            'convenio_id'     => $validated['convenio_id'] ?? null,
        ]);

        // 3. Emite token para já logar automaticamente após cadastrar (Opcional)
        $token = $paciente->createToken('paciente_token')->plainTextToken;

        return response()->json([
            'mensagem'     => 'Paciente cadastrado com sucesso!',
            'perfil'       => 'paciente',
            'usuario'      => $paciente,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }

    /**
     * Cadastro de Novo Médico
     */
    public function cadastrarMedico(Request $request)
    {
        // 1. Validação dos dados
        $validated = $request->validate([
            'nome'             => 'required|string|max:150',
            'crm'              => 'required|string|max:20|unique:medicos,crm',
            'especialidade_id' => 'required|exists:especialidades,id',
            'telefone'         => 'required|string|max:20',
            'email'            => 'required|email|max:150|unique:medicos,email',
            'senha'            => 'required|string|min:8|confirmed',
        ]);

        // 2. Persistência do Médico no banco
        $medico = Medico::create([
            'nome'             => $validated['nome'],
            'crm'              => $validated['crm'],
            'especialidade_id' => $validated['especialidade_id'],
            'telefone'         => $validated['telefone'],
            'email'            => $validated['email'],
            'senha_hash'       => Hash::make($validated['senha']),
            'status'           => 'ativo',
        ]);

        $token = $medico->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensagem'     => 'Médico cadastrado com sucesso!',
            'usuario'      => $medico,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }
}
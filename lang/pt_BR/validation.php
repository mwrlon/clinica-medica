<?php

// Mensagens de validação em português (somente as regras usadas no projeto).
// Qualquer regra que não esteja aqui cai no inglês padrão do Laravel (fallback_locale).
return [
    'accepted'        => 'Você precisa aceitar os :attribute.',
    'after_or_equal'  => 'A :attribute deve ser hoje ou uma data futura.',
    'array'           => 'O campo :attribute deve ser uma lista.',
    'before'          => 'A :attribute deve ser uma data anterior a hoje.',
    'date'            => 'A :attribute não é uma data válida.',
    'date_format'     => 'O campo :attribute não está no formato esperado (:format).',
    'email'           => 'Informe um :attribute válido.',
    'exists'          => 'O valor informado em :attribute não existe.',
    'in'              => 'O valor informado em :attribute é inválido.',
    'max'             => [
        'array'   => 'O campo :attribute deve ter no máximo :max itens.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string'  => 'O campo :attribute deve ter no máximo :max caracteres.',
    ],
    'min'             => [
        'array'   => 'O campo :attribute deve ter no mínimo :min itens.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string'  => 'A :attribute deve ter no mínimo :min caracteres.',
    ],
    'required'        => 'O campo :attribute é obrigatório.',
    'same'            => 'A :attribute não confere com a :other.',
    'string'          => 'O campo :attribute deve ser um texto.',
    'unique'          => 'Este :attribute já está cadastrado.',

    'attributes' => [
        'nome'            => 'nome',
        'cpf'             => 'CPF',
        'data_nascimento' => 'data de nascimento',
        'sexo'            => 'sexo',
        'telefone'        => 'telefone',
        'email'           => 'e-mail',
        'senha'           => 'senha',
        'confirmar_senha' => 'confirmação da senha',
        'termos'          => 'termos de uso',
        'data'            => 'data',
        'hora'            => 'hora',
        'medico_id'       => 'médico',
        'paciente_id'     => 'paciente',
        'sala_id'         => 'sala',
        'consulta_id'     => 'consulta',
        'diagnostico'     => 'diagnóstico',
        'identificacao'   => 'identificação',
    ],
];

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecuperacaoSenhaController;



Route::get('/', function () {
    return view('index');
});

Route::get('/login', function () {
    return view('loginPaciente');
})->name('login');

Route::get('/login-medico', function () {
    return view('loginMedico');
})->name('loginMedico');

Route::get('/login-paciente', function () {
    return view('loginPaciente');
})->name('loginPaciente');

Route::get('/cadastro', function () {
    return view('cadastro');
})->name('cadastro');

Route::get('/esqueci-senha', function () {
    return view('esqueciSenha');
})->name('esqueciSenha');

// Recuperação de senha (pacientes e médicos)
Route::post('/esqueci-senha', [RecuperacaoSenhaController::class, 'enviarLink'])
    ->middleware('throttle:5,1')
    ->name('esqueciSenha.enviar');

Route::get('/redefinir-senha/{token}', [RecuperacaoSenhaController::class, 'formulario'])
    ->name('senha.redefinir');

Route::post('/redefinir-senha', [RecuperacaoSenhaController::class, 'redefinir'])
    ->middleware('throttle:10,1')
    ->name('senha.atualizar');

Route::get('/medico', function () {
    return view('medico');
})->name('medico');

Route::get('/paciente', function () {
    return view('paciente');
})->name('paciente');


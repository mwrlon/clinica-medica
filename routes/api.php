<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ConsultaController;
use App\Http\Controllers\Api\Admin\MedicoAdminController;
use App\Http\Controllers\Api\Admin\EspecialidadeAdminController;
use App\Http\Controllers\Api\Admin\SalaAdminController;
use App\Http\Controllers\Api\Admin\ConvenioAdminController;
use App\Http\Controllers\Api\ExameController;
use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\PacienteController;

/*
|--------------------------------------------------------------------------
| 1. ROTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::post('/cadastrar', [RegisterController::class, 'cadastrarPaciente']);

// Endpoints de Login por Perfil
Route::post('/login/paciente', [AuthController::class, 'loginPaciente']);
Route::post('/login/medico', [AuthController::class, 'loginMedico']);
Route::post('/login/admin', [AuthController::class, 'loginAdmin']);

// Dados públicos para exibição em formulários de agendamento/home
Route::get('/especialidades', [EspecialidadeAdminController::class, 'index']);
Route::get('/medicos', [MedicoAdminController::class, 'indexPublico']);


/*
|--------------------------------------------------------------------------
| 2. PAINEL ADMINISTRATIVO (Requer autenticação e perfil de administrador)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'perfil:admin'])->prefix('admin')->group(function () {

    // Gestão de Médicos (CRUD)
    Route::get('/medicos', [MedicoAdminController::class, 'index']);
    Route::post('/medicos', [MedicoAdminController::class, 'store']);
    Route::get('/medicos/{id}', [MedicoAdminController::class, 'show']);
    Route::put('/medicos/{id}', [MedicoAdminController::class, 'update']);
    Route::delete('/medicos/{id}', [MedicoAdminController::class, 'destroy']);

    // Gestão de Especialidades
    Route::post('/especialidades', [EspecialidadeAdminController::class, 'store']);
    Route::delete('/especialidades/{id}', [EspecialidadeAdminController::class, 'destroy']);

    // Gestão de Salas
    Route::get('/salas', [SalaAdminController::class, 'index']);
    Route::post('/salas', [SalaAdminController::class, 'store']);

    // Gestão de Convênios
    Route::get('/convenios', [ConvenioAdminController::class, 'index']);
    Route::post('/convenios', [ConvenioAdminController::class, 'store']);
});


/*
|--------------------------------------------------------------------------
| 3. ÁREA DO PACIENTE & MÉDICO (Consultas, Exames e Prontuários)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/perfil', [AuthController::class, 'perfil']);

    // Consultas
    Route::get('/consultas', [ConsultaController::class, 'index']);
    Route::post('/consultas', [ConsultaController::class, 'store']);
    Route::put('/consultas/{id}/cancelar', [ConsultaController::class, 'cancelar']);
    Route::put('/consultas/{id}/reagendar', [ConsultaController::class, 'reagendar']);

    // Horários livres de um médico em um dia (para agendar e reagendar)
    Route::get('/medicos/{id}/horarios', [AgendaController::class, 'horarios']);

    // Busca de pacientes por nome/CPF (para o médico marcar encaixes)
    Route::get('/pacientes', [PacienteController::class, 'index'])
        ->middleware('perfil:medico,admin');

    // Exames e Atendimentos
    Route::get('/exames', [ExameController::class, 'index']);
    Route::post('/atendimentos', [ConsultaController::class, 'registrarAtendimento'])
        ->middleware('perfil:medico'); // Exclusivo Médico
});

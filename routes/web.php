<?php

use Illuminate\Support\Facades\Route;

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


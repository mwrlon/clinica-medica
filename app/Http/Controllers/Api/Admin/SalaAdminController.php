<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sala;
use Illuminate\Http\Request;

class SalaAdminController extends Controller
{
    public function index() { return response()->json(Sala::all()); }

    public function store(Request $request)
    {
        // A tabela "salas" possui apenas a coluna "identificacao" (ex.: "Consultório 01")
        $validated = $request->validate([
            'identificacao' => 'required|string|max:30|unique:salas,identificacao',
        ]);

        return response()->json(Sala::create($validated), 201);
    }
}

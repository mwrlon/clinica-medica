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
        $validated = $request->validate(['numero' => 'required|string', 'bloco' => 'nullable|string']);
        return response()->json(Sala::create($validated), 201);
    }
}
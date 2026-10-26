<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use Illuminate\Http\Request;

class ConvenioAdminController extends Controller
{
    public function index() { return response()->json(Convenio::all()); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:100|unique:convenios,nome',
        ]);

        return response()->json(Convenio::create($validated), 201);
    }
}

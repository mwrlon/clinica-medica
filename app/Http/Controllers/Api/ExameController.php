<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exame;
use Illuminate\Http\Request;

class ExameController extends Controller
{
    public function index(Request $request)
    {
        $exames = Exame::where('paciente_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($exames);
    }
}
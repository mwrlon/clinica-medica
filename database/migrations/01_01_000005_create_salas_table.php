<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salas', function (Blueprint $table) {
            $table->id();
            $table->string('identificacao', 30)->unique('uq_salas_identificacao')->comment('Ex.: Consultório 01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salas');
    }
};
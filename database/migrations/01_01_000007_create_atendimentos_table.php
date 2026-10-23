<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->unique('uq_atendimentos_consulta')->constrained('consultas')->cascadeOnDelete();
            $table->text('diagnostico');
            $table->text('receita')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('criado_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos');
    }
};
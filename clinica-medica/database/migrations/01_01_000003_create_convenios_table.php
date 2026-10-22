<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique('uq_convenios_nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convenios');
    }
};
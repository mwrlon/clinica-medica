<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('especialidades')->insert([
            ['nome' => 'Clínica Geral'],
            ['nome' => 'Cardiologia'],
            ['nome' => 'Pediatria'],
            ['nome' => 'Ortopedia'],
            ['nome' => 'Dermatologia'],
            ['nome' => 'Ginecologia'],
            ['nome' => 'Neurologia'],
            ['nome' => 'Oftalmologia'],
        ]);

        DB::table('convenios')->insert([
            ['nome' => 'Unimed'],
            ['nome' => 'Bradesco Saúde'],
            ['nome' => 'SulAmérica'],
            ['nome' => 'Amil'],
        ]);

        DB::table('salas')->insert([
            ['identificacao' => 'Consultório 01'],
            ['identificacao' => 'Consultório 02'],
            ['identificacao' => 'Consultório 03'],
        ]);
    }
}
<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Dados básicos da clínica. Pode ser executado várias vezes (não duplica registros).
 *
 *   php artisan db:seed
 *
 * Fora de produção também carrega o DemoSeeder (médicos, paciente e consultas de exemplo).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Clínica Geral', 'Cardiologia', 'Pediatria', 'Ortopedia', 'Dermatologia',
            'Ginecologia', 'Neurologia', 'Oftalmologia', 'Nutrição', 'Psicologia',
        ] as $nome) {
            DB::table('especialidades')->insertOrIgnore(['nome' => $nome]);
        }

        foreach (['Unimed', 'Bradesco Saúde', 'SulAmérica', 'Amil'] as $nome) {
            DB::table('convenios')->insertOrIgnore(['nome' => $nome]);
        }

        foreach (['Consultório 01', 'Consultório 02', 'Consultório 03'] as $identificacao) {
            DB::table('salas')->insertOrIgnore(['identificacao' => $identificacao]);
        }

        $this->criarAdministrador();

        if (!app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }

    /**
     * Cria o administrador inicial (necessário para usar o login e as rotas /api/admin/*).
     *
     * Credenciais via .env: ADMIN_EMAIL e ADMIN_PASSWORD. Fora de produção, se ADMIN_PASSWORD
     * não existir, usa uma senha de desenvolvimento. Em produção a senha é obrigatória.
     * Um administrador que já existe nunca tem a senha alterada.
     */
    private function criarAdministrador(): void
    {
        $email = config('app.admin_email');
        $senha = config('app.admin_password');

        if (!$senha) {
            if (app()->isProduction()) {
                $this->command?->warn('ADMIN_PASSWORD não definida: administrador não foi criado.');
                return;
            }

            $senha = 'admin12345'; // somente desenvolvimento
        }

        Administrador::firstOrCreate(
            ['email' => $email],
            ['nome' => 'Administrador ProSaúde', 'senha_hash' => Hash::make($senha)]
        );
    }
}

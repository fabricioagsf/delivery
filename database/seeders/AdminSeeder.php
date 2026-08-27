<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Verifica o acesso do painel. Usuário e senha do admin vivem SOMENTE
     * na tabela users do banco — nunca em código, seeder ou .env.
     * Se o banco ainda não tiver admin, oriente o cadastro via:
     *   php artisan admin:senha
     */
    public function run(): void
    {
        if (User::exists()) {
            $this->command?->line('  ✓ Acesso do painel já gravado no banco (tabela users).');

            return;
        }

        $this->command?->warn('  ! Nenhum admin no banco. Cadastre o acesso com: php artisan admin:senha');
    }
}

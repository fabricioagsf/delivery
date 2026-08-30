<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TextoSistemaSeeder::class,
            ConfiguracaoSeeder::class,
            AdminSeeder::class,
            ModuloSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Mesa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Configura 10 mesas para a loja matriz (slug gostosuras). Idempotente:
 * mesas com o mesmo código da mesma loja não são duplicadas.
 */
class MesasSeeder extends Seeder
{
    public function run(): void
    {
        $lojaId = DB::table('tenants')->where('slug', 'gostosuras')->value('id') ?? 1;

        $capacidades = [2, 4, 4, 4, 6, 6, 6, 8, 2, 4];

        foreach (range(1, 10) as $i) {
            $codigo = str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            Mesa::firstOrCreate(
                ['loja_id' => $lojaId, 'codigo' => $codigo],
                [
                    'nome' => 'Mesa '.$codigo,
                    'capacidade' => $capacidades[$i - 1],
                    'ativo' => true,
                ]
            );
        }

        $this->command?->info('10 mesas configuradas para a loja matriz.');
    }
}
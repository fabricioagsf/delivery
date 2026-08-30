<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;

/**
 * Semeia as linhas globais (loja_id NULL) da tabela `modulos`, com o estado
 * atual de cada módulo. A ativação/desativação é feita APENAS direto no
 * banco: mude o flag `ativo` (1 = ligado, 0 = desligado) nesta tabela.
 */
class ModuloSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = [
            ['slug' => 'pdv', 'nome' => 'PDV (mesas, tablet e caixa)', 'ativo' => true],
            ['slug' => 'delivery', 'nome' => 'Delivery (vendas online)', 'ativo' => true],
            ['slug' => 'item_venda', 'nome' => 'Produtos e serviços', 'ativo' => true],
            ['slug' => 'fidelidade', 'nome' => 'Fidelidade / pontos', 'ativo' => true],
            ['slug' => 'cupons', 'nome' => 'Cupons de desconto', 'ativo' => true],
        ];

        foreach ($modulos as $modulo) {
            Modulo::updateOrCreate(
                ['loja_id' => null, 'slug' => $modulo['slug']],
                ['nome' => $modulo['nome'], 'ativo' => $modulo['ativo']]
            );
        }
    }
}
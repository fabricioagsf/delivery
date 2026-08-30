<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Produto;
use App\Models\ProdutoEstoque;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga mínima usada pelos testes Dusk (delivery_test):
 * a categoria "Sobremesas" e o produto "Brigadeiro" (R$ 8,90) que os
 * testes esperam na tela do tablet do garçom.
 *
 * Idempotente — usa updateOrCreate por slug.
 */
class TesteCardapioSeeder extends Seeder
{
    public function run(): void
    {
        $lojaId = DB::table('tenants')->where('slug', 'gostosuras')->value('id') ?? 1;

        $categoria = Categoria::updateOrCreate(
            ['slug' => 'sobremesas'],
            ['nome' => 'Sobremesas', 'ativo' => true]
        );

        $produto = Produto::updateOrCreate(
            ['slug' => 'brigadeiro-teste'],
            [
                'categoria_id' => $categoria->id,
                'nome' => 'Brigadeiro',
                'descricao' => 'Brigadeiro gourmet tradicional.',
                'preco' => 8.90,
                'destaque' => true,
                'ativo' => true,
            ]
        );

        ProdutoEstoque::updateOrCreate(
            ['produto_id' => $produto->id, 'loja_id' => $lojaId],
            ['estoque' => 100, 'estoque_minimo' => 5]
        );
    }
}

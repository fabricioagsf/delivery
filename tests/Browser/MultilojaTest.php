<?php

namespace Tests\Browser;

use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\ProdutoEstoque;
use Fabricioagsf\AuthMulti\Models\Tenant as Loja;
use Tests\DuskTestCase;

class MultilojaTest extends DuskTestCase
{
    protected string $slugFilial = 'loja-dusk-filial-isolamento';

    protected function criarFilial(): int
    {
        $filial = Loja::where('slug', $this->slugFilial)->first();
        if (! $filial) {
            $filial = Loja::create([
                'nome' => 'Loja Dusk Filial Isolamento',
                'slug' => $this->slugFilial,
                'status' => 'ativo',
            ]);
        }

        return (int) $filial->id;
    }

    protected function tearDown(): void
    {
        $filial = Loja::where('slug', $this->slugFilial)->first();
        if ($filial) {
            Pedido::where('loja_id', $filial->id)->delete();
            $filial->delete();
        }
        parent::tearDown();
    }

    /**
     * Isolamento de pedidos: um pedido criado na matriz NÃO aparece na lista
     * de pedidos da filial e vice-versa (via global scope + middleware GarantirLojaAtiva).
     */
    public function test_pedidos_de_outra_loja_nao_aparecem_na_lista(): void
    {
        $filialId = $this->criarFilial();
        $matrizId = (int) loja_atual_id();
        $codigoMatriz = 'ISO-M-' . now()->timestamp;
        $codigoFilial = 'ISO-F-' . now()->timestamp;

        Pedido::create([
            'loja_id' => $matrizId,
            'codigo' => $codigoMatriz,
            'nome_cliente' => 'Cliente Isolamento',
            'telefone' => '(11) 99999-0001',
            'tipo_entrega' => 'retirada',
            'forma_pagamento' => 'pix',
            'subtotal' => 0,
            'total' => 0,
            'status' => 'novo',
        ]);

        Pedido::create([
            'loja_id' => $filialId,
            'codigo' => $codigoFilial,
            'nome_cliente' => 'Cliente Isolamento',
            'telefone' => '(11) 99999-0002',
            'tipo_entrega' => 'retirada',
            'forma_pagamento' => 'pix',
            'subtotal' => 0,
            'total' => 0,
            'status' => 'novo',
        ]);

        $this->browse(function ($browser) use ($filialId, $codigoMatriz, $codigoFilial) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            // Matriz → vê pedido da matriz, NÃO vê da filial
            $browser->visit('/admin/pedidos')
                ->pause(2000)
                ->assertSee($codigoMatriz)
                ->assertDontSee($codigoFilial);

            // Troca para filial via seletor do painel
            $browser->script("
                var form = document.querySelector('form[action=\"/admin/lojas/trocar\"]');
                if (form) {
                    var select = form.querySelector('select[name=\"loja_id\"]');
                    if (select) { select.value = '$filialId'; form.submit(); }
                } else {
                    console.log('Formulario de troca de loja NAO encontrado!');
                }
            ");
            $browser->pause(4000);
            $browser->screenshot('multiloja_pos_troca');

            // Filial → vê pedido da filial, NÃO vê da matriz
            $browser->visit('/admin/pedidos')
                ->pause(3000)
                ->assertSee($codigoFilial)
                ->assertDontSee($codigoMatriz);

            $browser->screenshot('multiloja_isolamento_pedidos');
        });
    }

    /**
     * Estoque é individual: mesmo produto (global) aparece na vitrine de ambas
     * as lojas, mas com disponibilidade diferente conforme o estoque da loja.
     */
    public function test_produto_global_com_estoque_variando_por_loja(): void
    {
        $filialId = $this->criarFilial();
        $matrizId = (int) loja_atual_id();
        $categoria = Categoria::first();

        $produto = Produto::create([
            'categoria_id' => $categoria->id,
            'nome' => 'Brig Iso-' . now()->timestamp,
            'slug' => 'brig-iso-' . now()->timestamp,
            'descricao' => 'Teste.',
            'preco' => 8.5,
            'ativo' => true,
            'destaque' => false,
        ]);

        ProdutoEstoque::create([
            'produto_id' => $produto->id,
            'loja_id' => $matrizId,
            'estoque' => 10,
            'estoque_minimo' => 2,
        ]);

        ProdutoEstoque::create([
            'produto_id' => $produto->id,
            'loja_id' => $filialId,
            'estoque' => 0,
            'estoque_minimo' => 2,
        ]);

        $this->browse(function ($browser) use ($filialId, $produto) {
            $matrizId = (int) loja_atual_id();

            // Vitrine da matriz: produto disponível (tem estoque)
            $browser->visit('/vitrine')
                ->pause(4000);

            $temFonte = str_contains($browser->driver->getPageSource(), $produto->nome);
            $this->assertTrue($temFonte, "Vitrine deveria listar produto {$produto->nome}");

            $botaoMatriz = $browser->element("button[data-produto-id=\"{$produto->id}\"][data-adicionar-direto]");
            $this->assertNotNull($botaoMatriz, "Matriz (loja {$matrizId}): produto deveria ter estoque e botão adicionar");

            // Troca para filial via seletor público
            $browser->script("
                var form = document.querySelector('form[action=\"/loja/trocar\"]');
                if (form) {
                    var select = form.querySelector('select[name=\"loja_id\"]');
                    if (select) { select.value = '$filialId'; form.submit(); }
                }
            ");
            $browser->pause(3000);

            // Vitrine da filial: produto esgotado (sem botão adicionar)
            $browser->visit('/vitrine')
                ->pause(2500);

            $botaoFilial = $browser->element("button[data-produto-id=\"{$produto->id}\"][data-adicionar-direto]");
            $this->assertNull($botaoFilial, "Filial (loja {$filialId}): produto deveria estar esgotado");

            $browser->screenshot('multiloja_estoque_isolado');
        });
    }
}

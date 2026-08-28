<?php

namespace Tests\Browser;

use App\Models\Categoria;
use App\Models\Produto;
use Tests\DuskTestCase;

class CardapioTest extends DuskTestCase
{
    protected string $slug = 'acai-dusk-cardapio';

    protected function tearDown(): void
    {
        Produto::where('slug', $this->slug)->delete();
        parent::tearDown();
    }

    protected function criarProdutoDeTeste(): Produto
    {
        Produto::where('slug', $this->slug)->delete();

        return Produto::create([
            'categoria_id' => Categoria::first()->id,
            'nome' => 'Açaí Dusk Cardápio',
            'slug' => $this->slug,
            'descricao' => 'Produto de teste para o cardápio digital.',
            'preco' => 9.5,
            'estoque' => 20,
            'estoque_minimo' => 5,
            'ativo' => true,
            'destaque' => false,
        ]);
    }

    /**
     * Fluxo visual do cardápio digital: o cliente abre o cardápio, vê os
     * itens agrupados por categoria e pede direto de lá (vai para o carrinho).
     */
    public function test_cardapio_lista_itens_e_permite_pedir(): void
    {
        $produto = $this->criarProdutoDeTeste();

        $this->browse(function ($browser) use ($produto) {
            $browser->visit('/cardapio')
                ->pause(1000)
                ->assertSee('Cardápio')
                ->assertSee('Açaí Dusk Cardápio')
                ->assertVisible('.cardapio-nav')
                ->screenshot('cardapio_vitrine');

            // Pede direto do cardápio (produto sem complementos)
            $browser->click("[data-produto-id=\"".$produto->id."\"][data-adicionar-direto]")
                ->pause(1200)
                ->assertSee('Adicionado ao carrinho!')
                ->screenshot('cardapio_adicionado');

            // Conferir no carrinho
            $browser->visit('/carrinho')
                ->pause(1200)
                ->assertSee('Açaí Dusk Cardápio')
                ->assertSee('R$ 9,50')
                ->screenshot('cardapio_carrinho');
        });
    }
}

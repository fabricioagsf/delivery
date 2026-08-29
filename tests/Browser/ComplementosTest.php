<?php

namespace Tests\Browser;

use App\Models\Categoria;
use App\Models\Produto;
use App\Models\ProdutoEstoque;
use Illuminate\Support\Facades\DB;
use Tests\DuskTestCase;

class ComplementosTest extends DuskTestCase
{
    protected string $slug = 'acai-dusk-complementos';

    protected function tearDown(): void
    {
        // Remove pedidos de teste criados no fluxo ponta a ponta (itens vêm junto via cascade)
        \App\Models\PedidoItem::whereHas('pedido', fn ($q) => $q->whereNull('cliente_id'))
            ->where('nome_produto', 'Açaí Dusk Complementos')
            ->delete();
        \App\Models\Pedido::whereNull('cliente_id')
            ->where('nome_cliente', 'Cliente Teste Dusk')
            ->delete();
        Produto::where('slug', $this->slug)->delete();
        parent::tearDown();
    }

    protected function criarProdutoComComplementos(): Produto
    {
        Produto::where('slug', $this->slug)->delete();

        $produto = Produto::create([
            'categoria_id' => Categoria::first()->id,
            'nome' => 'Açaí Dusk Complementos',
            'slug' => $this->slug,
            'descricao' => 'Produto de teste para personalizações.',
            'preco' => 10.0,
            'ativo' => true,
            'destaque' => false,
        ]);

        $lojaId = DB::table('tenants')->where('slug', 'gostosuras')->value('id');
        if ($lojaId) {
            ProdutoEstoque::create([
                'produto_id' => $produto->id,
                'loja_id' => $lojaId,
                'estoque' => 20,
                'estoque_minimo' => 5,
            ]);
        }

        $produto->complementos()->create(['tipo' => 'adicional', 'nome' => 'Cobertura de chocolate', 'preco' => 2, 'ordem' => 10]);
        $produto->complementos()->create(['tipo' => 'remocao', 'nome' => 'leite condensado', 'preco' => 0, 'ordem' => 20]);

        return $produto->fresh('complementos');
    }

    /**
     * Fluxo visual: o cliente personaliza a gostosura, escolhe adicional e
     * remoção, adiciona ao carrinho e vê o valor (base + adicionais) na página.
     */
    public function test_cliente_personaliza_e_adiciona_ao_carrinho(): void
    {
        $produto = $this->criarProdutoComComplementos();

        $this->browse(function ($browser) use ($produto) {
            $browser->visit('/')
                ->pause(1500)
                ->assertSee('Açaí Dusk Complementos')
                ->assertSee('Personalizar')
                ->screenshot('complementos_vitrine');

            // Abre a personalização do produto que tem complementos
            $browser->click("[data-produto-id=\"".$produto->id."\"][data-modal-personalizar]")
                ->pause(800)
                ->assertVisible('#modal-personalizar')
                ->assertSee('Cobertura de chocolate')
                ->assertSee('leite condensado')
                ->screenshot('complementos_modal');

            // Marca o adicional (cobertura +R$2) e a remoção (leite condensado)
            $browser->check('#modal-personalizar-corpo input[data-tipo="adicional"]')
                ->check('#modal-personalizar-corpo input[data-tipo="remocao"]')
                ->pause(300);

            // 2 unidades: (10 + 2) × 2 = R$ 24,00
            $browser->click('[data-qtd="mais"]')
                ->pause(600)
                ->assertSeeIn('[data-total]', 'R$ 24,00')
                ->screenshot('complementos_modal_total');

            // Confirma: adiciona ao carrinho
            $browser->click('[data-confirmar]')
                ->pause(1500)
                ->assertSee('Adicionado ao carrinho!');

            // Página do carrinho: linha com a personalização e o valor certo
            $browser->visit('/carrinho')
                ->pause(1200)
                ->assertSee('Açaí Dusk Complementos')
                ->assertSee('Cobertura de chocolate')
                ->assertSee('sem leite condensado')
                ->assertSee('R$ 24,00')
                ->screenshot('complementos_carrinho');

            // Finalizar o pedido (retirada + pix — sem gateway, sem endereço)
            $browser->click('a[href="'.route('checkout').'"]')
                ->pause(1000)
                ->assertVisible('#form-checkout')
                ->assertSee('Resumo do pedido')
                ->assertSee('Açaí Dusk Complementos')
                ->assertSee('R$ 24,00')
                ->screenshot('complementos_checkout');

            // Dados + retirada + pagamento Pix
            $browser->type('input[name="nome_cliente"]', 'Cliente Teste Dusk')
                ->type('input[name="telefone"]', '(11) 99999-0000')
                ->radio('tipo_entrega', 'retirada')
                ->radio('forma_pagamento', 'pix')
                ->scrollIntoView('button[type="submit"]')
                ->press('Confirmar pedido')
                ->pause(1500)
                ->assertSee('Pedido confirmado!')
                ->assertSee('Açaí Dusk Complementos')
                ->assertSee('Cobertura de chocolate')
                ->assertSee('sem leite condensado')
                ->assertSee('R$ 24,00')
                ->assertSee('Retire na loja')
                ->screenshot('complementos_confirmacao');
        });
    }
}

<?php

namespace Tests\Browser;

use App\Models\Categoria;
use App\Models\Cupom;
use App\Models\Produto;
use App\Models\ProdutoEstoque;
use Illuminate\Support\Facades\DB;
use Tests\DuskTestCase;

class CupomTest extends DuskTestCase
{
    protected string $slug = 'acai-dusk-cupom';
    protected string $codigoCupom = 'DUSK10';

    protected function tearDown(): void
    {
        Cupom::where('codigo', $this->codigoCupom)->delete();
        \App\Models\PedidoItem::whereHas('pedido', fn ($q) => $q->whereNull('cliente_id'))
            ->where('nome_produto', 'Açaí Dusk Cupom')
            ->delete();
        \App\Models\Pedido::whereNull('cliente_id')
            ->where('nome_cliente', 'Cliente Teste Dusk Cupom')
            ->delete();
        Produto::where('slug', $this->slug)->delete();
        parent::tearDown();
    }

    protected function criarCenario(): Produto
    {
        Cupom::where('codigo', $this->codigoCupom)->delete();
        Cupom::create([
            'codigo' => $this->codigoCupom,
            'tipo' => 'percentual',
            'valor' => 10,
            'valor_minimo' => null,
            'limite_usos' => null,
            'usos' => 0,
            'ativo' => true,
            'inicio_em' => null,
            'fim_em' => null,
        ]);

        Produto::where('slug', $this->slug)->delete();

        $produto = Produto::create([
            'categoria_id' => Categoria::first()->id,
            'nome' => 'Açaí Dusk Cupom',
            'slug' => $this->slug,
            'descricao' => 'Produto de teste para cupom.',
            'preco' => 40.0,
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

        return $produto;
    }

    /**
     * Painel admin: a tela de cupons lista o cupom criado e aparece no menu.
     */
    public function test_admin_lista_cupons(): void
    {
        $this->criarCenario();

        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL(), 'Login falhou');

            $browser->visit('/admin/cupons')
                ->pause(2000)
                ->assertSee('DUSK10')
                ->assertVisible('.cartao-cupom')
                ->screenshot('admin_cupons');
        });
    }

    /**
     * Fluxo ponta a ponta: o cliente aplica o cupom no checkout, vê o
     * desconto no resumo e a confirmação exibe o valor abatido.
     */
    public function test_cliente_aplica_cupom_no_checkout(): void
    {
        $produto = $this->criarCenario();

        $this->browse(function ($browser) use ($produto) {
            $browser->visit('/cardapio')
                ->pause(1500)
                ->assertSee('Açaí Dusk Cupom')
                ->click("button[data-produto-id=\"{$produto->id}\"][data-adicionar-direto]")
                ->pause(1500);

            $browser->visit('/carrinho')
                ->pause(1000)
                ->assertSee('R$ 40,00');

            $browser->visit(route('checkout'))
                ->pause(1200)
                ->assertVisible('#form-checkout')
                ->assertSee('R$ 40,00');

            // Aplica o cupom pela interface (AJAX valida e abate 10%)
            $browser->type('#cupom-codigo', $this->codigoCupom)
                ->click('#botao-aplicar-cupom')
                ->pause(1200)
                ->assertVisible('#resumo-linha-desconto')
                ->assertSeeIn('#resumo-desconto', '- R$ 4,00')
                ->assertSeeIn('#resumo-total', 'R$ 36,00');

            // Confirma o pedido (retirada + pix, sem gateway/endereço)
            $browser->type('input[name="nome_cliente"]', 'Cliente Teste Dusk Cupom')
                ->type('input[name="telefone"]', '(11) 99999-1111')
                ->radio('tipo_entrega', 'retirada')
                ->radio('forma_pagamento', 'pix')
                ->scrollIntoView('button[type="submit"]')
                ->press('Confirmar pedido')
                ->pause(2000)
                ->assertSee('Pedido confirmado!')
                ->assertSee('Açaí Dusk Cupom')
                ->assertSee('- R$ 4,00')
                ->assertSee('DUSK10')
                ->screenshot('cupom_confirmacao');
        });
    }
}

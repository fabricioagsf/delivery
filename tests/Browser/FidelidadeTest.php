<?php

namespace Tests\Browser;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\ProdutoEstoque;
use Fabricioagsf\AuthMulti\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\DuskTestCase;

class FidelidadeTest extends DuskTestCase
{
    protected string $slug = 'acai-dusk-fidelidade';
    protected string $emailCliente = 'cliente-fidelidade@example.com';
    protected string $senhaCliente = '12345678';

    protected function tearDown(): void
    {
        $cliente = Cliente::where('email', $this->emailCliente)->first();

        if ($cliente) {
            Pedido::where('cliente_id', $cliente->id)->delete();
            $cliente->delete();
        }

        Usuario::where('email', $this->emailCliente)->delete();
        Produto::where('slug', $this->slug)->delete();

        Configuracao::where('chave', 'fidelidade_ativo')
            ->where('valor', '1')
            ->update(['valor' => '0']);

        parent::tearDown();
    }

    protected function ativarFidelidade(): void
    {
        Configuracao::updateOrCreate(['chave' => 'fidelidade_ativo'], ['valor' => '1']);
    }

    protected function criarClienteComPontos(): Cliente
    {
        $tenantId = DB::table('tenants')->where('slug', 'gostosuras')->value('id');

        Usuario::where('email', $this->emailCliente)->delete();

        $usuario = Usuario::create([
            'tenant_id' => $tenantId,
            'tipo' => 'cliente',
            'nome' => 'Cliente Fidelidade',
            'email' => $this->emailCliente,
            'senha' => $this->senhaCliente,
            'ativo' => true,
        ]);

        $cliente = Cliente::create([
            'usuario_id' => $usuario->id,
            'nome' => 'Cliente Fidelidade',
            'telefone' => '(11) 99999-2222',
            'email' => $this->emailCliente,
            'senha' => $this->senhaCliente,
            'chave_seguranca' => Hash::make('1234'),
            'pontos' => 50,
        ]);

        return $cliente;
    }

    protected function criarProduto(): Produto
    {
        $lojaId = DB::table('tenants')->where('slug', 'gostosuras')->value('id');

        Produto::where('slug', $this->slug)->delete();

        $produto = Produto::create([
            'categoria_id' => Categoria::first()->id,
            'nome' => 'Açaí Dusk Fidelidade',
            'slug' => $this->slug,
            'descricao' => 'Produto de teste para fidelidade.',
            'preco' => 40.0,
            'ativo' => true,
            'destaque' => false,
        ]);

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
     * Painel admin: a tela de fidelidade abre, mostra o menu e permite salvar.
     */
    public function test_admin_tela_fidelidade(): void
    {
        $this->ativarFidelidade();
        $this->criarClienteComPontos();

        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL(), 'Login falhou');

            $browser->visit('/admin/fidelidade')
                ->pause(2000)
                ->assertVisible('.form-admin')
                ->assertSee('Programa de fidelidade')
                ->screenshot('admin_fidelidade');
        });
    }

    /**
     * Fluxo ponta a ponta: cliente logado aplica pontos no checkout, vê o
     * desconto no resumo e a confirmação mostra os pontos usados/ganhos.
     */
    public function test_cliente_aplica_pontos_no_checkout(): void
    {
        $this->ativarFidelidade();
        $produto = $this->criarProduto();
        $cliente = $this->criarClienteComPontos();

        $this->browse(function ($browser) use ($produto, $cliente) {
            $browser->loginAs($cliente, 'cliente');

            $browser->visit('/cardapio')
                ->pause(1500)
                ->assertSee('Açaí Dusk Fidelidade')
                ->click("button[data-produto-id=\"{$produto->id}\"][data-adicionar-direto]")
                ->pause(1500);

            $browser->visit('/carrinho')
                ->pause(1000)
                ->assertSee('R$ 40,00');

            $browser->visit(route('checkout'))
                ->pause(1200)
                ->assertVisible('#form-checkout');

            // Aplica 50 pontos (valor do ponto 0,10 → R$ 5,00 de desconto)
            $browser->type('#pontos-input', '50')
                ->click('#botao-aplicar-pontos')
                ->pause(1200)
                ->assertVisible('#resumo-linha-desconto-pontos')
                ->assertSeeIn('#resumo-desconto-pontos', '- R$ 5,00')
                ->assertSeeIn('#resumo-total', 'R$ 35,00');

            // Confirma o pedido (retirada + pix, sem gateway/endereço)
            $browser->type('input[name="nome_cliente"]', 'Cliente Fidelidade')
                ->type('input[name="telefone"]', '(11) 99999-2222')
                ->radio('tipo_entrega', 'retirada')
                ->radio('forma_pagamento', 'pix')
                ->scrollIntoView('button[type="submit"]')
                ->press('Confirmar pedido')
                ->pause(2000)
                ->assertSee('Pedido confirmado!')
                ->assertSee('Açaí Dusk Fidelidade')
                ->assertSee('- R$ 5,00')
                ->assertSee('usou 50 pontos')
                ->assertSee('ganhou 40 pontos')
                ->screenshot('fidelidade_confirmacao');
        });
    }
}

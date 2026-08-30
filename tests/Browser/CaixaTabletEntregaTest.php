<?php

namespace Tests\Browser;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use Tests\DuskTestCase;

/**
 * Modal do caixa em duas colunas (pedidos | fechamento) com pílula de status,
 * badge de quantidade e total por pedido correto + tablet do garçom marcando
 * "Entregue na mesa".
 */
class CaixaTabletEntregaTest extends DuskTestCase
{
    protected int $mesaId = 0;
    protected int $pedidoEntregaId = 0;
    protected int $pedidoNovoId = 0;

    protected function criarCenario(): void
    {
        $lojaId = (int) loja_atual_id();
        $this->assertNotEquals(0, $lojaId, 'Não havia loja ativa para montar o cenário.');

        $codigo = 'L' . substr((string) now()->timestamp, -6);

        $mesa = Mesa::where('codigo', $codigo)->first();
        if (! $mesa) {
            $mesa = new Mesa([
                'nome' => 'Mesa Layout Dusk',
                'codigo' => $codigo,
                'capacidade' => 4,
                'ativo' => true,
            ]);
            $mesa->loja_id = $lojaId;
            $mesa->save();
        }
        $this->mesaId = (int) $mesa->id;

        $produto = Produto::where('nome', 'Brigadeiro')->first();
        $this->assertNotNull($produto, 'Produto Brigadeiro não encontrado (rode os seeders de catálogo).');

        // Pedido em entrega (com 2× do mesmo item) — pronto para marcar a entrega na mesa
        $pedido = Pedido::where('codigo', 'LX-' . $codigo)->first();
        if (! $pedido) {
            $pedido = Pedido::create([
                'loja_id' => $lojaId,
                'codigo' => 'LX-' . $codigo,
                'mesa_id' => $this->mesaId,
                'nome_cliente' => 'Cliente Layout Dusk',
                'telefone' => '(11) 97777-6666',
                'tipo_entrega' => 'mesa',
                'forma_pagamento' => 'pix',
                'subtotal' => 17.8,
                'total' => 17.8,
                'status' => 'em_entrega',
            ]);
            PedidoItem::create([
                'pedido_id' => $pedido->id,
                'produto_id' => $produto->id,
                'nome_produto' => $produto->nome,
                'preco_unitario' => $produto->preco,
                'complementos' => null,
                'quantidade' => 2,
            ]);
        }
        $this->pedidoEntregaId = (int) $pedido->id;

        // Pedido em "novo" para a mesa ter dois blocos na conta
        $pedidoNovo = Pedido::firstOrCreate(
            ['codigo' => 'LY-' . $codigo],
            [
                'loja_id' => $lojaId,
                'mesa_id' => $this->mesaId,
                'nome_cliente' => 'Cliente Layout Dusk',
                'telefone' => '(11) 97777-6666',
                'tipo_entrega' => 'mesa',
                'forma_pagamento' => 'pix',
                'subtotal' => 8.9,
                'total' => 8.9,
                'status' => 'novo',
            ]
        );
        $this->pedidoNovoId = (int) $pedidoNovo->id;

        if ($pedidoNovo->itens()->count() === 0) {
            PedidoItem::create([
                'pedido_id' => $pedidoNovo->id,
                'produto_id' => $produto->id,
                'nome_produto' => $produto->nome,
                'preco_unitario' => $produto->preco,
                'complementos' => null,
                'quantidade' => 1,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Pedido::where('mesa_id', $this->mesaId)->delete();
        if ($this->mesaId) {
            Mesa::where('id', $this->mesaId)->delete();
        }
        parent::tearDown();
    }

    public function test_caixa_mostra_duas_colunas_status_pilula_e_total_por_pedido(): void
    {
        $this->criarCenario();

        $this->browse(function ($browser) {
            $browser->resize(1600, 900)
                ->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            $browser->visit('/admin/caixa')
                ->pause(1500)
                ->waitForText('Mesa Layout Dusk', 20)
                ->screenshot('caixa_modal_grade');

            // Clicar na mesa abre o modal em 2 colunas: pedidos | fechamento
            $browser->script('document.querySelector("[data-mesa-id=\'' . $this->mesaId . '\']").click();');
            $browser->waitFor('.caixa-fechamento', 15)
                ->assertVisible('.caixa-pedidos')
                ->assertVisible('.caixa-fechamento')
                ->screenshot('caixa_modal_duas_colunas');

            // Pílulas de status dos dois pedidos abertos (CSS aplica uppercase no texto)
            $browser->assertSeeIn('.modal-mesa__status-pilula--novo', 'aguardando preparo', true)
                ->assertSeeIn('.modal-mesa__status-pilula--em-entrega', 'sendo entregue', true);

            // Badge de quantidade e total por pedido agora correto (não mais R$ 0,00)
            $browser->assertSeeIn('.modal-mesa__item-qtd', '2×')
                ->assertSee('Total: R$ 17,80')
                ->assertDontSeeIn('.caixa-pedidos', 'Total: R$ 0,00');

            // Total da conta e fechamento na lateral
            $browser->assertSeeIn('.caixa-fechamento', 'Total da conta')
                ->assertSeeIn('.caixa-fechamento', 'R$ 26,70')
                ->assertSeeIn('.caixa-fechamento', 'Fechar conta e registrar pagamento');
        });
    }

    public function test_garcom_marca_pedido_entregue_na_mesa_pelo_tablet(): void
    {
        $this->criarCenario();

        $this->browse(function ($browser) {
            $browser->resize(1600, 900)
                ->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            $browser->visit('/admin/mesa/' . $this->mesaId . '/pedido')
                ->pause(1500)
                ->waitFor('.mesa-pedido__abertos', 15);

            // Status das pílulas é uppercase via CSS → compara ignorando caixa
            $browser->assertSeeIn('.mesa-pedido__aberto-status--em-entrega', 'sendo entregue', true)
                ->assertSeeIn('.mesa-pedido__aberto-status--novo', 'aguardando preparo', true);

            // O botão aparece em qualquer pedido em aberto (novo/em_preparo/em_entrega)
            $qtdBotoes = $browser->script('return document.querySelectorAll("[data-entregar-pedido]").length;')[0];
            $this->assertSame(2, $qtdBotoes, 'Deveria haver um botão por pedido em aberto.');

            // O garçom confirma e marca como entregue na mesa (pedido em_entrega, o primeiro).
            // O confirm() trava o JS → aceita o dialog DEPOIS do clique.
            $browser->click('[data-entregar-pedido]')
                ->acceptDialog();
            $browser->waitFor('.mesa-pedido__aberto-entregue', 15)
                ->assertSeeIn('.mesa-pedido__aberto-entregue', 'Entregue às');

            // O pedido "novo" ainda tem o botão (só um restante)
            $qtdRestante = $browser->script('return document.querySelectorAll("[data-entregar-pedido]").length;')[0];
            $this->assertSame(1, $qtdRestante, 'O botão do pedido ainda "novo" deveria permanecer.');
            $browser->screenshot('tablet_entregue_marcado');
        });

        // Prova no banco: o flag entregue_mesa_em foi gravado e o status virou "entregue"
        $pedido = Pedido::find($this->pedidoEntregaId);
        $this->assertNotNull($pedido);
        $this->assertNotNull($pedido->entregue_mesa_em, 'entregue_mesa_em não foi gravado.');
        $this->assertSame('entregue', $pedido->status, 'O status deveria virar "entregue" ao marcar na mesa.');
    }

    public function test_mesas_controle_modal_marca_pedido_entregue_na_mesa(): void
    {
        $this->criarCenario();

        $this->browse(function ($browser) {
            $browser->resize(1600, 900)
                ->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            // Abre o Painel de mesas e o modal da mesa pelo card
            $browser->visit('/admin/mesas-controle')
                ->pause(1500)
                ->waitForText('Mesa Layout Dusk', 20);

            $browser->script('document.querySelector("[data-mesa-id=\'' . $this->mesaId . '\']").click();');

            // O botão "Entregue na mesa" existe em TODOS os pedidos em aberto
            // (novo, em_preparo e em_entrega), não só no em_entrega
            $browser->waitFor('.modal-mesa__acoes-botao--entregue-mesa', 15)
                ->screenshot('mesas_controle_modal_entregue');
            $qtdBotoes = $browser->script('return document.querySelectorAll(".modal-mesa__acoes-botao--entregue-mesa").length;')[0];
            $this->assertSame(2, $qtdBotoes, 'Deveria haver um botão por pedido em aberto no modal.');

            // Cenário real do usuário: o pedido está "novo" — o garçom confirma e
            // marca na mesma hora que a comida chegou à mesa (status vira "entregue").
            $browser->click('.modal-mesa__pedido[data-status="novo"] .modal-mesa__acoes-botao--entregue-mesa')
                ->acceptDialog();

            // O fetch do confirmarEntregaMesa atualiza o botão imediatamente:
            // remove is-loading, adiciona is-done e define texto "Entregue às HH:mm".
            // Isso acontece síncronamente após o acceptDialog(), sem depender do polling.
            $browser->waitFor('.modal-mesa__acoes-botao--entregue-mesa.is-done', 15)
                ->assertSeeIn('.modal-mesa__acoes-botao--entregue-mesa.is-done', 'Entregue às')
                ->screenshot('mesas_controle_modal_entregue_ok');
        });

        // Prova no banco: pedido "novo" virou "entregue" com o flag gravado
        // via modal mesas-controle (exatamente o cenário da sua tela)
        $pedido = Pedido::find($this->pedidoNovoId);
        $this->assertNotNull($pedido);
        $this->assertSame('entregue', $pedido->status);
        $this->assertNotNull($pedido->entregue_mesa_em, 'entregue_mesa_em não foi gravado pelo modal.');
    }
}
<?php

namespace Tests\Browser;

use App\Models\Configuracao;
use App\Models\Mesa;
use App\Models\Modulo;
use App\Models\Pedido;
use Tests\DuskTestCase;

/**
 * Módulos PDV e Delivery (modos de operação da loja: delivery, PDV ou ambos)
 * + painel de Módulos (ligado/desligado somente via banco, flag ativo 1/0).
 */
class ModulosCaixaTest extends DuskTestCase
{
    protected int $mesaId = 0;
    protected int $pedidoId = 0;

    protected function criarMesaEPedido(): void
    {
        $lojaId = (int) loja_atual_id();
        $this->assertNotEquals(0, $lojaId, 'Não havia loja ativa para montar o cenário.');

        $codigo = 'M' . substr((string) now()->timestamp, -7);

        $mesa = Mesa::where('codigo', $codigo)->first();
        if (! $mesa) {
            $mesa = new Mesa([
                'nome' => 'Mesa Caixa Dusk',
                'codigo' => $codigo,
                'capacidade' => 4,
                'ativo' => true,
            ]);
            $mesa->loja_id = $lojaId;
            $mesa->save();
        }

        $this->mesaId = (int) $mesa->id;

        $pedido = Pedido::where('codigo', 'PX-' . $codigo)->first();
        if (! $pedido) {
            $pedido = Pedido::create([
                'loja_id' => $lojaId,
                'codigo' => 'PX-' . $codigo,
                'mesa_id' => $this->mesaId,
                'nome_cliente' => 'Cliente Caixa Dusk',
                'telefone' => '(11) 98888-7777',
                'tipo_entrega' => 'mesa',
                'forma_pagamento' => 'pix',
                'subtotal' => 41.9,
                'total' => 41.9,
                'status' => 'novo',
            ]);

            // Cria o item do pedido para a prova de exibição do detalho
            $produtoTeste = \App\Models\Produto::where('nome', 'Brigadeiro')->first();
            if ($produtoTeste) {
                \App\Models\PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $produtoTeste->id,
                    'nome_produto' => $produtoTeste->nome,
                    'preco_unitario' => $produtoTeste->preco,
                    'complementos' => null,
                    'quantidade' => 1,
                ]);
            }
        }

        $this->pedidoId = (int) $pedido->id;
    }

    protected function tearDown(): void
    {
        // Os módulos sempre voltam ligados ao final, qualquer que seja o resultado.
        Modulo::where('slug', 'pdv')->whereNull('loja_id')->update(['ativo' => true]);
        Modulo::where('slug', 'delivery')->whereNull('loja_id')->update(['ativo' => true]);

        if ($this->pedidoId) {
            Pedido::where('id', $this->pedidoId)->delete();
        }
        if ($this->mesaId) {
            Mesa::where('id', $this->mesaId)->delete();
        }

        Configuracao::where('loja_id', loja_atual_id())
            ->whereIn('chave', ['chave_pix', 'empresa_razao_social', 'empresa_cidade', 'efi_taxa'])
            ->delete();

        parent::tearDown();
    }

    public function test_caixa_fecha_conta_de_mesa_e_marca_pago(): void
    {
        $this->criarMesaEPedido();

        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            // Módulo ligado: menu tem o grupo "PDV" (funções agrupadas)
            $browser->assertSee('PDV');
            $browser->assertSee('Caixa');

            // Grade de mesas: a mesa com pedido aberto mostra a conta
            $browser->visit('/admin/caixa')
                ->waitForText('Mesa Caixa Dusk', 20)
                ->waitForText('R$ 41,90');

            // Abre a conta da mesa
            $browser->script('document.querySelector("[data-mesa-id=\'' . $this->mesaId . '\']").click();');
            $browser->waitForText('Fechar conta e registrar pagamento', 15);

            // Fecha a conta em dinheiro (confirmação do JS aceita no teste)
            $browser->script('window.confirm = function(){ return true; };');
            $browser->select('.caixa-form select[name="forma_pagamento"]', 'dinheiro');
            $browser->script('var i = document.querySelector(".caixa-form input[name=\'troco_para\']"); i.value = "50"; i.dispatchEvent(new Event("input", { bubbles: true }));');
            $browser->pause(400);
            $browser->assertSelected('.caixa-form select[name="forma_pagamento"]', 'dinheiro');
            $browser->assertValue('.caixa-form input[name="troco_para"]', '50');
            $browser->assertVisible('.caixa-form input[name="troco_para"]')
                ->assertSee('Troco: R$ 8,10')
                ->click('.caixa-form button[type="submit"]');
            $browser->waitForText('Conta da mesa fechada!', 15)
                ->waitForText('Livre');

            $browser->screenshot('caixa_conta_fechada');
        });

        // Prova no banco: pedido entregue e pago, com a forma registrada
        $pedido = Pedido::find($this->pedidoId);
        $this->assertNotNull($pedido);
        $this->assertSame('entregue', $pedido->status);
        $this->assertSame('pago', $pedido->pagamento_status);
        $this->assertSame('dinheiro', $pedido->forma_pagamento);
        $this->assertSame('50.00', $pedido->troco_para);
    }

    public function test_caixa_exibe_qr_pix_por_chave_e_opcao_efi_com_taxa(): void
    {
        $lojaId = (int) loja_atual_id();
        $this->assertNotEquals(0, $lojaId, 'Não havia loja ativa para montar o cenário.');

        $configsPix = [
            'chave_pix' => '123e4567-e12b-12d1-a456-426655440000',
            'empresa_razao_social' => 'Gostosuras Doceria LTDA',
            'empresa_cidade' => 'Sao Paulo',
            'efi_taxa' => '0.99',
        ];
        foreach ($configsPix as $chave => $valor) {
            Configuracao::updateOrCreate(
                ['loja_id' => $lojaId, 'chave' => $chave],
                ['valor' => $valor]
            );
        }

        $this->criarMesaEPedido();

        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            $browser->visit('/admin/caixa')
                ->pause(2000)
                ->screenshot('caixa_pix_load')
                ->waitForText('Mesa Caixa Dusk', 20);

            $browser->script('document.querySelector("[data-mesa-id=\'' . $this->mesaId . '\']").click();');
            $browser->waitForText('Fechar conta e registrar pagamento', 15);

            // Escolhe Pix: as 2 opções (chave registrada / Efí) aparecem
            $browser->select('.caixa-form select[name="forma_pagamento"]', 'pix');
            $browser->waitForText('QR code por chave registrada')
                ->assertSee('QR code Pix automático (Efí)')
                ->assertSee('Sem taxa de operadora')
                ->assertSee('Pix copia e cola')
                ->assertVisible('.caixa-form__pix-qr');

            // Payload copia e cola no padrão BR Code (começa em 000201)
            $prefixo = $browser->script('return document.querySelector(".caixa-form__pix-input").value.substring(0, 6);')[0];
            $this->assertSame('000201', $prefixo);

            // Opção Efí: mostra a taxa da operadora e, sem credenciais, avisa
            $browser->script('document.querySelector(".caixa-form input[name=\'pix_opcao\'][value=\'efi\']").click();');
            $browser->waitForText('Pix automático (Efí) não ativado')
                ->assertSee('Taxa da operadora (Efí): 0,99%');

            $browser->screenshot('caixa_pix_qr_opcoes');
        });
    }

    public function test_painel_modulos_exibe_estado_e_ativa_desativa_so_pelo_banco(): void
    {
        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());
        });

        // Painel mostra o PDV como Ativo
        $this->browse(function ($browser) {
            $browser->visit('/admin/modulos')
                ->pause(1000)
                ->assertSee('Módulos do sistema')
                ->assertSee('PDV (mesas, tablet e caixa)')
                ->assertSeeIn('.cartao-cupom', 'Ativo');
        });

        // Desliga direto no banco (flag 0) — é assim que o módulo é controlado
        Modulo::where('slug', 'pdv')->whereNull('loja_id')->update(['ativo' => false]);

        $this->browse(function ($browser) {
            // Painel passa a mostrar o PDV como Inativo (card desligado)
            $browser->visit('/admin/modulos')
                ->pause(1000)
                ->assertSeeIn('.cartao-cupom--desligado', 'PDV (mesas, tablet e caixa)')
                ->assertSeeIn('.status-pilula--cancelado', 'Inativo');

            // Menu esconde o grupo PDV e a tela redireciona para o painel
            $browser->visit('/admin/painel')
                ->pause(1000)
                ->assertDontSee('PDV');

            $browser->visit('/admin/caixa')
                ->pause(2000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());
            $browser->screenshot('modulos_pdv_desativado');
        });

        // Liga de volta direto no banco (flag 1)
        Modulo::where('slug', 'pdv')->whereNull('loja_id')->update(['ativo' => true]);

        $this->browse(function ($browser) {
            $browser->visit('/admin/caixa')
                ->pause(1500)
                ->assertSee('Caixa (contas das mesas)')
                ->assertSee('PDV');
        });
    }

    public function test_delivery_desligado_bloqueia_vendas_online(): void
    {
        // Desliga o delivery direto no banco (flag 0) — é assim que o módulo é controlado
        Modulo::where('slug', 'delivery')->whereNull('loja_id')->update(['ativo' => false]);

        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            // Menu esconde o link de "Pedidos" do canal online; "Pedidos das mesas"
            // (grupo PDV) continua presente e a tela redireciona para o painel
            $browser->pause(1000)
                ->assertMissing('.lateral__menu a[href*="admin/pedidos"]')
                ->assertSeeLink('Pedidos das mesas');

            $browser->visit('/admin/pedidos')
                ->pause(2000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            // Loja pública não vende online: mostra o aviso
            $browser->visit('/')
                ->pause(1500)
                ->screenshot('delivery_desativado_vitrine')
                ->assertSee('Vendas online desativadas');
        });
    }

    public function test_garcom_cria_pedido_da_mesa_pelo_tablet(): void
    {
        $this->criarMesaEPedido();

        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            // Navega direto para o tablet de pedidos da mesa
            $browser->visit('/admin/mesa/' . $this->mesaId . '/pedido')
                ->pause(2000)
                ->screenshot('tablet_page_load')
                ->waitForText('Brigadeiro', 15)
                ->assertSee('R$ 8,90')
                ->assertSee('Sobremesas');

            // Adiciona o brigadeiro ao pedido (mira o card do Brigadeiro, não
            // o primeiro data-adicionar-direto, que pode ser um produto órfão)
            $browser->script('
                var btn = [].slice.call(document.querySelectorAll("[data-adicionar-direto]")).find(function (b) {
                    var card = b.closest(".cartao-produto-mesa");
                    return card && card.getAttribute("data-produto-nome") === "Brigadeiro";
                });
                if (btn) { btn.click(); window.__mesaClickOk = true; } else { window.__mesaClickOk = false; }
            ');
            $clickou = $browser->script('return window.__mesaClickOk;')[0];
            $this->assertTrue($clickou, 'Botão data-adicionar-direto não foi encontrado.');

            $browser->pause(1000)
                ->screenshot('tablet_apos_adicionar');

            // Diagnóstico primeiro
            $diag = $browser->script('
                return JSON.stringify({contador: document.getElementById("mesa-pedido-contador") && document.getElementById("mesa-pedido-contador").textContent, linhas: document.querySelectorAll(".mesa-pedido__linha").length, hasCart: !!document.getElementById("mesa-pedido-cart"), btnTexto: (document.querySelector("[data-adicionar-direto]")||{}).textContent, btnClasse: (document.querySelector("[data-adicionar-direto]")||{}).className});
            ')[0];
            file_put_contents('C:\Users\Admin\AppData\Local\Temp\opencode\diag_mesa.txt', $diag);

            $browser->assertSeeIn('#mesa-pedido-contador', '1');

            // Preenche nome do cliente
            $browser->type('input[name="nome_cliente"]', 'Cliente Tablet Dusk');

            // Envia o pedido
            $browser->click('#mesa-pedido-enviar');
            $browser->pause(1500)
                ->screenshot('garcom_pedido_enviado');
        });

        // Prova no banco: pedido criado para a mesa com item
        $pedido = Pedido::where('mesa_id', $this->mesaId)
            ->where('tipo_entrega', 'mesa')
            ->where('status', 'novo')
            ->where('nome_cliente', 'Cliente Tablet Dusk')
            ->latest('id')
            ->first();

        $this->assertNotNull($pedido, 'Pedido da mesa não foi encontrado no banco.');
        // A forma de pagamento NÃO é definida no pedido — a pessoa não fala como
        // vai pagar enquanto come; só o fechamento do caixa registra a forma.
        $this->assertNull($pedido->forma_pagamento, 'Pedido de mesa não pode nascer com forma de pagamento fixada.');
        $this->assertNotEmpty($pedido->codigo);
        $this->assertSame(8.90, (float) $pedido->total);

        $item = $pedido->itens()->first();
        $this->assertNotNull($item);
        $this->assertSame('Brigadeiro', $item->nome_produto);
        $this->assertSame(1, (int) $item->quantidade);
        $this->assertSame(8.90, (float) $item->preco_unitario);

        // Limpa pedido criado pelo teste
        $pedido->delete();
    }
}
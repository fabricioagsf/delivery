<?php

namespace Tests\Browser;

use Tests\DuskTestCase;

class ItemVendaTest extends DuskTestCase
{
    public function test_admin_configura_o_modulo_e_o_tipo(): void
    {
        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->assertSee('administrador')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('/admin/painel', $url, 'Login falhou');

            $browser->visit('/admin/item-venda')
                ->pause(3000)
                ->assertSee('Módulo de produtos e serviços')
                ->assertSee('O que o sistema vende')
                ->assertSee('Apenas produtos')
                ->screenshot('item_venda');

            $html = $browser->driver->getPageSource();
            $this->assertStringContainsString('item_venda_ativo', $html);
            $this->assertStringContainsString('item_venda_tipo', $html);
            $this->assertStringContainsString('Salvar módulo', $html);

            // Fluxo real: ligar o módulo, escolher o tipo, salvar e confirmar persistência
            $browser->check('item_venda_ativo')
                ->select('item_venda_tipo', 'ambos')
                ->press('Salvar módulo')
                ->pause(3000)
                ->assertSee('Módulo de produtos e serviços atualizado!')
                ->screenshot('item_venda_salvo');

            $browser->visit('/admin/item-venda')
                ->pause(2500)
                ->assertChecked('item_venda_ativo')
                ->assertSelected('item_venda_tipo', 'ambos')
                ->screenshot('item_venda_persistido');
        });
    }
}

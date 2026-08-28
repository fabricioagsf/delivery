<?php

namespace Tests\Browser;

use Tests\DuskTestCase;

class HelpTest extends DuskTestCase
{
    public function test_admin_acessa_a_ajuda_pelo_painel(): void
    {
        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->assertSee('administrador')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL(), 'Login falhou');

            $browser->visit('/admin/help')
                ->pause(2500)
                ->assertVisible('.help-doc');

            $html = $browser->driver->getPageSource();

            // Menu lateral tem o link "Ajuda" apontando para a rota
            $this->assertStringContainsString('/admin/help', $html, 'Link Ajuda não encontrado no menu');
            $this->assertStringContainsString('Ajuda', $html, 'Rótulo Ajuda não encontrado');

            // Conteúdo do HELP.md convertido (cabeçalhos e tabelas presentes)
            $this->assertStringContainsString('<h1', $html, 'Sem título principal convertido');
            $this->assertStringContainsString('<table', $html, 'Sem tabelas convertidas');
            $this->assertStringContainsString('<h2', $html, 'Sem seções convertidas');
            $this->assertStringContainsString('Personalizar complementos', $html, 'Conteúdo do HELP não renderizado');

            $browser->screenshot('admin_help');
        });
    }
}

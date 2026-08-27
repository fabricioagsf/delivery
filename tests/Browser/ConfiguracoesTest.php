<?php

namespace Tests\Browser;

use Tests\DuskTestCase;

class ConfiguracoesTest extends DuskTestCase
{
    public function test_admin_acessa_configuracoes(): void
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

            $browser->visit('/admin/configuracoes')
                ->pause(3000)
                ->screenshot('configuracoes_final');

            $html = $browser->driver->getPageSource();

            $this->assertStringContainsString('Login social', $html);
            $this->assertStringContainsString('Google', $html);
            $this->assertStringContainsString('Facebook', $html);
            $this->assertStringContainsString('Microsoft', $html);
            $this->assertStringContainsString('Instagram', $html);
            $this->assertStringContainsString('google_login_ativo', $html);
            $this->assertStringContainsString('facebook_login_ativo', $html);
            $this->assertStringContainsString('microsoft_login_ativo', $html);
            $this->assertStringContainsString('instagram_login_ativo', $html);
            $this->assertStringContainsString('Salvar', $html);
        });
    }
}

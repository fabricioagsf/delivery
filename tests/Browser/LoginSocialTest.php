<?php

namespace Tests\Browser;

use Tests\DuskTestCase;

class LoginSocialTest extends DuskTestCase
{
    public function test_botoes_sociais_aparecem_no_drawer(): void
    {
        $this->browse(function ($browser) {
            $browser->visit('/')
                ->pause(1000)
                ->screenshot('homepage_antes_drawer');

            $html = $browser->driver->getPageSource();
            file_put_contents('C:\delivery\debug_homepage.html', $html);

            $provedoresAtivos = \App\Services\LoginSocial::ativos();
            if (empty($provedoresAtivos)) {
                $this->markTestSkipped('Nenhum provedor de login social ativo no ambiente de teste.');
            }

            $this->assertStringContainsString('botao-social', $html, 'Botoes sociais nao encontrados no HTML da homepage');
        });
    }
}

<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Http;
use Tests\DuskTestCase;

class PwaTest extends DuskTestCase
{
    /**
     * PWA público: o cardápio expõe as metas/manifest/ícone do app e o botão
     * "Instalar" fica pronto, e o service worker entrega o cache offline.
     */
    public function test_cardapio_exposto_como_pwa(): void
    {
        $this->browse(function ($browser) {
            $browser->visit('/cardapio')
                ->pause(1000)
                ->assertSee('Cardápio');

            $html = $browser->driver->getPageSource();

            $this->assertStringContainsString('rel="manifest"', $html, 'Manifest não linkado');
            $this->assertStringContainsString('theme-color', $html, 'theme-color ausente');
            $this->assertStringContainsString('icons/icon.svg', $html, 'Ícone do PWA ausente');
            $this->assertStringContainsString('id="instalar-app"', $html, 'Botão instalar ausente');

            $browser->screenshot('cardapio_pwa');
        });

        // Service worker responde com o cache da versão configurada
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $resposta = $kernel->handle(
            \Illuminate\Http\Request::create('/sw.js', 'GET')
        );

        $this->assertSame(200, $resposta->getStatusCode(), 'Service worker não respondeu 200');
        $this->assertSame('application/javascript', $resposta->headers->get('Content-Type'));
        $this->assertStringContainsString('networkFirstPagina', $resposta->getContent(), 'Lógica offline ausente');
        $this->assertStringContainsString('var IMAGENS', $resposta->getContent(), 'Imagens do cardápio não pré-cacheadas');
        $kernel->terminate(new \Illuminate\Http\Request, $resposta);
    }

    /**
     * Módulo PWA no painel: tela acessível, com resumo do cache e botão salvar.
     */
    public function test_admin_acessa_modulo_pwa(): void
    {
        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->assertSee('administrador')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL(), 'Login falhou');

            $browser->visit('/admin/pwa')
                ->pause(2000)
                ->assertVisible('.form-admin')
                ->assertVisible('.lista-links-pwa');

            $htmlPage = $browser->driver->getPageSource();

            // Menu lateral aponta para o módulo PWA
            $this->assertStringContainsString('/admin/pwa', $htmlPage, 'Link do módulo PWA ausente no menu');
            $this->assertStringContainsString('Salvar app (PWA)', $htmlPage, 'Botão salvar não renderizado');

            $browser->screenshot('admin_pwa');
        });
    }
}

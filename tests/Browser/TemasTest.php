<?php

namespace Tests\Browser;

use App\Models\Configuracao;
use Tests\DuskTestCase;

class TemasTest extends DuskTestCase
{
    protected function tearDown(): void
    {
        Configuracao::updateOrCreate(
            ['chave' => 'tema_loja'],
            ['valor' => 'guloseimas', 'updated_at' => now()]
        );
        parent::tearDown();
    }

    /**
     * Tema padrão: a loja expõe a identidade "Guloseimas" (sem override de CSS).
     */
    public function test_tema_padrao_exibe_identidade_guloseimas(): void
    {
        $this->browse(function ($browser) {
            $browser->visit('/')
                ->pause(1000)
                ->assertSee('Guloseimas');

            $html = $browser->driver->getPageSource();
            $this->assertStringNotContainsString('css/themes/', $html, 'Tema padrão não deveria carregar override de CSS');

            $browser->screenshot('tema_guloseimas');
        });
    }

    /**
     * Troca de tema pelo admin: ao escolher "Italiana", a loja passa a usar a
     * identidade e a paleta italianas (e volta ao padrão ao final do teste).
     */
    public function test_admin_troca_tema_pela_configuracao(): void
    {
        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->assertSee('administrador')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL(), 'Login falhou');

            // Seleciona o tema italiano e salva
            $browser->visit('/admin/configuracoes')
                ->pause(2000)
                ->select('tema_loja', 'italiana')
                ->press('Salvar configurações')
                ->pause(2000)
                ->assertSee('Configurações salvas!');

            $browser->visit('/')
                ->pause(1000)
                ->assertSee('Trattoria Bella');

            $html = $browser->driver->getPageSource();
            $this->assertStringContainsString('css/themes/italiana.css', $html, 'CSS do tema italiano não carregado');

            $browser->screenshot('tema_italiana');
        });
    }
}

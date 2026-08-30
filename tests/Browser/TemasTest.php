<?php

namespace Tests\Browser;

use App\Models\Configuracao;
use Laravel\Dusk\Browser;
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
            Configuracao::updateOrCreate(
                ['chave' => 'tema_loja', 'loja_id' => (int) loja_atual_id()],
                ['valor' => 'guloseimas', 'updated_at' => now()]
            );
            \Illuminate\Support\Facades\Cache::flush();

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
                ->pause(2000)
                ->waitFor('#am-email', 15)
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(6000);

            $url = $browser->driver->getCurrentURL();
            $this->assertStringContainsString('/admin/painel', $url, 'Login falhou: ' . $url);

            $browser->visit('/admin/configuracoes')
                ->pause(2500)
                ->screenshot('tema_config_page');

            $html = $browser->driver->getPageSource();
            file_put_contents('C:\delivery\debug_config.html', $html);
            $this->assertStringContainsString('tema_loja', $html, 'Select tema_loja nao encontrado na pagina de configuracoes');

            $browser->select('tema_loja', 'italiana')
                ->press('Salvar configurações')
                ->pause(2500)
                ->assertSee('Configurações salvas!');

            $browser->visit('/')
                ->pause(1500)
                ->assertSee('Trattoria Bella');

            $html2 = $browser->driver->getPageSource();
            $this->assertStringContainsString('css/themes/italiana.css', $html2, 'CSS do tema italiano não carregado');

            $browser->screenshot('tema_italiana');
        });
    }
}

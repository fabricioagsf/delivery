<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TrocarLojaTest extends DuskTestCase
{
    /**
     * Troca de loja ativa pelo botão "Tornar ativa" da tela /admin/lojas.
     * Prova que POST /admin/lojas/trocar resolve para a rota correta
     * (e não é capturada pela rota parametrizada /lojas/{loja}).
     */
    public function test_troca_loja_ativa_pelo_botao_tornar_ativa(): void
    {
        $ids = collect(\Illuminate\Support\Facades\DB::table('tenants')->pluck('id', 'slug'));

        $this->assertTrue($ids->has('gostosuras'), 'Loja matriz gostosuras deve existir');
        $this->assertTrue($ids->has('loja-de-teste'), 'Loja "teste" (slug loja-de-teste) deve existir');

        $matrizId = (string) $ids->get('gostosuras');
        $filialId = (string) $ids->get('loja-de-teste');

        $this->browse(function (Browser $browser) use ($matrizId, $filialId) {
            $browser->visit('/admin')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(3000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            // Vai para a tela de lojas e clica em "Tornar ativa" na linha da filial
            $browser->visit('/admin/lojas')
                ->pause(1500)
                ->screenshot('trocar_loja_antes')
                ->click('form input[name="loja_id"][value="'.$filialId.'"] ~ button')
                ->pause(2500)
                ->screenshot('trocar_loja_depois_filial');

            $browser->assertSee('Você está na loja teste.')
                ->assertSelected('select[name="loja_id"]', $filialId)
                ->assertSeeIn('.lateral__marca', 'teste');

            // Volta para a matriz para não deixar o painel na loja de teste
            $browser->click('form input[name="loja_id"][value="'.$matrizId.'"] ~ button')
                ->pause(2500)
                ->assertSee('Você está na loja Gostosuras.')
                ->assertSelected('select[name="loja_id"]', $matrizId)
                ->assertSeeIn('.lateral__marca', 'Gostosuras')
                ->screenshot('trocar_loja_volta_matriz');
        });
    }
}
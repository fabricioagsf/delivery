<?php

namespace Tests\Browser;

use Fabricioagsf\AuthMulti\Models\Tenant as Loja;
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
        $filial = Loja::where('slug', 'loja-de-teste')->first();
        if (! $filial) {
            $filial = Loja::create([
                'nome' => 'teste',
                'slug' => 'loja-de-teste',
                'status' => 'ativo',
            ]);
        }

        $ids = collect(\Illuminate\Support\Facades\DB::table('tenants')->pluck('id', 'slug'));

        $this->assertTrue($ids->has('gostosuras'), 'Loja matriz gostosuras deve existir');
        $this->assertTrue($ids->has('loja-de-teste'), 'Loja "teste" (slug loja-de-teste) deve existir');

        $matrizId = (string) $ids->get('gostosuras');
        $filialId = (string) $ids->get('loja-de-teste');

        $this->browse(function (Browser $browser) use ($matrizId, $filialId) {
            $browser->visit('/admin')
                ->pause(2000)
                ->waitFor('#am-email', 15)
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(6000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL());

            $browser->visit('/admin/lojas')
                ->pause(2000)
                ->screenshot('trocar_loja_antes');

            $browser->script("
                var btns = document.querySelectorAll('button.mini-botao--salvar');
                for (var btn of btns) {
                    if (btn.textContent.indexOf('Tornar ativa') !== -1) {
                        var form = btn.closest('form');
                        if (form) {
                            var input = form.querySelector('input[name=\"loja_id\"]');
                            if (input && input.value === '$filialId') {
                                form.submit();
                                break;
                            }
                        }
                    }
                }
            ");
            $browser->pause(3000)
                ->screenshot('trocar_loja_depois_filial');

            $browser->assertSee('Você está na loja teste.')
                ->assertSeeIn('.lateral__marca', 'teste');

            $browser->visit('/admin/lojas')
                ->pause(2000);

            $browser->script("
                var btns = document.querySelectorAll('button.mini-botao--salvar');
                for (var btn of btns) {
                    if (btn.textContent.indexOf('Tornar ativa') !== -1) {
                        var form = btn.closest('form');
                        if (form) {
                            var input = form.querySelector('input[name=\"loja_id\"]');
                            if (input && input.value === '$matrizId') {
                                form.submit();
                                break;
                            }
                        }
                    }
                }
            ");
            $browser->pause(3000)
                ->assertSee('Você está na loja Gostosuras.')
                ->assertSeeIn('.lateral__marca', 'Gostosuras')
                ->screenshot('trocar_loja_volta_matriz');
        });

        Loja::where('slug', 'loja-de-teste')->delete();
    }
}

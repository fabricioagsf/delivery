<?php

namespace Tests\Browser;

use App\Models\Categoria;
use App\Models\Produto;
use Tests\DuskTestCase;

class ComplementosFormTest extends DuskTestCase
{
    protected string $slug = 'brigadeiro-dusk-form';

    protected function tearDown(): void
    {
        Produto::where('slug', $this->slug)->delete();
        parent::tearDown();
    }

    public function test_admin_adiciona_personalizacao_imediatamente_no_produto_novo(): void
    {
        Produto::where('slug', $this->slug)->delete();

        $this->browse(function ($browser) {
            $browser->visit('/admin')
                ->assertSee('administrador')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL(), 'Login falhou');

            $browser->visit('/admin/produtos/criar')
                ->pause(1500)
                ->assertVisible('#adicionar-complemento');

            $antes = count($browser->elements('.linha-complemento'));
            $this->assertGreaterThanOrEqual(1, $antes, 'Deve haver ao menos uma linha modelo no produto novo');

            // Clica em "Adicionar personalização" e verifica que a linha aparece
            // imediatamente, SEM sair/recarregar a página.
            $browser->click('#adicionar-complemento')
                ->pause(500);

            $depois = count($browser->elements('.linha-complemento'));
            $this->assertGreaterThan(
                $antes,
                $depois,
                'Clicar em adicionar personalização deve criar uma linha nova na hora'
            );
            $this->assertSame($antes + 1, $depois, 'Apenas uma linha deve ser adicionada por clique');
            $browser->screenshot('complementos_form_adicionado');

            // Preenche as duas linhas: adicional pago e remoção grátis
            $browser->type('complementos[0][nome]', 'Cobertura de chocolate')
                ->type('complementos[0][preco]', '2')
                ->select('complementos[0][tipo]', 'adicional');

            // Remove o preço da remoção fica desabilitado → sem preço
            $browser->type('complementos[1][nome]', 'sem granulado')
                ->select('complementos[1][tipo]', 'remocao');

            // Preenche o produto (campos obrigatórios) e salva
            $browser->type('input[name="nome"]', 'Brigadeiro Dusk Form')
                ->select('categoria_id', Categoria::first()->id)
                ->type('input[name="preco"]', '9')
                ->scrollIntoView('.botao--chefe')
                ->press('.botao--chefe')
                ->pause(2000)
                ->assertSee('Produto criado');

            $produto = Produto::where('slug', $this->slug)->first();
            $this->assertNotNull($produto, 'Produto não foi criado');

            $comp = $produto->complementos()->get();
            $this->assertCount(2, $comp, 'Os 2 complementos digitados devem ter sido salvos');

            $adicional = $comp->firstWhere('tipo', 'adicional');
            $this->assertSame('Cobertura de chocolate', $adicional->nome);
            $this->assertEquals(2.0, $adicional->preco);

            $remocao = $comp->firstWhere('tipo', 'remocao');
            $this->assertSame('sem granulado', $remocao->nome);
            $this->assertEquals(0.0, $remocao->preco, 'Remoção é sempre gratuita');
            $browser->screenshot('complementos_form_salvo');
        });
    }
}

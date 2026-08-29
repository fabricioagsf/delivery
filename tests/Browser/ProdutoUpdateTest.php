<?php

namespace Tests\Browser;

use App\Models\Categoria;
use App\Models\Produto;
use App\Models\ProdutoEstoque;
use Illuminate\Support\Facades\DB;
use Tests\DuskTestCase;

class ProdutoUpdateTest extends DuskTestCase
{
    protected string $slug = 'bombom-dusk-update';

    protected function tearDown(): void
    {
        Produto::where('slug', $this->slug)->delete();
        parent::tearDown();
    }

    public function test_admin_edita_e_salva_produto_sem_erro(): void
    {
        Produto::where('slug', $this->slug)->delete();
        $produto = Produto::create([
            'categoria_id' => Categoria::first()->id,
            'nome' => 'Bombom Dusk Update',
            'slug' => $this->slug,
            'descricao' => 'Produto criado para testar a edição.',
            'preco' => 5.0,
            'ativo' => true,
            'destaque' => false,
        ]);

        $lojaId = DB::table('tenants')->where('slug', 'gostosuras')->value('id');
        if ($lojaId) {
            ProdutoEstoque::create([
                'produto_id' => $produto->id,
                'loja_id' => $lojaId,
                'estoque' => 10,
                'estoque_minimo' => 2,
            ]);
        }

        $this->browse(function ($browser) use ($produto) {
            $browser->visit('/admin')
                ->assertSee('administrador')
                ->type('#am-email', 'admin@gostosuras.local')
                ->type('#am-senha', '12345678')
                ->click('.am-botao')
                ->pause(5000);

            $this->assertStringContainsString('/admin/painel', $browser->driver->getCurrentURL(), 'Login falhou');

            $browser->visit('/admin/produtos/'.$produto->id.'/editar')
                ->pause(1500)
                ->assertValue('input[name="nome"]', 'Bombom Dusk Update');

            // Muda o nome e o preço e SALVA (fluxo que estava dando TypeError)
            $browser->type('input[name="nome"]', 'Bombom Dusk Editado')
                ->type('input[name="preco"]', '6.5')
                ->scrollIntoView('.botao--chefe')
                ->press('.botao--chefe')
                ->pause(2000);

            $this->assertStringContainsString('/admin/produtos', $browser->driver->getCurrentURL(), 'Não voltou para a lista de produtos');

            $atualizado = Produto::find($produto->id);
            $this->assertNotNull($atualizado, 'Produto não encontrado após salvar');
            $this->assertSame('Bombom Dusk Editado', $atualizado->nome, 'Nome não foi salvo');
            $this->assertEquals(6.5, $atualizado->preco, 'Preço não foi salvo');
            $browser->screenshot('produto_update_salvo');
        });
    }
}

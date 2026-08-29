<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Produto;
use App\Models\ProdutoComplemento;
use App\Models\ProdutoEstoque;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Carga inicial do catálogo da loja matriz: 15 bebidas + 30 pratos
 * (primeira leva) + 50 pratos (segunda leva). Dos 50 pratos da segunda
 * leva, 20 têm ao menos 6 adicionais cada para personalização no pedido.
 *
 * Os produtos/categorias são globais (sem loja_id); o estoque é criado
 * por loja matriz (profile da loja activa/slug gostosuras). O seeder é
 * idempotente: itens já existentes pelo slug não são duplicados.
 */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $lojaId = DB::table('tenants')->where('slug', 'gostosuras')->value('id') ?? 1;

        $bebidas = Categoria::updateOrCreate(
            ['slug' => 'bebidas'],
            ['nome' => 'Bebidas', 'ativo' => true]
        );

        $pratos = Categoria::updateOrCreate(
            ['slug' => 'pratos'],
            ['nome' => 'Pratos', 'ativo' => true]
        );

        $countBebidas = 0;
        foreach ($this->bebidas() as $item) {
            $this->criarProduto($bebidas, $item, $lojaId);
            $countBebidas++;
        }

        $countPratos = 0;
        foreach ($this->pratosLeva1() as $item) {
            $this->criarProduto($pratos, $item, $lojaId);
            $countPratos++;
        }

        $countLeva2 = 0;
        $comAdicionais = 0;
        $adicionaisPool = $this->adicionaisPool();

        foreach ($this->pratosLeva2() as $indice => $item) {
            $produto = $this->criarProduto($pratos, $item, $lojaId);
            $countLeva2++;

            if ($indice < 20) {
                $this->criarAdicionais($produto, $adicionaisPool[$indice % count($adicionaisPool)]);
                $comAdicionais++;
            }
        }

        $this->command?->info(sprintf(
            'Catálogo: %d bebidas, %d pratos (leva 1) + %d pratos (leva 2, %d com 6+ adicionais).',
            $countBebidas,
            $countPratos,
            $countLeva2,
            $comAdicionais
        ));
    }

    private function criarProduto(Categoria $categoria, array $item, int $lojaId): ?Produto
    {
        $slug = $this->slugUnico($item['nome']);

        if (Produto::where('slug', $slug)->exists()) {
            return Produto::where('slug', $slug)->first();
        }

        $produto = Produto::create([
            'categoria_id' => $categoria->id,
            'nome' => $item['nome'],
            'slug' => $slug,
            'descricao' => $item['descricao'] ?? null,
            'preco' => round((float) $item['preco'], 2),
            'imagem' => null,
            'destaque' => $item['destaque'] ?? false,
            'ativo' => $item['ativo'] ?? true,
        ]);

        ProdutoEstoque::updateOrCreate(
            ['produto_id' => $produto->id, 'loja_id' => $lojaId],
            ['estoque' => $item['estoque'] ?? 40, 'estoque_minimo' => $item['estoque_minimo'] ?? 5]
        );

        return $produto;
    }

    private function criarAdicionais(Produto $produto, array $grupo): void
    {
        foreach ($grupo as $ordem => $adicional) {
            ProdutoComplemento::firstOrCreate(
                ['produto_id' => $produto->id, 'nome' => $adicional['nome']],
                [
                    'tipo' => 'adicional',
                    'preco' => round((float) $adicional['preco'], 2),
                    'ativo' => true,
                    'ordem' => $ordem,
                ]
            );
        }
    }

    private function slugUnico(string $nome): string
    {
        $base = Str::slug($nome);
        $slug = $base;
        $i = 2;

        while (Produto::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function bebidas(): array
    {
        return [
            ['nome' => 'Refrigerante Cola Lata 350 ml', 'descricao' => 'Lata gelada de cola.', 'preco' => 6.90, 'destaque' => true],
            ['nome' => 'Refrigerante Guaraná Lata 350 ml', 'descricao' => 'Lata gelada de guaraná.', 'preco' => 6.90],
            ['nome' => 'Refrigerante Laranja Lata 350 ml', 'descricao' => 'Lata gelada sabor laranja.', 'preco' => 6.90],
            ['nome' => 'Água Mineral sem Gás 500 ml', 'descricao' => 'Garrafinha sem gás.', 'preco' => 4.90],
            ['nome' => 'Água Mineral com Gás 500 ml', 'descricao' => 'Garrafinha com gás.', 'preco' => 5.50],
            ['nome' => 'Suco de Laranja 500 ml', 'descricao' => 'Suco de laranja espremido na hora.', 'preco' => 11.90],
            ['nome' => 'Suco de Uva Integral 500 ml', 'descricao' => 'Suco integral de uva.', 'preco' => 10.90],
            ['nome' => 'Limonada Suíça 500 ml', 'descricao' => 'Limonada com leve toque de leite condensado.', 'preco' => 12.90],
            ['nome' => 'Vitamina de Banana 500 ml', 'descricao' => 'Banana batida com leite.', 'preco' => 13.90],
            ['nome' => 'Milkshake de Chocolate 400 ml', 'descricao' => 'Milkshake cremoso de chocolate.', 'preco' => 19.90, 'destaque' => true],
            ['nome' => 'Milkshake de Morango 400 ml', 'descricao' => 'Milkshake cremoso de morango.', 'preco' => 19.90],
            ['nome' => 'Café Expresso', 'descricao' => 'Espresso puro e quentinho.', 'preco' => 5.90],
            ['nome' => 'Cappuccino Cremoso', 'descricao' => 'Cappuccino com canela.', 'preco' => 14.90],
            ['nome' => 'Chocolate Quente 400 ml', 'descricao' => 'Chocolate quente com marshmallow.', 'preco' => 17.90, 'destaque' => true],
            ['nome' => 'Chá Gelado de Pêssego 400 ml', 'descricao' => 'Chá gelado sabor pêssego.', 'preco' => 9.90],
        ];
    }

    private function pratosLeva1(): array
    {
        return [
            ['nome' => 'Strogonoff de Frango', 'descricao' => 'Acompanha arroz branco e batata palha.', 'preco' => 34.90, 'destaque' => true],
            ['nome' => 'Strogonoff de Carne', 'descricao' => 'Acompanha arroz branco e batata palha.', 'preco' => 38.90],
            ['nome' => 'Filé de Frango à Parmegiana', 'descricao' => 'Frango empanado, molho de tomate e queijo gratinado.', 'preco' => 36.90, 'destaque' => true],
            ['nome' => 'Filé Mignon à Parmegiana', 'descricao' => 'Filé mignon empanado com molho e queijo gratinado.', 'preco' => 52.90],
            ['nome' => 'Bife Acebolado', 'descricao' => 'Bife grelhado com cebola, arroz e feijão.', 'preco' => 32.90],
            ['nome' => 'Frango Grelhado com Legumes', 'descricao' => 'Filé de frango grelhado com legumes da casa.', 'preco' => 31.90],
            ['nome' => 'Picanha na Brasa', 'descricao' => 'Fatiada com arroz, vinagrete e farofa.', 'preco' => 59.90, 'destaque' => true],
            ['nome' => 'Tilápia Grelhada', 'descricao' => 'Tilápia grelhada com arroz e salada.', 'preco' => 39.90],
            ['nome' => 'Salmão Grelhado', 'descricao' => 'Posta de salmão com legumes.', 'preco' => 54.90],
            ['nome' => 'Feijoada Completa', 'descricao' => 'Feijoada com todas as carnes.', 'preco' => 42.90, 'destaque' => true],
            ['nome' => 'Moqueca de Peixe', 'descricao' => 'Peixe na moqueca com arroz e pirão.', 'preco' => 49.90],
            ['nome' => 'Baião de Dois com Carne de Sol', 'descricao' => 'Arroz com feijão, carne de sol e queijo.', 'preco' => 37.90],
            ['nome' => 'Escondidinho de Carne Seca', 'descricao' => 'Purê de mandioca com carne seca gratinada.', 'preco' => 35.90],
            ['nome' => 'Lasanha à Bolonhesa', 'descricao' => 'Massa com molho bolonhesa e queijo.', 'preco' => 38.90],
            ['nome' => 'Macarrão à Carbonara', 'descricao' => 'Espaguete ao molho carbonara.', 'preco' => 36.90],
            ['nome' => 'Penne ao Pesto', 'descricao' => 'Penne com molho pesto e tomate seco.', 'preco' => 33.90],
            ['nome' => 'Risoto de Camarão', 'descricao' => 'Risoto cremoso com camarões.', 'preco' => 58.90],
            ['nome' => 'Frango Assado com Batata', 'descricao' => 'Meio frango assado com batatas.', 'preco' => 33.90],
            ['nome' => 'Coxa de Frango ao Barbecue', 'descricao' => 'Coxas assadas com molho barbecue.', 'preco' => 30.90],
            ['nome' => 'Almôndegas ao Molho', 'descricao' => 'Almôndegas ao molho com purê.', 'preco' => 32.90],
            ['nome' => 'Carne Moída com Polenta', 'descricao' => 'Carne moída sobre polenta cremosa.', 'preco' => 29.90],
            ['nome' => 'Picanha ao Molho Madeira', 'descricao' => 'Picanha fatiada ao molho madeira.', 'preco' => 55.90],
            ['nome' => 'Filé Mignon ao Gorgonzola', 'descricao' => 'Filé mignon ao molho de gorgonzola.', 'preco' => 62.90],
            ['nome' => 'Costela ao Molho Barbecue', 'descricao' => 'Costela desfiada ao molho barbecue.', 'preco' => 48.90],
            ['nome' => 'Linguiça Acebolada', 'descricao' => 'Linguiça toscana com cebola.', 'preco' => 30.90],
            ['nome' => 'Calabresa Acebolada', 'descricao' => 'Calabresa com cebola e molho.', 'preco' => 29.90],
            ['nome' => 'Omelete de Queijo e Presunto', 'descricao' => 'Omelete recheado com salada.', 'preco' => 24.90],
            ['nome' => 'Ovos Mexidos com Torradas', 'descricao' => 'Ovos mexidos com torradas.', 'preco' => 19.90],
            ['nome' => 'Cuscuz com Ovo e Queijo', 'descricao' => 'Cuscuz nordestino com ovo e queijo.', 'preco' => 22.90],
            ['nome' => 'Tapioca de Queijo', 'descricao' => 'Tapioca recheada com queijo.', 'preco' => 21.90],
        ];
    }

    private function pratosLeva2(): array
    {
        return [
            ['nome' => 'Burger Clássico 180 g', 'descricao' => 'Pão brioche, hambúrguer, queijo e molho da casa.', 'preco' => 27.90, 'destaque' => true],
            ['nome' => 'Burger Bacon Duplo', 'descricao' => 'Dois burgers, bacon crocante e cheddar.', 'preco' => 34.90],
            ['nome' => 'Burger de Frango Empanado', 'descricao' => 'Frango empanado, queijo e salada.', 'preco' => 28.90],
            ['nome' => 'X-Burguer Completo', 'descricao' => 'Hambúrguer, ovo, queijo, presunto e salada.', 'preco' => 25.90],
            ['nome' => 'X-Salada', 'descricao' => 'Hambúrguer, queijo, alface e tomate.', 'preco' => 23.90],
            ['nome' => 'X-Bacon', 'descricao' => 'Hambúrguer, bacon, queijo e molho.', 'preco' => 26.90],
            ['nome' => 'X-Tudo', 'descricao' => 'Tudo que a casa tem em um só sanduíche.', 'preco' => 32.90],
            ['nome' => 'Hot Dog Especial', 'descricao' => 'Cachorro-quente completo com batata palha.', 'preco' => 18.90],
            ['nome' => 'Sanduíche de Frango Grelhado', 'descricao' => 'Frango grelhado, queijo e salada no pão francês.', 'preco' => 24.90],
            ['nome' => 'Sanduíche Natural', 'descricao' => 'Pão integral com frango desfiado e salada.', 'preco' => 19.90],
            ['nome' => 'Misto Quente Grelhado', 'descricao' => 'Presunto e queijo na chapa.', 'preco' => 16.90],
            ['nome' => 'Wrap de Frango', 'descricao' => 'Wrap com frango, queijo e molho especial.', 'preco' => 22.90],
            ['nome' => 'Wrap Vegetariano', 'descricao' => 'Wrap com legumes grelhados e cream cheese.', 'preco' => 21.90],
            ['nome' => 'Executivo de Frango', 'descricao' => 'Frango grelhado, arroz, feijão e salada.', 'preco' => 28.90],
            ['nome' => 'Executivo de Carne', 'descricao' => 'Bife grelhado, arroz, feijão e salada.', 'preco' => 30.90],
            ['nome' => 'Executivo de Peixe', 'descricao' => 'Peixe grelhado, arroz, feijão e salada.', 'preco' => 32.90],
            ['nome' => 'Salada Caesar com Frango', 'descricao' => 'Alface, frango grelhado, croutons e molho caesar.', 'preco' => 29.90],
            ['nome' => 'Salada Tropical', 'descricao' => 'Mix de folhas, frutas e castanhas.', 'preco' => 26.90],
            ['nome' => 'Prato Feito de Bife', 'descricao' => 'Bife, arroz, feijão, salada e fritas.', 'preco' => 24.90],
            ['nome' => 'Prato Feito de Frango', 'descricao' => 'Frango, arroz, feijão, salada e fritas.', 'preco' => 22.90],
            ['nome' => 'Pernil ao Forno', 'descricao' => 'Pernil assado com farofa.', 'preco' => 34.90],
            ['nome' => 'Frango Xadrez', 'descricao' => 'Frango, legumes e shoyu no wok.', 'preco' => 33.90],
            ['nome' => 'Espaguete ao Alho e Óleo', 'descricao' => 'Espaguete com alho e óleo.', 'preco' => 24.90],
            ['nome' => 'Galinhada', 'descricao' => 'Arroz de galinha com frango caipira.', 'preco' => 26.90],
            ['nome' => 'Rabada', 'descricao' => 'Rabada cozida com polenta.', 'preco' => 36.90],
            ['nome' => 'Bobó de Camarão', 'descricao' => 'Camarão com purê de mandioca.', 'preco' => 52.90],
            ['nome' => 'Vatapá com Arroz', 'descricao' => 'Vatapá baiano com arroz.', 'preco' => 28.90],
            ['nome' => 'Acarajé (2 un)', 'descricao' => 'Acarajé com vatapá e camarão.', 'preco' => 15.90],
            ['nome' => 'Peixada com Pirão', 'descricao' => 'Peixe cozido com pirão e arroz.', 'preco' => 38.90],
            ['nome' => 'Carpaccio de Carne', 'descricao' => 'Fatias finas de carne com rúcula e parmesão.', 'preco' => 32.90],
            ['nome' => 'Tartare de Salmão', 'descricao' => 'Salmão temperado com ervas e torradas.', 'preco' => 46.90],
            ['nome' => 'Bruschetta de Tomate', 'descricao' => 'Pão tostado com tomate e manjericão.', 'preco' => 18.90],
            ['nome' => 'Pão de Alho Recheado', 'descricao' => 'Pão de alho recheado com queijo.', 'preco' => 20.90],
            ['nome' => 'Portobello Grelhado', 'descricao' => 'Cogumelos portobello grelhados com alecrim.', 'preco' => 27.90],
            ['nome' => 'Camarão Empanado (10 un)', 'descricao' => 'Camarões empanados com molho tártaro.', 'preco' => 44.90],
            ['nome' => 'Coxinha de Frango (6 un)', 'descricao' => 'Coxinhas crocantes de frango.', 'preco' => 17.90],
            ['nome' => 'Quibe Frito (6 un)', 'descricao' => 'Quibes fritos com limão.', 'preco' => 15.90],
            ['nome' => 'Bolinho de Queijo (8 un)', 'descricao' => 'Bolinho de queijo sequinho.', 'preco' => 19.90],
            ['nome' => 'Pastel de Carne (4 un)', 'descricao' => 'Pastéis de carne fritos na hora.', 'preco' => 16.90],
            ['nome' => 'Pastel de Queijo (4 un)', 'descricao' => 'Pastéis de queijo fritos na hora.', 'preco' => 16.90],
            ['nome' => 'Pastel de Camarão (4 un)', 'descricao' => 'Pastéis de camarão fritos na hora.', 'preco' => 21.90],
            ['nome' => 'Croquete de Carne (6 un)', 'descricao' => 'Croquete de carne moída com catupiry.', 'preco' => 18.90],
            ['nome' => 'Torresmo de Borda', 'descricao' => 'Torresmo crocante com limão.', 'preco' => 19.90],
            ['nome' => 'Mandioca Frita', 'descricao' => 'Mandioca frita crocante.', 'preco' => 14.90],
            ['nome' => 'Batata Frita (porção)', 'descricao' => 'Batata frita sequinha.', 'preco' => 16.90, 'destaque' => true],
            ['nome' => 'Polenta Frita', 'descricao' => 'Palitos de polenta frita.', 'preco' => 15.90],
            ['nome' => 'Onion Rings', 'descricao' => 'Anéis de cebola empanados.', 'preco' => 13.90],
            ['nome' => 'Frango a Passarinho', 'descricao' => 'Frango frito com alho e limão.', 'preco' => 24.90],
            ['nome' => 'Isca de Peixe', 'descricao' => 'Iscas de peixe empanadas com molho.', 'preco' => 27.90],
            ['nome' => 'Vinagrete da Casa', 'descricao' => 'Vinagrete fresco acompanhamento.', 'preco' => 9.90],
        ];
    }

    /**
     * Grupos de 6 adicionais para os 20 pratos da 2ª leva que personalizam.
     */
    private function adicionaisPool(): array
    {
        return [
            [
                ['nome' => 'Bacon crocante', 'preco' => 7.90],
                ['nome' => 'Queijo cheddar extra', 'preco' => 6.90],
                ['nome' => 'Ovo frito', 'preco' => 4.50],
                ['nome' => 'Molho da casa', 'preco' => 3.50],
                ['nome' => 'Batata frita extra', 'preco' => 9.90],
                ['nome' => 'Salada fresca', 'preco' => 5.90],
            ],
            [
                ['nome' => 'Queijo mussarela extra', 'preco' => 6.50],
                ['nome' => 'Hambúrguer extra 90 g', 'preco' => 9.90],
                ['nome' => 'Molho barbecue extra', 'preco' => 3.90],
                ['nome' => 'Pão brioche extra', 'preco' => 6.90],
                ['nome' => 'Farofa crocante', 'preco' => 5.50],
                ['nome' => 'Alho tostado', 'preco' => 4.90],
            ],
            [
                ['nome' => 'Peito de frango extra', 'preco' => 8.90],
                ['nome' => 'Gorgonzola', 'preco' => 7.50],
                ['nome' => 'Cebola caramelizada', 'preco' => 5.90],
                ['nome' => 'Rúcula', 'preco' => 4.90],
                ['nome' => 'Tomate seco', 'preco' => 6.90],
                ['nome' => 'Molho pesto', 'preco' => 4.50],
            ],
            [
                ['nome' => 'Arroz branco extra', 'preco' => 8.90],
                ['nome' => 'Feijão tropeiro', 'preco' => 7.90],
                ['nome' => 'Vinagrete', 'preco' => 3.90],
                ['nome' => 'Ovo caipira', 'preco' => 4.90],
                ['nome' => 'Farofa de manteiga', 'preco' => 6.50],
                ['nome' => 'Legumes grelhados', 'preco' => 7.50],
            ],
            [
                ['nome' => 'Camarões extras', 'preco' => 12.90],
                ['nome' => 'Arroz integral', 'preco' => 7.90],
                ['nome' => 'Purê extra', 'preco' => 6.90],
                ['nome' => 'Salada verde', 'preco' => 5.50],
                ['nome' => 'Molho tártaro', 'preco' => 3.50],
                ['nome' => 'Limão extra', 'preco' => 1.50],
            ],
        ];
    }
}
@php
    $complementos = $produto->complementosAtivos;
    $temComplementos = $complementos->isNotEmpty();
    $estoqueLoja = $produto->estoqueNaLoja();
    $qtdEstoque = $estoqueLoja?->estoque;
@endphp
<article class="cartao-produto-mesa {{ $produto->esgotado() ? 'cartao-produto-mesa--esgotado' : '' }}"
         data-complementos="{{ json_encode($complementos->map(fn ($c) => [
             'id' => $c->id,
             'tipo' => $c->tipo,
             'nome' => $c->nome,
             'preco' => (float) $c->preco,
         ])->values()->all()) }}"
         data-produto-id="{{ $produto->id }}"
         data-produto-nome="{{ $produto->nome }}"
         data-produto-preco="{{ $produto->preco }}"
         data-produto-estoque="{{ $qtdEstoque ?? '' }}">
    <div class="cartao-produto-mesa__corpo">
        <span class="cartao-produto-mesa__categoria">{{ $produto->categoria?->nome }}</span>
        <h3 class="cartao-produto-mesa__nome">{{ $produto->nome }}</h3>
        @if($produto->descricao)
            <p class="cartao-produto-mesa__descricao">{{ $produto->descricao }}</p>
        @endif
        <div class="cartao-produto-mesa__pe">
            <strong class="cartao-produto-mesa__preco">{{ preco_br($produto->preco) }}</strong>
            @if($produto->esgotado())
                <span class="etiqueta-esgotado">{{ $produto->semQuantidade()
                    ? texto('vitrine', 'produto.indisponivel', 'Indisponível')
                    : texto('vitrine', 'produto.esgotado', 'Esgotado') }}</span>
            @else
                <button type="button"
                        class="botao botao--chefe botao--pequeno {{ $temComplementos ? 'botao-personalizar' : 'botao-adicionar' }}"
                        data-produto-id="{{ $produto->id }}"
                        @if($temComplementos)
                            data-modal-personalizar
                        @else
                            data-adicionar-direto
                        @endif
                >{{ $temComplementos
                        ? texto('admin_mesa_pedido', 'botao.personalizar', 'Personalizar')
                        : texto('admin_mesa_pedido', 'botao.adicionar', 'Adicionar') }}</button>
            @endif
        </div>
        @if($qtdEstoque !== null && $qtdEstoque > 0 && $qtdEstoque <= 3)
            <p class="aviso-estoque-baixo">{{ str_replace(':qtd', (string) $qtdEstoque, texto('vitrine', 'produto.ultimas', 'Últimas :qtd unidades!')) }}</p>
        @endif
    </div>
</article>
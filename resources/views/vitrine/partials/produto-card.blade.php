<article class="cartao-produto {{ $produto->esgotado() ? 'cartao-produto--esgotado' : '' }}">
    @if($produto->destaque)
        <span class="etiqueta-destaque">{{ texto('vitrine', 'produto.destaque', 'Destaque') }}</span>
    @endif

    <div class="cartao-produto__figura">
        @if($produto->imagem)
            <img src="{{ asset($produto->imagem) }}" alt="{{ $produto->nome }}" loading="lazy">
        @endif
    </div>

    <div class="cartao-produto__corpo">
        <span class="cartao-produto__categoria">{{ $produto->categoria?->nome }}</span>
        <h3 class="cartao-produto__nome">{{ $produto->nome }}</h3>
        @if($produto->descricao)
            <p class="cartao-produto__descricao">{{ $produto->descricao }}</p>
        @endif

        <div class="cartao-produto__pe">
            <strong class="cartao-produto__preco">{{ preco_br($produto->preco) }}</strong>

            @if($produto->esgotado())
                <span class="etiqueta-esgotado">{{ $produto->semQuantidade()
                    ? texto('vitrine', 'produto.indisponivel', 'Indisponível')
                    : texto('vitrine', 'produto.esgotado', 'Esgotado') }}</span>
            @else
                <button type="button"
                        class="botao botao--chefe botao-adicionar"
                        data-produto-id="{{ $produto->id }}"
                        @if($produto->estoque !== null && $produto->estoque <= 3)
                            title="{{ str_replace(':qtd', $produto->estoque, texto('vitrine', 'produto.ultimas', 'Últimas :qtd unidades!')) }}"
                        @endif
                >{{ texto('vitrine', 'botao.adicionar', 'Adicionar') }}</button>
            @endif
        </div>

        @if($produto->estoque !== null && $produto->estoque > 0 && $produto->estoque <= 3)
            <p class="aviso-estoque-baixo">{{ str_replace(':qtd', $produto->estoque, texto('vitrine', 'produto.ultimas', 'Últimas :qtd unidades!')) }}</p>
        @endif
    </div>
</article>

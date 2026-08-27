{{-- Ordem fixa da vitrine: 1) categorias, 2) destaques (só sem filtro), 3) produtos. --}}
@if($categorias->isNotEmpty())
<section class="secao">
    <h2 class="secao__titulo">{{ texto('vitrine', 'secao.categorias', 'Escolha por categoria') }}</h2>
    <div class="chips">
        <a href="{{ route('vitrine') }}" class="chip @if(!$categoriaAtiva) chip--ativa @endif">{{ texto('vitrine', 'filtro.todos', 'Tudo') }}</a>
        @foreach($categorias as $categoria)
            <a href="{{ route('vitrine', ['categoria' => $categoria->slug]) }}" class="chip @if($categoriaAtiva === $categoria->slug) chip--ativa @endif">
                {{ $categoria->nome }}
            </a>
        @endforeach
    </div>
</section>
@endif

@if(!$categoriaAtiva && $destaques->isNotEmpty())
<section class="secao">
    <h2 class="secao__titulo">{{ texto('vitrine', 'secao.destaque', 'Destaques da vitrine') }}</h2>
    <div class="grade">
        @foreach($destaques as $produto)
            @include('vitrine.partials.produto-card', ['produto' => $produto])
        @endforeach
    </div>
</section>
@endif

<section class="secao" id="produtos">
    <h2 class="secao__titulo">
        @if($categoriaAtiva)
            {{ $categorias->firstWhere('slug', $categoriaAtiva)?->nome ?? texto('vitrine', 'secao.produtos', 'Nossas gostosuras') }}
        @else
            {{ texto('vitrine', 'secao.produtos', 'Nossas gostosuras') }}
        @endif
    </h2>

    @if($produtos->isEmpty())
        <p class="vazio">{{ texto('vitrine', 'produtos.vazio', 'A vitrine está esvaziando... novidades saindo do tacho em breve!') }}</p>
    @else
        <div class="grade">
            @foreach($produtos as $produto)
                @include('vitrine.partials.produto-card', ['produto' => $produto])
            @endforeach
        </div>
    @endif
</section>

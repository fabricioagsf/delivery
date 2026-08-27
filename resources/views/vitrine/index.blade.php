@extends('layouts.loja')

@section('titulo', texto('vitrine', 'titulo.pagina', 'Gostosuras — Doces artesanais'))

@section('conteudo')
@if($banners->isNotEmpty())
<section class="carrossel" id="carrossel-banners" aria-label="{{ texto('vitrine', 'banner.rotulo', 'Promoções e novidades') }}">
    <div class="carrossel__slides">
        @foreach($banners as $indice => $banner)
            <div class="carrossel__slide {{ $indice === 0 ? 'ativo' : '' }}">
                @if($banner->link)
                    <a href="{{ $banner->link }}">
                        <img src="{{ asset($banner->imagem) }}" alt="{{ $banner->titulo ?? texto('vitrine', 'banner.rotulo', 'Promoção') }}" {{ $indice === 0 ? '' : 'loading="lazy"' }}>
                    </a>
                @else
                    <img src="{{ asset($banner->imagem) }}" alt="{{ $banner->titulo ?? texto('vitrine', 'banner.rotulo', 'Promoção') }}" {{ $indice === 0 ? '' : 'loading="lazy"' }}>
                @endif
            </div>
        @endforeach
    </div>
    @if($banners->count() > 1)
        <div class="carrossel__pontos">
            @foreach($banners as $indice => $banner)
                <button type="button" class="carrossel__ponto {{ $indice === 0 ? 'ativo' : '' }}" data-indice="{{ $indice }}"
                        aria-label="{{ texto('vitrine', 'banner.ir_para', 'Ir ao banner') }} {{ $indice + 1 }}"></button>
            @endforeach
        </div>
    @endif
</section>
@else
<section class="hero">
    <div class="hero__texto">
        <h1>{{ texto('vitrine', 'hero.titulo', 'Gostosuras feitas à mão') }}</h1>
        <p>{{ texto('vitrine', 'hero.subtitulo', 'Brigadeiros gourmet, chocolates cremosos e docinhos que derretem no coração. Peça online e receba em casa — ou retire na loja.') }}</p>
        <a href="#produtos" class="botao botao--chefe botao--grande">{{ texto('vitrine', 'hero.botao', 'Ver as gostosuras') }}</a>
    </div>
</section>
@endif

<div id="area-produtos">
    @include('vitrine.partials.resultados')
</div>

<section class="faixa-info">
    <article>
        <h3>{{ texto('vitrine', 'info.pagamento.titulo', 'Pague como preferir') }}</h3>
        <p>{{ texto('vitrine', 'info.pagamento.texto', 'Pix, cartão ou dinheiro. Online ou na entrega.') }}</p>
    </article>
    <article>
        <h3>{{ texto('vitrine', 'info.entrega.titulo', 'Receba em casa') }}</h3>
        <p>{{ str_replace(':taxa', preco_br((float) config_loja('taxa_entrega', '0')), texto('vitrine', 'info.entrega.texto', 'Entregamos no seu endereço. Taxa de entrega: :taxa.')) }}</p>
    </article>
    <article>
        <h3>{{ texto('vitrine', 'info.retirada.titulo', 'Retire na loja') }}</h3>
        <p>{{ texto('vitrine', 'info.retirada.texto', 'Peça pelo site e retire sem fila, no horário que combinar.') }}</p>
    </article>
</section>
@endsection

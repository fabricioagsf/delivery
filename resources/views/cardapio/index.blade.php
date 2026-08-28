@extends('layouts.loja')

@section('titulo', texto('cardapio', 'titulo.pagina', 'Cardápio — Gostosuras'))

@section('conteudo')
<section class="cardapio-cabecalho">
    <h1>{{ texto('cardapio', 'titulo', 'Cardápio') }}</h1>
    <p>{{ texto('cardapio', 'subtitulo', 'Escolha suas gostosuras e peça pelo site — receba em casa ou retire na loja.') }}</p>
    <p class="texto-suave">{{ str_replace(':n', $totalItens, texto('cardapio', 'contador', ':n gostosuras no cardápio')) }}</p>
</section>

@if($categorias->isEmpty())
    <p class="vazio">{{ texto('cardapio', 'vazio', 'O cardápio está sendo preparado... novidades saindo do tacho em breve!') }}</p>
@else
<nav class="cardapio-nav" aria-label="{{ texto('cardapio', 'nav.rotulo', 'Categorias do cardápio') }}">
    @foreach($categorias as $categoria)
        <a href="#cat-{{ $categoria->id }}" class="cardapio-nav__item">{{ $categoria->nome }}</a>
    @endforeach
</nav>

<div class="cardapio">
    @foreach($categorias as $categoria)
        <section class="cardapio-categoria" id="cat-{{ $categoria->id }}">
            <div class="cardapio-categoria__cabecalho">
                <h2>{{ $categoria->nome }}</h2>
                <span class="cardapio-categoria__contagem">{{ $categoria->produtos->count() }} {{ $categoria->produtos->count() === 1
                    ? texto('cardapio', 'item.singular', 'item')
                    : texto('cardapio', 'item.plural', 'itens') }}</span>
            </div>
            <div class="grade grade--cardapio">
                @foreach($categoria->produtos as $produto)
                    @include('vitrine.partials.produto-card', ['produto' => $produto])
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endif
@endsection

@extends('layouts.loja')

@section('titulo', $titulo)

@section('conteudo')
<div class="pagina-erro">
    <section class="cartao-erro">
        <span class="cartao-erro__codigo">{{ $codigo ?? 'OFF' }}</span>
        <h1>{{ $titulo }}</h1>
        <p>{{ $mensagem }}</p>
        @isset($acao)
            <a class="botao-primario" href="{{ $acao['url'] }}">{{ $acao['rotulo'] }}</a>
        @endisset
    </section>
</div>
@endsection
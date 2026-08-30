@extends('layouts.saas')

@section('titulo', 'Filiais')
@section('titulo_pagina', 'Filiais')
@section('acoes')
    <a href="{{ route('saas.filiais.create') }}" class="botao botao--chefe">Nova filial</a>
@endsection

@section('conteudo')
<section class="painel-admin">
    @if(session('sucesso'))
        <div class="alerta alerta--sucesso">{{ session('sucesso') }}</div>
    @endif
    @forelse($filiais as $filial)
        <article class="cartao-cupom {{ !$filial->ativo ? 'cartao-cupom--desligado' : '' }}">
            <div class="cartao-cupom__info">
                <strong>{{ $filial->nome }}</strong>
                <small>{{ $filial->slug }} {{ $filial->dominio ? '· '.$filial->dominio : '' }}</small>
                <span class="status-pilula {{ $filial->ativo ? 'status-pilula--entregue' : '' }}">
                    {{ $filial->ativo ? 'Ativa' : 'Inativa' }}
                </span>
            </div>
            <div class="cartao-cupom__acoes">
                <a href="{{ route('saas.filiais.edit', $filial) }}" class="mini-botao">Editar</a>
                <form method="POST" action="{{ route('saas.filiais.destroy', $filial) }}" style="display:inline" onsubmit="return confirm('Remover filial?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="mini-botao mini-botao--excluir">Remover</button>
                </form>
            </div>
        </article>
    @empty
        <p class="texto-suave">Nenhuma filial cadastrada.</p>
    @endforelse
</section>
@endsection

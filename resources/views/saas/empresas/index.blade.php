@extends('layouts.saas')

@section('titulo', 'Empresas')
@section('titulo_pagina', 'Empresas')
@section('acoes')
    <a href="{{ route('saas.empresas.create') }}" class="botao botao--chefe">Nova empresa</a>
@endsection

@section('conteudo')
<section class="painel-admin">
    @if(session('sucesso'))
        <div class="alerta alerta--sucesso">{{ session('sucesso') }}</div>
    @endif
    @forelse($empresas as $empresa)
        <article class="cartao-cupom {{ !$empresa->ativo ? 'cartao-cupom--desligado' : '' }}">
            <div class="cartao-cupom__info">
                <strong>{{ $empresa->nome }}</strong>
                <small>{{ $empresa->slug }} {{ $empresa->cnpj ? '· CNPJ: '.$empresa->cnpj : '' }}</small>
                <span class="status-pilula {{ $empresa->ativo ? 'status-pilula--entregue' : '' }}">
                    {{ $empresa->ativo ? 'Ativa' : 'Inativa' }}
                </span>
                <small>{{ $empresa->filiais_count }} filiais · {{ $empresa->employees_count }} funcionários</small>
            </div>
            <div class="cartao-cupom__acoes">
                <a href="{{ route('saas.empresas.config', $empresa) }}" class="mini-botao">Configurações</a>
                <a href="{{ route('saas.comissoes.index', $empresa) }}" class="mini-botao">Comissões</a>
                <a href="{{ route('saas.empresas.edit', $empresa) }}" class="mini-botao">Editar</a>
                <form method="POST" action="{{ route('saas.empresas.destroy', $empresa) }}" onsubmit="return confirm('Remover empresa?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="mini-botao mini-botao--excluir">Remover</button>
                </form>
            </div>
        </article>
    @empty
        <p class="texto-suave">Nenhuma empresa cadastrada.</p>
    @endforelse
</section>
@endsection

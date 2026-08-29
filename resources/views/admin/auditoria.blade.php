@extends('layouts.admin')

@section('titulo', texto('admin_auditoria', 'titulo', 'Auditoria — Gostosuras'))
@section('titulo_pagina', texto('admin_auditoria', 'titulo.pagina', 'Auditoria do banco'))

@section('conteudo')
@if(session('sucesso_auditoria'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_auditoria') }}</div>
@endif

<form method="GET" class="filtros">
    <input type="text" name="registro" value="{{ $filtros['registro'] ?? '' }}" placeholder="{{ texto('admin_auditoria', 'filtro.registro', 'Id do registro ou autor...') }}">
    <select name="tabela" class="seletor-status">
        <option value="">{{ texto('admin_auditoria', 'filtro.todas_tabelas', 'Todas as tabelas') }}</option>
        @foreach($tabelas as $tabela)
            <option value="{{ $tabela }}" @if(($filtros['tabela'] ?? '') === $tabela) selected @endif>{{ $tabela }}</option>
        @endforeach
    </select>
    <select name="acao" class="seletor-status">
        <option value="">{{ texto('admin_auditoria', 'filtro.todas_acoes', 'Todas as ações') }}</option>
        <option value="INSERT" @if(($filtros['acao'] ?? '') === 'INSERT') selected @endif>{{ texto('admin_auditoria', 'acao.insert', 'Criação') }}</option>
        <option value="UPDATE" @if(($filtros['acao'] ?? '') === 'UPDATE') selected @endif>{{ texto('admin_auditoria', 'acao.update', 'Alteração') }}</option>
        <option value="DELETE" @if(($filtros['acao'] ?? '') === 'DELETE') selected @endif>{{ texto('admin_auditoria', 'acao.delete', 'Exclusão') }}</option>
    </select>
    <select name="origem" class="seletor-status">
        <option value="">{{ texto('admin_auditoria', 'filtro.todas_origens', 'Todas as origens') }}</option>
        <option value="gatilho" @if(($filtros['origem'] ?? '') === 'gatilho') selected @endif>{{ texto('admin_auditoria', 'origem.gatilho', 'Banco (gatilho)') }}</option>
        <option value="aplicacao" @if(($filtros['origem'] ?? '') === 'aplicacao') selected @endif>{{ texto('admin_auditoria', 'origem.aplicacao', 'Aplicação') }}</option>
    </select>
    <button type="submit" class="botao">{{ texto('admin_produtos', 'botao.buscar', 'Buscar') }}</button>
</form>

<section class="painel-admin">
    <p class="nota-segura nota-segura--admin">{{ texto('admin_auditoria', 'nota.imutavel', 'Histórico imutável de tudo que é criado, alterado e excluído — inclusive edições feitas direto no banco. Para voltar um estado, abra o evento e use a restauração com a senha master.') }}</p>

    <div class="tabela-rolagem">
        <table class="tabela">
        <thead>
        <tr>
            <th>#</th>
            <th>{{ texto('admin_pedidos', 'tabela.quando', 'Quando') }}</th>
            <th>{{ texto('admin_auditoria', 'coluna.frase', 'O que aconteceu') }}</th>
            <th>{{ texto('admin_auditoria', 'coluna.origem', 'Origem') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($eventos as $evento)
            <tr>
                <td><strong>{{ $evento->id }}</strong></td>
                <td>{{ $evento->criado_em?->format('d/m/Y H:i:s') }}</td>
                <td><a href="{{ route('admin.auditoria.show', $evento) }}">{{ \App\Support\AuditoriaFormatador::frase($evento) }}</a></td>
                <td>{{ texto('admin_auditoria', 'origem.' . $evento->origem, ucfirst($evento->origem)) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="texto-suave">{{ texto('admin_auditoria', 'lista.vazia', 'Nenhum evento registrado ainda.') }}</td></tr>
        @endforelse
        </tbody>
        </table>
    </div>

    {{ $eventos->links('vendor.pagination.padrao') }}
</section>
@endsection

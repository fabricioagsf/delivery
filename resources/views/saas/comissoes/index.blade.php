@extends('layouts.saas')

@section('titulo', 'Comissões — ' . $empresa->nome)
@section('titulo_pagina', 'Relatório de Comissões')
@section('acoes')
    <a href="{{ route('saas.empresas.index') }}" class="botao">Voltar</a>
@endsection

@section('conteudo')
<form method="GET" class="filtros">
    <label>De
        <input type="date" name="inicio" value="{{ $inicio }}">
    </label>
    <label>Até
        <input type="date" name="fim" value="{{ $fim }}">
    </label>
    <label>Funcionário
        <select name="employee_id">
            <option value="">Todos</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
            @endforeach
        </select>
    </label>
    <button type="submit" class="botao">Filtrar</button>
</form>

<div class="duas-colunas">
    <section class="painel-admin">
        <h2>Resumo</h2>
        <div class="estatistica">
            <span class="estatistica__numero">{{ $totalPedidos }}</span>
            <span class="estatistica__rotulo">pedidos</span>
        </div>
        <div class="estatistica">
            <span class="estatistica__numero">R$ {{ number_format($totalGeral, 2, ',', '.') }}</span>
            <span class="estatistica__rotulo">comissão total</span>
        </div>
    </section>
</div>

<section class="painel-admin">
    <h2>Por funcionário</h2>
    @forelse($porEmployee as $item)
        <article class="cartao-cupom">
            <div class="cartao-cupom__info">
                <strong>{{ $item['employee']->name }}</strong>
                <small>{{ $item['employee']->roles->pluck('nome')->join(', ') ?: '—' }}</small>
                <span>{{ $item['pedidos'] }} pedidos</span>
            </div>
            <div class="cartao-cupom__acoes">
                <strong>R$ {{ number_format($item['total'], 2, ',', '.') }}</strong>
            </div>
        </article>
    @empty
        <p class="texto-suave">Nenhum registro no período.</p>
    @endforelse
</section>

@if(!$employeeId)
<section class="painel-admin">
    <h2>Detalhe por pedido</h2>
    <div class="tabela-rolagem">
    <table class="tabela">
        <thead>
        <tr>
            <th>Pedido</th>
            <th>Funcionário</th>
            <th>Data</th>
            <th>Comissão</th>
        </tr>
        </thead>
        <tbody>
        @forelse($registros as $reg)
            <tr>
                <td><a href="/admin/pedidos/{{ $reg->pedido_id }}">{{ $reg->pedido?->codigo ?? '#'.$reg->pedido_id }}</a></td>
                <td>{{ $reg->employee?->name ?? '—' }}</td>
                <td>{{ $reg->registrado_em?->format('d/m/Y H:i') }}</td>
                <td>R$ {{ number_format($reg->comissao_valor, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="texto-suave">Nenhum registro.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</section>
@endif
@endsection

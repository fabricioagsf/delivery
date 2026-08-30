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

<section class="painel-admin">
    <h2>Resumo geral</h2>
    <div class="estatisticas-linha">
        <div class="estatistica">
            <span class="estatistica__numero">{{ $porFuncionario->count() }}</span>
            <span class="estatistica__rotulo">funcionários</span>
        </div>
        <div class="estatistica">
            <span class="estatistica__numero">R$ {{ number_format($totalVendidoGeral, 2, ',', '.') }}</span>
            <span class="estatistica__rotulo">vendas no período</span>
        </div>
        <div class="estatistica">
            <span class="estatistica__numero">{{ $comissaoPercentual }}%</span>
            <span class="estatistica__rotulo">taxa de comissão</span>
        </div>
        <div class="estatistica">
            <span class="estatistica__numero">R$ {{ number_format($totalComissaoGeral, 2, ',', '.') }}</span>
            <span class="estatistica__rotulo">comissão total</span>
        </div>
    </div>
</section>

<section class="painel-admin">
    <h2>Por funcionário</h2>
    @forelse($porFuncionario as $item)
        <article class="cartao-cupom">
            <div class="cartao-cupom__info">
                <strong>{{ $item['employee']->name }}</strong>
                <span>{{ $item['pedidos_count'] }} pedidos</span>
            </div>
            <div class="cartao-cupom__valores">
                <div>
                    <small>Vendas</small>
                    <strong>R$ {{ number_format($item['total_vendido'], 2, ',', '.') }}</strong>
                </div>
                <div>
                    <small>Comissão</small>
                    <strong class="cor-destaque">R$ {{ number_format($item['comissao'], 2, ',', '.') }}</strong>
                </div>
            </div>
        </article>
    @empty
        <p class="texto-suave">Nenhum pedido com funcionário registrado no período.</p>
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
            <th>Valor</th>
            <th>Comissão</th>
        </tr>
        </thead>
        <tbody>
        @forelse($registros as $reg)
            <tr>
                <td><a href="/admin/pedidos/{{ $reg->pedido_id }}">{{ $reg->pedido?->codigo ?? '#'.$reg->pedido_id }}</a></td>
                <td>{{ $reg->employee?->name ?? '—' }}</td>
                <td>{{ $reg->registrado_em?->format('d/m/Y H:i') }}</td>
                <td>R$ {{ number_format($reg->pedido?->total ?? 0, 2, ',', '.') }}</td>
                <td>R$ {{ number_format($reg->comissao_valor, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="texto-suave">Nenhum registro.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</section>
@endif

<style>
.cor-destaque { color: var(--cor-sucesso, #16a34a); }
.estatisticas-linha { display:flex; gap:24px; flex-wrap:wrap; }
.cartao-cupom__valores { display:flex; gap:24px; text-align:right; }
.cartao-cupom__valores small { display:block; font-size:.75rem; color:var(--cor-texto-suave); }
</style>
@endsection

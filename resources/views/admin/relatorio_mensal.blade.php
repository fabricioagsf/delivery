@extends('layouts.admin')

@section('titulo', texto('admin_mensal', 'titulo', 'Relatório mensal — Gostosuras'))
@section('titulo_pagina', texto('admin_mensal', 'titulo.pagina', 'Relatório mensal'))

@section('conteudo')
<div class="barra-mensal sem-impressao">
    <a class="mini-botao" href="{{ route('admin.relatorios.mensal', ['mes' => $mesAnterior->month, 'ano' => $mesAnterior->year]) }}">« {{ $mesAnterior->translatedFormat('F/Y') }}</a>
    <strong class="mes-atual">{{ ucfirst($nomeMes) }} / {{ $ano }}</strong>
    <a class="mini-botao" href="{{ route('admin.relatorios.mensal', ['mes' => $mesSeguinte->month, 'ano' => $mesSeguinte->year]) }}">{{ $mesSeguinte->translatedFormat('F/Y') }} »</a>

    <form method="GET" class="form-inline">
        <select name="mes" class="seletor-status">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $m === $mes ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
            @endfor
        </select>
        <select name="ano" class="seletor-status">
            @for($a = now()->year; $a >= now()->year - 5; $a--)
                <option value="{{ $a }}" {{ $a === $ano ? 'selected' : '' }}>{{ $a }}</option>
            @endfor
        </select>
        <button type="submit" class="mini-botao mini-botao--salvar">{{ texto('admin_mensal', 'botao.ir', 'Ir') }}</button>
    </form>

    <button type="button" class="botao botao--chefe" onclick="window.print()">
        {{ texto('admin_mensal', 'botao.pdf', 'Exportar PDF') }}
    </button>
</div>

<div class="extrato">
    <header class="extrato__cabecalho">
        <div>
            <h1>{{ texto('layout', 'site.nome', 'Gostosuras') }}</h1>
            <p>{{ texto('admin_mensal', 'subtitulo', 'Relatório mensal de vendas e produtos') }}</p>
        </div>
        <div class="extrato__periodo">
            {{ ucfirst($nomeMes) }} / {{ $ano }}
        </div>
    </header>

    <div class="extrato__resumo">
        <div><small>{{ texto('admin_mensal', 'resumo.faturamento', 'Faturamento do mês') }}</small><strong>{{ preco_br($resumo['faturamento']) }}</strong></div>
        <div><small>{{ texto('admin_relatorios', 'vendas.pedidos', 'Pedidos') }}</small><strong>{{ $resumo['pedidos'] }}</strong></div>
        <div><small>{{ texto('admin_relatorios', 'vendas.ticket', 'Ticket médio') }}</small><strong>{{ preco_br($resumo['ticketMedio']) }}</strong></div>
        <div><small>{{ texto('admin_relatorios', 'vendas.taxas', 'Taxas de entrega') }}</small><strong>{{ preco_br($resumo['taxasEntrega']) }}</strong></div>
    </div>

    <h2>{{ texto('admin_mensal', 'secao.vendas', 'Vendas dia a dia') }}</h2>
    <table class="tabela extrato__tabela">
        <thead>
        <tr>
            <th>{{ texto('admin_mensal', 'coluna.dia', 'Dia') }}</th>
            <th>{{ texto('admin_relatorios', 'vendas.pedidos', 'Pedidos') }}</th>
            <th>{{ texto('admin_relatorios', 'produtos.faturamento', 'Faturamento') }}</th>
            <th>{{ texto('admin_mensal', 'coluna.acumulado', 'Acumulado do mês') }}</th>
        </tr>
        </thead>
        <tbody>
        @php
            $acumulado = 0;
        @endphp
        @forelse($porDia as $linha)
            @php
                $acumulado += $linha->faturamento;
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($linha->dia)->translatedFormat('d/m — l') }}</td>
                <td>{{ $linha->pedidos }}</td>
                <td>{{ preco_br($linha->faturamento) }}</td>
                <td><strong>{{ preco_br($acumulado) }}</strong></td>
            </tr>
        @empty
            <tr><td colspan="4" class="texto-suave">{{ texto('admin_relatorios', 'produtos.vazio', 'Nenhuma venda registrada neste período.') }}</td></tr>
        @endforelse
        @if($porDia->isNotEmpty())
            <tr class="linha-total">
                <td>{{ texto('admin_mensal', 'coluna.total', 'TOTAL DO MÊS') }}</td>
                <td>{{ $resumo['pedidos'] }}</td>
                <td colspan="2"><strong>{{ preco_br($resumo['faturamento']) }}</strong></td>
            </tr>
        @endif
        </tbody>
    </table>

    <h2>{{ texto('admin_mensal', 'secao.produtos', 'Produtos vendidos no mês') }}</h2>
    <table class="tabela extrato__tabela">
        <thead>
        <tr>
            <th>#</th>
            <th>{{ texto('admin_produtos', 'tabela.produto', 'Produto') }}</th>
            <th>{{ texto('admin_relatorios', 'produtos.quantidade', 'Qtd vendida') }}</th>
            <th>{{ texto('admin_relatorios', 'produtos.faturamento', 'Faturamento') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($produtos as $indice => $produto)
            <tr>
                <td>{{ $indice + 1 }}</td>
                <td>{{ $produto->nome_produto }}</td>
                <td>{{ $produto->quantidade_vendida }}</td>
                <td>{{ preco_br($produto->faturamento) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="texto-suave">{{ texto('admin_relatorios', 'produtos.vazio', 'Nenhuma venda registrada neste período.') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    <footer class="extrato__pe">
        {{ texto('admin_mensal', 'rodape', 'Documento gerado pelo painel Gostosuras — valores consideram pedidos não cancelados.') }}
        {{ now()->translatedFormat('d/m/Y H:i') }}
    </footer>
</div>
@endsection

@push('scripts')
    <style>
        .barra-mensal { display: flex; gap: 10px; align-items: center; margin-bottom: 18px; flex-wrap: wrap; }
        .mes-atual { text-transform: capitalize; font-family: Georgia, serif; font-size: 1.1rem; margin-right: auto; }

        .extrato { background: #fff; border-radius: var(--raio); box-shadow: var(--sombra); padding: 30px 34px; }
        .extrato__cabecalho { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 4px solid var(--cor-primaria); padding-bottom: 14px; margin-bottom: 20px; }
        .extrato__cabecalho h1 { font-family: Georgia, serif; margin: 0; }
        .extrato__cabecalho p { margin: 2px 0 0; color: var(--chocolate-medio); }
        .extrato__periodo { font-family: Georgia, serif; font-size: 1.3rem; color: var(--cor-primaria-texto); text-transform: capitalize; }
        .extrato__resumo { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 26px; }
        .extrato__resumo > div { background: var(--creme); border-radius: 12px; padding: 12px 14px; }
        .extrato__resumo small { display: block; color: var(--chocolate-medio); font-size: .74rem; text-transform: uppercase; letter-spacing: .05em; }
        .extrato__resumo strong { font-size: 1.25rem; color: var(--cor-primaria-texto); }
        .extrato h2 { font-family: Georgia, serif; font-size: 1.15rem; margin: 26px 0 10px; }
        .linha-total td { border-top: 3px double var(--chocolate); font-weight: 800; background: var(--creme); }
        .extrato__pe { margin-top: 26px; color: var(--chocolate-medio); font-size: .8rem; display: flex; justify-content: space-between; gap: 12px; }

        @media print {
            .lateral, .sem-impressao, .toast { display: none !important; }
            .admin-shell { display: block; }
            .principal { padding: 0; max-width: none; }
            .extrato { box-shadow: none; border-radius: 0; padding: 0; }
            body { background: #fff; }
            .tabela td, .tabela th { border-color: #ddd; }
        }
        @media (max-width: 760px) {
            .extrato__resumo { grid-template-columns: 1fr 1fr; }
        }
    </style>
@endpush

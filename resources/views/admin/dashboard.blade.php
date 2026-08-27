@extends('layouts.admin')

@section('titulo', texto('admin_dashboard', 'titulo', 'Dashboard — Gostosuras'))
@section('titulo_pagina', texto('admin_dashboard', 'titulo.pagina', 'Visão geral de hoje'))

@section('conteudo')
<div class="cartoes-resumo">
    <article class="cartao-metrica">
        <small>{{ texto('admin_dashboard', 'metrica.faturamento_hoje', 'Faturamento hoje') }}</small>
        <strong>{{ preco_br($faturamentoHoje) }}</strong>
    </article>
    <article class="cartao-metrica">
        <small>{{ texto('admin_dashboard', 'metrica.pedidos_hoje', 'Pedidos hoje') }}</small>
        <strong>{{ $pedidosHoje }}</strong>
    </article>
    <article class="cartao-metrica">
        <small>{{ texto('admin_dashboard', 'metrica.ticket_hoje', 'Ticket médio hoje') }}</small>
        <strong>{{ preco_br($ticketMedioHoje) }}</strong>
    </article>
    <article class="cartao-metrica">
        <small>{{ texto('admin_dashboard', 'metrica.faturamento_mes', 'Faturamento do mês') }}</small>
        <strong>{{ preco_br($faturamentoMes) }}</strong>
        <small>{{ str_replace(':qtd', $pedidosMes, texto('admin_dashboard', 'metrica.pedidos_mes', ':qtd pedidos no mês')) }}</small>
    </article>
</div>

<section class="painel-admin">
    <h2>{{ texto('admin_dashboard', 'grafico.titulo', 'Últimos 14 dias') }}</h2>
    <div class="grafico-barras">
        @foreach($serie as $ponto)
            <div class="barra-coluna" title="{{ $ponto['data'] }}: {{ preco_br($ponto['faturamento']) }} ({{ $ponto['pedidos'] }} {{ texto('admin_dashboard','grafico.pedidos','pedidos') }})">
                <div class="barra" style="height: {{ max(3, round($ponto['faturamento'] / $maxFaturamento * 100)) }}%"></div>
                <span>{{ $ponto['data'] }}</span>
            </div>
        @endforeach
    </div>
</section>

<div class="duas-colunas">
    <section class="painel-admin">
        <h2>{{ texto('admin_dashboard', 'status.titulo', 'Pedidos por status') }}</h2>
        <ul class="lista-status">
            @foreach(['novo', 'em_preparo', 'em_entrega', 'entregue', 'cancelado'] as $status)
                <li>
                    <span class="status-pilula status-pilula--{{ $status }}">{{ status_pedido($status) }}</span>
                    <strong>{{ $porStatus->get($status, 0) }}</strong>
                </li>
            @endforeach
        </ul>

        <h2 class="margem-topo">{{ texto('admin_dashboard', 'recentes.titulo', 'Pedidos recentes') }}</h2>
        <table class="tabela">
            <thead>
            <tr>
                <th>{{ texto('admin_pedidos', 'tabela.codigo', 'Código') }}</th>
                <th>{{ texto('admin_pedidos', 'tabela.cliente', 'Cliente') }}</th>
                <th>{{ texto('admin_pedidos', 'tabela.total', 'Total') }}</th>
                <th>{{ texto('admin_pedidos', 'tabela.status', 'Status') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($pedidosRecentes as $pedido)
                <tr>
                    <td><a href="{{ route('admin.pedidos.show', $pedido) }}">{{ $pedido->codigo }}</a></td>
                    <td>{{ $pedido->nome_cliente }}</td>
                    <td>{{ preco_br($pedido->total) }}</td>
                    <td><span class="status-pilula status-pilula--{{ $pedido->status }}">{{ status_pedido($pedido->status) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="painel-admin">
        <h2>{{ texto('admin_dashboard', 'estoque.titulo', 'Estoque precisando de atenção') }}</h2>
        @if($estoqueCritico->isEmpty())
            <p class="texto-suave">{{ texto('admin_dashboard', 'estoque.ok', 'Tudo sob controle por aqui.') }}</p>
        @else
            <table class="tabela">
                <thead>
                <tr>
                    <th>{{ texto('admin_produtos', 'tabela.produto', 'Produto') }}</th>
                    <th>{{ texto('admin_produtos', 'tabela.estoque', 'Estoque') }}</th>
                    <th>{{ texto('admin_produtos', 'tabela.minimo', 'Mínimo') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($estoqueCritico as $produto)
                    <tr>
                        <td><a href="{{ route('admin.produtos.index', ['estoque' => 'critico']) }}">{{ $produto->nome }}</a></td>
                        <td><strong class="{{ $produto->esgotado() ? 'texto-erro' : 'texto-alerta' }}">{{ $produto->estoque }}</strong></td>
                        <td>{{ $produto->estoque_minimo }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
        <a href="{{ route('admin.relatorios', ['aba' => 'horarios']) }}" class="botao margem-topo">
            {{ texto('admin_dashboard', 'botao.previsao', 'Ver previsão de produção por horário') }}
        </a>
    </section>
</div>
@endsection

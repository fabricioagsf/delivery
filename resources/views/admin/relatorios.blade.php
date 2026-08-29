@extends('layouts.admin')

@section('titulo', texto('admin_relatorios', 'titulo', 'Relatórios — Gostosuras'))
@section('titulo_pagina', texto('admin_relatorios', 'titulo.pagina', 'Relatórios'))

@section('conteudo')
<form method="GET" class="filtros filtros--periodo">
    <input type="hidden" name="aba" value="{{ $aba }}">
    <label class="filtro-data">
        {{ texto('admin_relatorios', 'campo.de', 'De') }}
        <input type="date" name="de" value="{{ $de->toDateString() }}">
    </label>
    <label class="filtro-data">
        {{ texto('admin_relatorios', 'campo.ate', 'Até') }}
        <input type="date" name="ate" value="{{ $ate->toDateString() }}">
    </label>
    <button type="submit" class="botao">{{ texto('admin_relatorios', 'botao.aplicar', 'Aplicar período') }}</button>
    <a href="{{ route('admin.relatorios.exportar', request()->query()) }}" class="botao botao--chefe">
        {{ texto('admin_relatorios', 'botao.exportar', '⬇ Exportar CSV') }}
    </a>
    <a href="{{ route('admin.relatorios.mensal') }}" class="botao">
        {{ texto('admin_relatorios', 'botao.mensal', 'Relatório mensal (PDF)') }}
    </a>
    <a href="{{ route('admin.relatorios.simples', array_merge(request()->query(), ['tipo' => 'vendas'])) }}" class="botao">
        {{ texto('admin_relatorios', 'botao.simples_vendas', 'Relatório simples de vendas') }}
    </a>
    <a href="{{ route('admin.relatorios.simples', array_merge(request()->query(), ['tipo' => 'produtos'])) }}" class="botao">
        {{ texto('admin_relatorios', 'botao.simples_produtos', 'Relatório simples de produtos') }}
    </a>
</form>

<div class="chips chips--abas">
    @foreach($abas as $abaNome)
        <a href="{{ route('admin.relatorios', array_merge(request()->query(), ['aba' => $abaNome])) }}"
           class="chip {{ $aba === $abaNome ? 'chip--ativa' : '' }}">
            {{ texto('admin_relatorios', 'aba.' . $abaNome, ucfirst($abaNome)) }}
        </a>
    @endforeach
</div>

{{-- ============================ VENDAS ============================ --}}
@if($aba === 'vendas')
    <div class="cartoes-resumo">
        <article class="cartao-metrica"><small>{{ texto('admin_relatorios', 'vendas.faturamento', 'Faturamento no período') }}</small><strong>{{ preco_br($resumo['faturamento']) }}</strong></article>
        <article class="cartao-metrica"><small>{{ texto('admin_relatorios', 'vendas.pedidos', 'Pedidos') }}</small><strong>{{ $resumo['pedidos'] }}</strong></article>
        <article class="cartao-metrica"><small>{{ texto('admin_relatorios', 'vendas.ticket', 'Ticket médio') }}</small><strong>{{ preco_br($resumo['ticketMedio']) }}</strong></article>
        <article class="cartao-metrica"><small>{{ texto('admin_relatorios', 'vendas.taxas', 'Taxas de entrega somadas') }}</small><strong>{{ preco_br($resumo['taxasEntrega']) }}</strong></article>
        <article class="cartao-metrica"><small>{{ texto('admin_relatorios', 'vendas.clientes', 'Clientes distintos (com conta)') }}</small><strong>{{ $resumo['clientesDistintos'] }}</strong></article>
    </div>

    <section class="painel-admin">
        <h2>{{ texto('admin_relatorios', 'vendas.por_dia', 'Vendas por dia') }}</h2>
        <div class="grafico-barras grafico-barras--rolagem">
            @foreach($porDia as $linha)
                <div class="barra-coluna" title="{{ $linha->dia }}: {{ preco_br($linha->faturamento) }}">
                    <div class="barra" style="height: {{ max(3, round($linha->faturamento / $maxDia * 100)) }}%"></div>
                    <span>{{ \Illuminate\Support\Str::substr($linha->dia, 8, 2) }}/{{ \Illuminate\Support\Str::substr($linha->dia, 5, 2) }}</span>
                </div>
            @endforeach
        </div>
    </section>

{{-- ============================ PRODUTOS ============================ --}}
@elseif($aba === 'produtos')
    <section class="painel-admin">
        <h2>{{ texto('admin_relatorios', 'produtos.titulo', 'Mais vendidos no período') }}</h2>
        @if($itens->isEmpty())
            <p class="texto-suave">{{ texto('admin_relatorios', 'produtos.vazio', 'Nenhuma venda registrada neste período.') }}</p>
        @else
            <div class="tabela-rolagem">
                <table class="tabela">
                <thead>
                <tr>
                    <th>{{ texto('admin_produtos', 'tabela.produto', 'Produto') }}</th>
                    <th>{{ texto('admin_relatorios', 'produtos.quantidade', 'Qtd vendida') }}</th>
                    <th>{{ texto('admin_relatorios', 'produtos.faturamento', 'Faturamento') }}</th>
                    <th style="width: 30%"> </th>
                </tr>
                </thead>
                <tbody>
                @foreach($itens as $item)
                    <tr>
                        <td><strong>{{ $item->nome_produto }}</strong></td>
                        <td>{{ $item->quantidade_vendida }}</td>
                        <td>{{ preco_br($item->faturamento) }}</td>
                        <td>
                            <div class="barra-linha" aria-hidden="true">
                                <span style="width: {{ round($item->quantidade_vendida / $maxQuantidade * 100) }}%"></span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                </table>
            </div>
        @endif
    </section>

{{-- ============================ HORÁRIOS ============================ --}}
@elseif($aba === 'horarios')
    <section class="painel-admin">
        <h2>{{ texto('admin_relatorios', 'horarios.titulo', 'Quanto produzir em cada horário') }}</h2>
        <p class="texto-suave">
            {{ str_replace(':margem', number_format($margemProducao, 0), texto('admin_relatorios', 'horarios.explicacao',
                'Média de itens vendidos em cada hora do dia, considerando o período escolhido, mais uma margem de segurança de :margem%. Use como guia de produção diária.')) }}
        </p>
        @if($linhasHorario->isEmpty())
            <p class="texto-suave">{{ texto('admin_relatorios', 'horarios.vazio', 'Ainda sem vendas suficientes para calcular a previsão.') }}</p>
        @else
            <div class="tabela-rolagem">
                <table class="tabela">
                <thead>
                <tr>
                    <th>{{ texto('admin_relatorios', 'horarios.hora', 'Hora') }}</th>
                    <th>{{ texto('admin_relatorios', 'horarios.total_periodo', 'Itens no período') }}</th>
                    <th>{{ texto('admin_relatorios', 'horarios.media_dia', 'Média por dia ativo') }}</th>
                    <th>{{ texto('admin_relatorios', 'horarios.sugestao', 'Produzir (com margem)') }}</th>
                    <th style="width: 25%"> </th>
                </tr>
                </thead>
                <tbody>
                @foreach($linhasHorario as $linha)
                    <tr>
                        <td><strong>{{ $linha['hora'] }}</strong></td>
                        <td>{{ $linha['total_itens'] }}</td>
                        <td>{{ $linha['media_por_dia'] }}</td>
                        <td><strong class="texto-destaque">{{ $linha['sugestao_producao'] }}</strong></td>
                        <td>
                            <div class="barra-linha barra-linha--salmao" aria-hidden="true">
                                <span style="width: {{ round($linha['total_itens'] / $maxItensHora * 100) }}%"></span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                </table>
            </div>
            <p class="nota-segura nota-segura--admin">{{ texto('admin_relatorios', 'horarios.nota_margem', 'Para ajustar a margem de segurança, altere a configuração "margem_producao" da loja.') }}</p>
        @endif
    </section>

{{-- ============================ PAGAMENTOS ============================ --}}
@elseif($aba === 'pagamentos')
    <section class="painel-admin">
        <h2>{{ texto('admin_relatorios', 'pagamentos.titulo', 'Formas de pagamento no período') }}</h2>
        @if($formas->isEmpty())
            <p class="texto-suave">{{ texto('admin_relatorios', 'pagamentos.vazio', 'Nenhuma venda registrada neste período.') }}</p>
        @else
            <div class="tabela-rolagem">
                <table class="tabela">
                <thead>
                <tr>
                    <th>{{ texto('admin_pedidos', 'tabela.pagamento', 'Pagamento') }}</th>
                    <th>{{ texto('admin_relatorios', 'vendas.pedidos', 'Pedidos') }}</th>
                    <th>{{ texto('admin_relatorios', 'produtos.faturamento', 'Faturamento') }}</th>
                    <th>{{ texto('admin_relatorios', 'pagamentos.fatia', 'Fatia') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($formas as $forma)
                    <tr>
                        <td><strong>{{ forma_pagamento_label($forma->forma_pagamento) }}</strong></td>
                        <td>{{ $forma->pedidos }}</td>
                        <td>{{ preco_br($forma->faturamento) }}</td>
                        <td>{{ number_format($forma->faturamento / $totalFaturamento * 100, 1) }}%</td>
                    </tr>
                @endforeach
                </tbody>
                </table>
            </div>
        @endif
    </section>

{{-- ============================ ENTREGAS ============================ --}}
@elseif($aba === 'entregas')
    <div class="duas-colunas duas-colunas--compacta">
        @foreach($tipos as $tipo)
            <article class="painel-admin cartao-tipo">
                <h2>{{ texto('conta', 'tipo.' . $tipo->tipo_entrega, ucfirst($tipo->tipo_entrega)) }}</h2>
                <strong class="numero-grande">{{ $tipo->pedidos }}</strong>
                <p>{{ texto('admin_relatorios', 'entregas.pedidos_no_periodo', 'pedidos no período') }}</p>
                <p class="resumo-linha"><span>{{ texto('admin_relatorios', 'produtos.faturamento', 'Faturamento') }}</span><span>{{ preco_br($tipo->faturamento) }}</span></p>
                @if($tipo->tipo_entrega === 'entrega')
                    <p class="resumo-linha"><span>{{ texto('admin_relatorios', 'vendas.taxas', 'Taxas somadas') }}</span><span>{{ preco_br($tipo->taxas) }}</span></p>
                @endif
                <p class="texto-suave">{{ number_format($tipo->pedidos / $totalPedidosTipos * 100, 1) }}%</p>
            </article>
        @endforeach
    </div>

{{-- ============================ ESTOQUE ============================ --}}
@elseif($aba === 'estoque')
    <section class="painel-admin">
        <h2>{{ texto('admin_relatorios', 'estoque.titulo', 'Estoque crítico ou esgotado') }}</h2>
        @if($criticos->isEmpty())
            <p class="texto-suave">{{ texto('admin_dashboard', 'estoque.ok', 'Tudo sob controle por aqui.') }}</p>
        @else
            <div class="tabela-rolagem">
                <table class="tabela">
                <thead>
                <tr>
                    <th>{{ texto('admin_produtos', 'tabela.produto', 'Produto') }}</th>
                    <th>{{ texto('admin_produtos', 'tabela.categoria', 'Categoria') }}</th>
                    <th>{{ texto('admin_produtos', 'tabela.estoque', 'Estoque') }}</th>
                    <th>{{ texto('admin_produtos', 'tabela.minimo', 'Mínimo') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($criticos as $linha)
                    <tr>
                        <td><strong>{{ $linha->produto?->nome }}</strong></td>
                        <td>{{ $linha->produto?->categoria?->nome }}</td>
                        <td><strong class="{{ ($linha->estoque ?? 0) <= 0 ? 'texto-erro' : 'texto-alerta' }}">{{ $linha->estoque }}</strong></td>
                        <td>{{ $linha->estoque_minimo }}</td>
                        <td><a href="{{ route('admin.produtos.index', ['estoque' => 'critico']) }}" class="mini-botao mini-botao--salvar">{{ texto('admin_relatorios', 'estoque.repor', 'Repor') }}</a></td>
                    </tr>
                @endforeach
                </tbody>
                </table>
            </div>
            {{ $criticos->links('vendor.pagination.padrao') }}
        @endif
    </section>
@endif
@endsection

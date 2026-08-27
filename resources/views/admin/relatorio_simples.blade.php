<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $tipo === 'vendas' ? texto('admin_simples', 'vendas.titulo', 'Relatório de vendas') : texto('admin_simples', 'produtos.titulo', 'Relatório de produtos') }} — {{ texto('layout', 'site.nome', 'Gostosuras') }}</title>
    <style>
        body { font-family: Consolas, 'Courier New', monospace; font-size: 14px; color: #222; margin: 24px; background: #fff; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .periodo { margin: 0 0 16px; color: #555; }
        table { border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #999; padding: 4px 10px; text-align: left; }
        th { background: #f2f2f2; }
        td.num, th.num { text-align: right; }
        tr.total td { font-weight: bold; border-top: 2px solid #222; }
        .acoes { margin: 12px 0; }
        .acoes a, .acoes button { font-family: inherit; font-size: 13px; margin-right: 10px; }
        @media print {
            .acoes { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="acoes">
        <a href="{{ route('admin.relatorios.simples', array_merge(request()->query(), ['export' => 'csv'])) }}">{{ texto('admin_simples', 'botao.csv', 'Exportar CSV') }}</a>
        <button type="button" onclick="window.print()">{{ texto('admin_simples', 'botao.pdf', 'Exportar PDF (imprimir)') }}</button>
        <a href="{{ route('admin.relatorios.simples', ['tipo' => $tipo === 'vendas' ? 'produtos' : 'vendas', 'de' => $de->toDateString(), 'ate' => $ate->toDateString()]) }}">
            {{ $tipo === 'vendas'
                ? texto('admin_simples', 'trocar_produtos', 'Ver como relatório de produtos')
                : texto('admin_simples', 'trocar_vendas', 'Ver como relatório de vendas') }}
        </a>
        <a href="{{ route('admin.relatorios') }}">{{ texto('confirmacao', 'botao.voltar', 'Voltar') }}</a>
    </div>

    <h1>
        @if($tipo === 'vendas')
            {{ texto('admin_simples', 'vendas.titulo', 'Relatório de vendas') }}
        @else
            {{ texto('admin_simples', 'produtos.titulo', 'Relatório de produtos') }}
        @endif
    </h1>
    <p class="periodo">
        {{ texto('layout', 'site.nome', 'Gostosuras') }} —
        {{ texto('admin_relatorios', 'campo.de', 'De') }} {{ $de->format('d/m/Y') }}
        {{ texto('admin_relatorios', 'campo.ate', 'até') }} {{ $ate->format('d/m/Y') }}
    </p>

    <table>
        <thead>
        <tr>
            <th>{{ texto('admin_simples', 'coluna.produto', 'Produto') }}</th>
            <th class="num">{{ texto('admin_simples', 'coluna.quantidade', 'Quantidade') }}</th>
            @if($tipo === 'vendas')
                <th class="num">{{ texto('admin_simples', 'coluna.valor_venda', 'Valor de venda') }}</th>
            @else
                <th class="num">{{ texto('admin_simples', 'coluna.valor_unitario', 'Valor unitário') }}</th>
                <th class="num">{{ texto('admin_simples', 'coluna.total', 'Total') }}</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @php
            $totalGeral = 0;
            $totalItens = 0;
        @endphp
        @forelse($linhas as $linha)
            @php
                $totalGeral += $linha->total;
                $totalItens += $linha->quantidade;
            @endphp
            <tr>
                <td>{{ $linha->nome_produto }}</td>
                <td class="num">{{ $linha->quantidade }}</td>
                @if($tipo === 'vendas')
                    <td class="num">{{ preco_br($linha->total) }}</td>
                @else
                    <td class="num">{{ preco_br($linha->valor_unitario) }}</td>
                    <td class="num">{{ preco_br($linha->total) }}</td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $tipo === 'vendas' ? 3 : 4 }}">{{ texto('admin_relatorios', 'produtos.vazio', 'Nenhuma venda registrada neste período.') }}</td></tr>
        @endforelse
        @if($linhas->isNotEmpty())
            <tr class="total">
                <td>{{ texto('admin_simples', 'coluna.total_geral', 'TOTAL GERAL') }}</td>
                <td class="num">{{ $totalItens }}</td>
                @if($tipo === 'vendas')
                    <td class="num">{{ preco_br($totalGeral) }}</td>
                @else
                    <td></td>
                    <td class="num">{{ preco_br($totalGeral) }}</td>
                @endif
            </tr>
        @endif
        </tbody>
    </table>
</body>
</html>

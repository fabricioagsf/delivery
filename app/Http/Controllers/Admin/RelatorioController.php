<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\ProdutoEstoque;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    public const ABAS = ['vendas', 'produtos', 'horarios', 'pagamentos', 'entregas', 'estoque'];

    public function index(Request $request): View
    {
        $aba = in_array($request->query('aba'), self::ABAS, true)
            ? $request->query('aba')
            : 'vendas';

        [$de, $ate] = $this->periodo($request);

        return view('admin.relatorios', [
            'aba' => $aba,
            'abas' => self::ABAS,
            'de' => $de,
            'ate' => $ate,
            ...$this->{'dados'.ucfirst($aba)}($de, $ate),
        ]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function periodo(Request $request): array
    {
        try {
            $de = CarbonImmutable::parse((string) $request->query('de'))?->startOfDay();
            $ate = CarbonImmutable::parse((string) $request->query('ate'))?->endOfDay();
        } catch (\Throwable) {
            $de = null;
            $ate = null;
        }

        if (! $de || ! $ate || $de->gt($ate)) {
            $ate = CarbonImmutable::now()->endOfDay();
            $de = $ate->subDays(29)->startOfDay();
        }

        return [$de, $ate];
    }

    public function exportar(Request $request)
    {
        $aba = in_array($request->query('aba'), self::ABAS, true)
            ? $request->query('aba')
            : 'vendas';

        [$de, $ate] = $this->periodo($request);
        $dados = $this->{'dados'.ucfirst($aba)}($de, $ate);

        $nomeArquivo = "relatorio-{$aba}-".$de->format('Y-m-d').'-a-'.$ate->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($aba, $dados, $de, $ate) {
            $saida = fopen('php://output', 'w');

            // BOM para o Excel reconhecer UTF-8; ; como separador (padrão BR)
            fwrite($saida, "\xEF\xBB\xBF");

            $linha = fn ($campos) => fputcsv($saida, $campos, ';');

            $linha([texto('admin_relatorios', 'titulo.pagina', 'Relatórios').' — '.texto('admin_relatorios', 'aba.'.$aba, $aba)]);
            $linha([texto('admin_relatorios', 'campo.de', 'De').': '.$de->format('d/m/Y'), texto('admin_relatorios', 'campo.ate', 'Até').': '.$ate->format('d/m/Y')]);
            $linha(['']);

            switch ($aba) {
                case 'vendas':
                    $linha([texto('admin_relatorios', 'vendas.faturamento', 'Faturamento'), number_format($dados['resumo']['faturamento'], 2, ',', '.')]);
                    $linha([texto('admin_relatorios', 'vendas.pedidos', 'Pedidos'), $dados['resumo']['pedidos']]);
                    $linha([texto('admin_relatorios', 'vendas.ticket', 'Ticket médio'), number_format($dados['resumo']['ticketMedio'], 2, ',', '.')]);
                    $linha([texto('admin_relatorios', 'vendas.taxas', 'Taxas de entrega'), number_format($dados['resumo']['taxasEntrega'], 2, ',', '.')]);
                    $linha(['']);
                    $linha([texto('admin_relatorios', 'vendas.por_dia', 'Vendas por dia')]);
                    $linha([texto('admin_relatorios', 'horarios.hora', 'Dia'), texto('admin_relatorios', 'produtos.faturamento', 'Faturamento'), texto('admin_relatorios', 'vendas.pedidos', 'Pedidos')]);
                    foreach ($dados['porDia'] as $l) {
                        $linha([$l->dia, number_format($l->faturamento, 2, ',', '.'), $l->pedidos]);
                    }
                    break;

                case 'produtos':
                    $linha([texto('admin_produtos', 'tabela.produto', 'Produto'), texto('admin_relatorios', 'produtos.quantidade', 'Qtd vendida'), texto('admin_relatorios', 'produtos.faturamento', 'Faturamento')]);
                    foreach ($dados['itens'] as $i) {
                        $linha([$i->nome_produto, $i->quantidade_vendida, number_format($i->faturamento, 2, ',', '.')]);
                    }
                    break;

                case 'horarios':
                    $linha([texto('admin_relatorios', 'horarios.hora', 'Hora'), texto('admin_relatorios', 'horarios.total_periodo', 'Itens no período'), texto('admin_relatorios', 'horarios.media_dia', 'Média por dia ativo'), texto('admin_relatorios', 'horarios.sugestao', 'Produzir (com margem)')]);
                    foreach ($dados['linhasHorario'] as $l) {
                        $linha([$l['hora'], $l['total_itens'], $l['media_por_dia'], $l['sugestao_producao']]);
                    }
                    break;

                case 'pagamentos':
                    $linha([texto('admin_pedidos', 'tabela.pagamento', 'Pagamento'), texto('admin_relatorios', 'vendas.pedidos', 'Pedidos'), texto('admin_relatorios', 'produtos.faturamento', 'Faturamento')]);
                    foreach ($dados['formas'] as $f) {
                        $linha([forma_pagamento_label($f->forma_pagamento), $f->pedidos, number_format($f->faturamento, 2, ',', '.')]);
                    }
                    break;

                case 'entregas':
                    $linha([texto('admin_pedidos', 'tabela.tipo', 'Tipo'), texto('admin_relatorios', 'vendas.pedidos', 'Pedidos'), texto('admin_relatorios', 'produtos.faturamento', 'Faturamento'), texto('admin_relatorios', 'vendas.taxas', 'Taxas')]);
                    foreach ($dados['tipos'] as $t) {
                        $linha([texto('conta', 'tipo.'.$t->tipo_entrega, $t->tipo_entrega), $t->pedidos, number_format($t->faturamento, 2, ',', '.'), number_format($t->taxas, 2, ',', '.')]);
                    }
                    break;

                case 'estoque':
                    $linha([texto('admin_produtos', 'tabela.produto', 'Produto'), texto('admin_produtos', 'tabela.categoria', 'Categoria'), texto('admin_produtos', 'tabela.estoque', 'Estoque'), texto('admin_produtos', 'tabela.minimo', 'Mínimo')]);
                    foreach ($dados['criticos'] as $pe) {
                        $linha([$pe->produto?->nome, $pe->produto?->categoria?->nome, $pe->estoque, $pe->estoque_minimo]);
                    }
                    break;
            }

            fclose($saida);
        }, $nomeArquivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Relatório mensal estilo extrato: vendas dia a dia + ranking de produtos,
     * pronto para "Salvar como PDF" (Ctrl+P / botão de imprimir).
     */
    /**
     * Relatórios simples estilo planilha (sem enfeite):
     * tipo=vendas  -> produto | quantidade | valor de venda
     * tipo=produtos -> produto | quantidade | valor unitário | total
     * Com exportação CSV (?export=csv) e PDF via impressão.
     */
    public function simples(Request $request)
    {
        $tipo = $request->query('tipo') === 'produtos' ? 'produtos' : 'vendas';
        [$de, $ate] = $this->periodo($request);

        $linhas = DB::table('pedido_itens')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_itens.pedido_id')
            ->where('pedidos.loja_id', loja_atual_id())
            ->where('pedidos.status', '!=', 'cancelado')
            ->whereBetween('pedidos.created_at', [$de, $ate])
            ->selectRaw(
                'pedido_itens.nome_produto, '
                .'SUM(pedido_itens.quantidade) as quantidade, '
                .'ROUND(AVG(pedido_itens.preco_unitario), 2) as valor_unitario, '
                .'SUM(pedido_itens.quantidade * pedido_itens.preco_unitario) as total'
            )
            ->groupByRaw('pedido_itens.produto_id, pedido_itens.nome_produto')
            ->orderByRaw('SUM(pedido_itens.quantidade) desc')
            ->get();

        if ($request->query('export') === 'csv') {
            $nomeArquivo = "relatorio-{$tipo}-".$de->format('Y-m-d').'-a-'.$ate->format('Y-m-d').'.csv';

            return response()->streamDownload(function () use ($tipo, $linhas, $de, $ate) {
                $saida = fopen('php://output', 'w');
                fwrite($saida, "\xEF\xBB\xBF");
                $escrever = fn ($campos) => fputcsv($saida, $campos, ';');

                if ($tipo === 'vendas') {
                    $escrever([texto('admin_simples', 'vendas.titulo', 'Relatório de vendas')]);
                    $escrever([texto('admin_relatorios', 'campo.de', 'De').': '.$de->format('d/m/Y'), texto('admin_relatorios', 'campo.ate', 'Até').': '.$ate->format('d/m/Y')]);
                    $escrever(['']);
                    $escrever([texto('admin_simples', 'coluna.produto', 'Produto'), texto('admin_simples', 'coluna.quantidade', 'Quantidade'), texto('admin_simples', 'coluna.valor_venda', 'Valor de venda')]);
                } else {
                    $escrever([texto('admin_simples', 'produtos.titulo', 'Relatório de produtos')]);
                    $escrever([texto('admin_relatorios', 'campo.de', 'De').': '.$de->format('d/m/Y'), texto('admin_relatorios', 'campo.ate', 'Até').': '.$ate->format('d/m/Y')]);
                    $escrever(['']);
                    $escrever([texto('admin_simples', 'coluna.produto', 'Produto'), texto('admin_simples', 'coluna.quantidade', 'Quantidade'), texto('admin_simples', 'coluna.valor_unitario', 'Valor unitário'), texto('admin_simples', 'coluna.total', 'Total')]);
                }

                $totalGeral = 0;
                $totalItens = 0;

                foreach ($linhas as $l) {
                    $totalGeral += $l->total;
                    $totalItens += $l->quantidade;

                    if ($tipo === 'vendas') {
                        $escrever([$l->nome_produto, $l->quantidade, number_format($l->total, 2, ',', '.')]);
                    } else {
                        $escrever([$l->nome_produto, $l->quantidade, number_format($l->valor_unitario, 2, ',', '.'), number_format($l->total, 2, ',', '.')]);
                    }
                }

                $escrever(['']);
                $escrever($tipo === 'vendas'
                    ? [texto('admin_simples', 'coluna.total_geral', 'TOTAL GERAL'), $totalItens, number_format($totalGeral, 2, ',', '.')]
                    : [texto('admin_simples', 'coluna.total_geral', 'TOTAL GERAL'), $totalItens, '', number_format($totalGeral, 2, ',', '.')]);

                fclose($saida);
            }, $nomeArquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('admin.relatorio_simples', [
            'tipo' => $tipo,
            'de' => $de,
            'ate' => $ate,
            'linhas' => $linhas,
        ]);
    }

    public function mensal(Request $request): View
    {
        $mes = (int) $request->query('mes', now()->month);
        $ano = (int) $request->query('ano', now()->year);

        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        if ($ano < 2020 || $ano > 2100) {
            $ano = now()->year;
        }

        $inicio = CarbonImmutable::create($ano, $mes, 1, 0, 0, 0)->startOfDay();
        $fim = $inicio->endOfMonth()->endOfDay();

        $base = $this->filtroPedidos($inicio, $fim);

        $resumo = [
            'faturamento' => (float) (clone $base)->sum('total'),
            'pedidos' => (int) (clone $base)->count(),
            'ticketMedio' => (float) (clone $base)->avg('total'),
            'taxasEntrega' => (float) (clone $base)->sum('taxa_entrega'),
        ];

        $porDia = (clone $base)
            ->selectRaw('DATE(created_at) as dia, SUM(total) as faturamento, COUNT(*) as pedidos')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();

        $produtos = DB::table('pedido_itens')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_itens.pedido_id')
            ->where('pedidos.loja_id', loja_atual_id())
            ->where('pedidos.status', '!=', 'cancelado')
            ->whereBetween('pedidos.created_at', [$inicio, $fim])
            ->selectRaw(
                'pedido_itens.nome_produto, '
                .'SUM(pedido_itens.quantidade) as quantidade_vendida, '
                .'SUM(pedido_itens.quantidade * pedido_itens.preco_unitario) as faturamento'
            )
            ->groupByRaw('pedido_itens.produto_id, pedido_itens.nome_produto')
            ->orderByRaw('SUM(pedido_itens.quantidade) desc')
            ->limit(15)
            ->get();

        return view('admin.relatorio_mensal', [
            'mes' => $mes,
            'ano' => $ano,
            'nomeMes' => $inicio->translatedFormat('F'),
            'resumo' => $resumo,
            'porDia' => $porDia,
            'produtos' => $produtos,
            'mesAnterior' => $inicio->subMonth(),
            'mesSeguinte' => $inicio->addMonth(),
        ]);
    }

    protected function filtroPedidos(CarbonImmutable $de, CarbonImmutable $ate)
    {
        return Pedido::query()
            ->where('loja_id', loja_atual_id())
            ->where('status', '!=', 'cancelado')
            ->whereBetween('created_at', [$de, $ate]);
    }

    // ------------------------------------------------------------------
    // Aba: Vendas
    // ------------------------------------------------------------------
    protected function dadosVendas(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $base = $this->filtroPedidos($de, $ate);

        $resumo = [
            'faturamento' => (float) (clone $base)->sum('total'),
            'pedidos' => (int) (clone $base)->count(),
            'ticketMedio' => (float) (clone $base)->avg('total'),
            'taxasEntrega' => (float) (clone $base)->sum('taxa_entrega'),
            'clientesDistintos' => (int) (clone $base)->distinct('cliente_id')->whereNotNull('cliente_id')->count('cliente_id'),
        ];

        $porDia = (clone $base)
            ->selectRaw('DATE(created_at) as dia, SUM(total) as faturamento, COUNT(*) as pedidos')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();

        $maxDia = max(1, $porDia->max('faturamento'));

        return compact('resumo', 'porDia', 'maxDia');
    }

    // ------------------------------------------------------------------
    // Aba: Produtos (mais vendidos no período)
    // ------------------------------------------------------------------
    protected function dadosProdutos(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $itens = DB::table('pedido_itens')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_itens.pedido_id')
            ->where('pedidos.loja_id', loja_atual_id())
            ->where('pedidos.status', '!=', 'cancelado')
            ->whereBetween('pedidos.created_at', [$de, $ate])
            ->selectRaw(
                'pedido_itens.produto_id, pedido_itens.nome_produto, '
                .'SUM(pedido_itens.quantidade) as quantidade_vendida, '
                .'SUM(pedido_itens.quantidade * pedido_itens.preco_unitario) as faturamento'
            )
            ->groupByRaw('pedido_itens.produto_id, pedido_itens.nome_produto')
            ->orderByRaw('SUM(pedido_itens.quantidade) desc')
            ->limit(20)
            ->get();

        $maxQuantidade = max(1, (int) $itens->max('quantidade_vendida'));

        return compact('itens', 'maxQuantidade');
    }

    // ------------------------------------------------------------------
    // Aba: Horários — quantos itens produzir em cada hora, com base na
    // média de vendas daquele horário no período + margem de segurança.
    // ------------------------------------------------------------------
    protected function dadosHorarios(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $margem = (float) config_loja('margem_producao', '20');

        $linhas = DB::table('pedido_itens')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_itens.pedido_id')
            ->where('pedidos.loja_id', loja_atual_id())
            ->where('pedidos.status', '!=', 'cancelado')
            ->whereBetween('pedidos.created_at', [$de, $ate])
            ->selectRaw(
                'HOUR(pedidos.created_at) as hora, '
                .'SUM(pedido_itens.quantidade) as total_itens, '
                .'COUNT(DISTINCT DATE(pedidos.created_at)) as dias_com_venda'
            )
            ->groupByRaw('HOUR(pedidos.created_at)')
            ->orderByRaw('HOUR(pedidos.created_at)')
            ->get()
            ->map(function ($linha) use ($margem) {
                $media = $linha->dias_com_venda > 0
                    ? $linha->total_itens / $linha->dias_com_venda
                    : 0;

                return [
                    'hora' => sprintf('%02d:00', $linha->hora),
                    'total_itens' => (int) $linha->total_itens,
                    'dias_com_venda' => (int) $linha->dias_com_venda,
                    'media_por_dia' => round($media, 1),
                    'sugestao_producao' => (int) ceil($media * (1 + $margem / 100)),
                ];
            });

        $maxTotal = max(1, (int) $linhas->max('total_itens'));

        return [
            'linhasHorario' => $linhas,
            'maxItensHora' => $maxTotal,
            'margemProducao' => $margem,
        ];
    }

    // ------------------------------------------------------------------
    // Aba: Pagamentos
    // ------------------------------------------------------------------
    protected function dadosPagamentos(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $formas = (clone $this->filtroPedidos($de, $ate))
            ->selectRaw('forma_pagamento, COUNT(*) as pedidos, SUM(total) as faturamento')
            ->groupByRaw('forma_pagamento')
            ->orderByRaw('SUM(total) desc')
            ->get();

        $totalFaturamento = max(0.01, (float) $formas->sum('faturamento'));

        return compact('formas', 'totalFaturamento');
    }

    // ------------------------------------------------------------------
    // Aba: Entregas (entrega x retirada)
    // ------------------------------------------------------------------
    protected function dadosEntregas(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $tipos = (clone $this->filtroPedidos($de, $ate))
            ->selectRaw('tipo_entrega, COUNT(*) as pedidos, SUM(total) as faturamento, SUM(taxa_entrega) as taxas')
            ->groupByRaw('tipo_entrega')
            ->get();

        $totalPedidosTipos = max(1, (int) $tipos->sum('pedidos'));

        return compact('tipos', 'totalPedidosTipos');
    }

    // ------------------------------------------------------------------
    // Aba: Estoque crítico / esgotado
    // ------------------------------------------------------------------
    protected function dadosEstoque(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $lojaId = loja_atual_id();

        $criticos = ProdutoEstoque::query()
            ->with('produto.categoria:id,nome,slug')
            ->whereNotNull('estoque')
            ->whereColumn('estoque', '<=', 'estoque_minimo')
            ->when($lojaId, fn ($q) => $q->where('loja_id', $lojaId))
            ->orderByRaw('(estoque IS NULL) asc, estoque asc')
            ->paginate(25);

        return ['criticos' => $criticos];
    }
}

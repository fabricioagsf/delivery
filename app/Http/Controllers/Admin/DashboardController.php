<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Produto;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $hoje = now()->startOfDay();
        $inicioMes = now()->startOfMonth();

        // Vendas dos últimos 14 dias (exclui cancelados)
        $serieBruta = Pedido::query()
            ->where('status', '!=', 'cancelado')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as dia, SUM(total) as faturamento, COUNT(*) as pedidos')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get()
            ->keyBy('dia');

        $serie = collect(range(0, 13))->map(function ($i) use ($serieBruta) {
            $dia = now()->subDays(13 - $i)->toDateString();
            $registro = $serieBruta->get($dia);

            return [
                'data' => date('d/m', strtotime($dia)),
                'faturamento' => (float) ($registro->faturamento ?? 0),
                'pedidos' => (int) ($registro->pedidos ?? 0),
            ];
        });

        $maxFaturamento = max(1, $serie->max('faturamento'));

        $porStatus = Pedido::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupByRaw('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'faturamentoHoje' => (float) Pedido::where('status', '!=', 'cancelado')->where('created_at', '>=', $hoje)->sum('total'),
            'pedidosHoje' => (int) Pedido::where('created_at', '>=', $hoje)->count(),
            'ticketMedioHoje' => (float) Pedido::where('status', '!=', 'cancelado')->where('created_at', '>=', $hoje)->avg('total'),
            'faturamentoMes' => (float) Pedido::where('status', '!=', 'cancelado')->where('created_at', '>=', $inicioMes)->sum('total'),
            'pedidosMes' => (int) Pedido::where('created_at', '>=', $inicioMes)->count(),
            'serie' => $serie,
            'maxFaturamento' => $maxFaturamento,
            'porStatus' => $porStatus,
            'pedidosRecentes' => Pedido::orderByDesc('id')->take(8)->get(),
            'estoqueCritico' => Produto::query()
                ->whereNotNull('estoque')
                ->whereColumn('estoque', '<=', 'estoque_minimo')
                ->orderByRaw('COALESCE(estoque, 999999) asc')
                ->take(8)
                ->get(),
        ]);
    }
}

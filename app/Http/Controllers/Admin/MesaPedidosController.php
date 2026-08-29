<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\Pedido;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MesaPedidosController extends Controller
{
    /**
     * Tela de controle: grade com todas as mesas mostrando o status do
     * pedido em aberto (novo, em preparo, etc.). Atualiza por AJAX.
     */
    public function index(): View
    {
        $mesas = Mesa::query()->ativas()->orderBy('id')->get();

        return view('admin.mesas_pedidos', [
            'mesas' => $mesas,
        ]);
    }

    /**
     * Retorna o estado atual de cada mesa (JSON). Endpoint consumido por
     * polling — entrega o resumo dos pedidos em aberto por mesa, além da
     * versão atual (para o cliente saber se mudou alguma coisa).
     *
     * Aceita `?ultima_versao=N` — quando bate, devolve só `versao` + `sem_mudancas: true`
     * (resposta leve para o cliente decidir se precisa de um payload completo).
     */
    public function estado(Request $request): JsonResponse
    {
        $ultimaVersao = $request->integer('ultima_versao') ?: null;
        $lojaId = loja_atual_id();
        $mesas = Mesa::query()->ativas()->orderBy('id')->get();

        $pedidosPorMesa = Pedido::query()
            ->where('loja_id', $lojaId)
            ->whereIn('mesa_id', $mesas->pluck('id'))
            ->whereIn('status', ['novo', 'em_preparo', 'em_entrega'])
            ->orderBy('created_at')
            ->get(['id', 'mesa_id', 'codigo', 'status', 'total', 'created_at', 'nome_cliente', 'forma_pagamento']);

        // Versão simples: hash determinístico do conjunto de pedidos.
        $versao = (int) substr(md5($pedidosPorMesa->toJson().'|'.$lojaId), 0, 8);

        if ($ultimaVersao !== null && $ultimaVersao === $versao) {
            return response()->json(['versao' => $versao, 'sem_mudancas' => true]);
        }

        $pedidosAgrupados = $pedidosPorMesa->groupBy('mesa_id');

        $dados = $mesas->map(function (Mesa $mesa) use ($pedidosAgrupados) {
            $pedidos = $pedidosAgrupados->get($mesa->id, collect());

            $temNovo = $pedidos->contains('status', 'novo');
            $temPreparo = $pedidos->contains('status', 'em_preparo');

            $itens = $pedidos->sum(fn ($p) => $p->itens_count ?? 0);

            return [
                'id' => $mesa->id,
                'nome' => $mesa->nome ?: ($mesa->codigo ?: ('Mesa #'.$mesa->id)),
                'capacidade' => $mesa->capacidade,
                'pedidos' => $pedidos->map(fn ($p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'status' => $p->status,
                    'total' => (float) $p->total,
                    'cliente' => $p->nome_cliente,
                    'pagamento' => $p->forma_pagamento,
                    'quando' => optional($p->created_at)->format('H:i'),
                ])->values(),
                'estado' => $temNovo ? 'novo' : ($temPreparo ? 'em_preparo' : ($pedidos->isNotEmpty() ? 'em_entrega' : 'livre')),
                'total' => (float) $pedidos->sum('total'),
            ];
        })->values();

        return response()->json([
            'versao' => $versao,
            'sem_mudancas' => false,
            'mesas' => $dados,
        ]);
    }

    /**
     * Detalhe completo de UMA mesa (JSON): pedidos em aberto com itens e
     * complementos. É chamado quando o atendente abre o modal da mesa —
     * o polling permanece leve (só status).
     */
    public function detalhe(Mesa $mesa): JsonResponse
    {
        $mesa->load([]);

        $pedidos = Pedido::query()
            ->where('loja_id', loja_atual_id())
            ->where('mesa_id', $mesa->id)
            ->whereIn('status', ['novo', 'em_preparo', 'em_entrega'])
            ->with(['itens' => fn ($q) => $q->orderBy('id')])
            ->orderBy('created_at')
            ->get();

        $temNovo = $pedidos->contains('status', 'novo');
        $temPreparo = $pedidos->contains('status', 'em_preparo');

        return response()->json([
            'mesa' => [
                'id' => $mesa->id,
                'nome' => $mesa->nome ?: ($mesa->codigo ?: ('Mesa #'.$mesa->id)),
                'capacidade' => $mesa->capacidade,
                'estado' => $temNovo ? 'novo' : ($temPreparo ? 'em_preparo' : ($pedidos->isNotEmpty() ? 'em_entrega' : 'livre')),
                'total' => (float) $pedidos->sum('total'),
                'pedidos' => $pedidos->map(fn ($p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'status' => $p->status,
                    'total' => (float) $p->total,
                    'cliente' => $p->nome_cliente,
                    'pagamento' => $p->forma_pagamento,
                    'observacoes' => $p->observacoes,
                    'quando' => optional($p->created_at)->format('H:i'),
                    'itens' => $p->itens->map(fn ($i) => [
                        'nome' => $i->nome_produto,
                        'quantidade' => (int) $i->quantidade,
                        'preco' => (float) $i->preco_unitario,
                        'complementos' => collect($i->complementos ?? [])
                            ->map(fn ($c) => trim($c['nome'] ?? ''))
                            ->filter()
                            ->values(),
                        'subtotal' => $i->subtotal(),
                    ])->values(),
                ])->values(),
            ],
        ]);
    }
}
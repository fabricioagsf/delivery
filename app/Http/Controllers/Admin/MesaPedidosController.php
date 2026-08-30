<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Models\ProdutoComplemento;
use Fabricioagsf\ItemVenda\Complemento;
use Fabricioagsf\ItemVenda\ComplementoTipo;
use Fabricioagsf\ItemVenda\ItemFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
     * Tela do tablet/garçom: escolhe a mesa e monta o pedido com o cardápio
     * real (produtos ativos + complementos), igual ao cliente pelo QR.
     */
    public function pedir(Mesa $mesa): View
    {
        $categorias = Categoria::query()
            ->where('ativo', true)
            ->with(['produtos' => fn ($q) => $q
                ->ativos()
                ->with('complementosAtivos')
                ->orderBy('nome')
            ])
            ->orderBy('nome')
            ->get()
            ->filter(fn ($categoria) => $categoria->produtos->isNotEmpty());

        $abertos = Pedido::query()
            ->where('loja_id', loja_atual_id())
            ->where('mesa_id', $mesa->id)
            ->whereIn('status', ['novo', 'em_preparo', 'em_entrega'])
            ->get();

        return view('admin.mesa_pedido', [
            'mesa' => $mesa,
            'categorias' => $categorias,
            'abertos' => $abertos,
            'totalAberto' => (float) $abertos->sum('total'),
        ]);
    }

    /**
     * Cria o pedido da mesa pelo tablet/garçom. Revalida tudo no banco
     * (produto ativo + estoque com trava) e guarda o snapshot dos
     * complementos com tipo/nome/preço atuais — nunca preço vindo da tela.
     *
     * @return JsonResponse{mesa, mensagem, pedido}
     */
    public function confirmarPedido(Request $request, Mesa $mesa): JsonResponse
    {
        try {
            $dados = $request->validate([
                'itens' => ['required', 'array', 'min:1'],
                'itens.*.produto_id' => ['required', 'integer'],
                'itens.*.quantidade' => ['required', 'integer', 'min:1', 'max:99'],
                'itens.*.complementos' => ['array'],
                'itens.*.complementos.*' => ['integer'],
                'nome_cliente' => ['nullable', 'string', 'max:120'],
                'observacoes' => ['nullable', 'string', 'max:500'],
                'forma_pagamento' => ['required', Rule::in(CaixaController::FORMAS)],
            ], [
                'itens.required' => texto('admin_mesa_pedido', 'val.sem_itens', 'Escolha ao menos um item.'),
                'itens.*.produto_id.required' => texto('admin_mesa_pedido', 'val.produto_obrigatorio', 'Falta o produto do item.'),
                'itens.*.quantidade.min' => texto('admin_mesa_pedido', 'val.quantidade_invalida', 'Quantidade inválida.'),
                'forma_pagamento.in' => texto('admin_mesa_pedido', 'val.forma_invalida', 'Forma de pagamento inválida.'),
            ], [
                'itens' => texto('admin_mesa_pedido', 'campo.itens', 'Itens'),
                'nome_cliente' => texto('admin_mesa_pedido', 'campo.cliente', 'Nome do cliente'),
                'observacoes' => texto('admin_mesa_pedido', 'campo.observacoes', 'Observações'),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['mensagem' => collect($e->errors())->flatten()->first()], 422);
        }

        $lojaId = loja_atual_id();

        try {
            $pedido = DB::transaction(function () use ($dados, $mesa, $lojaId) {
                $ids = collect($dados['itens'])->pluck('produto_id')->unique()->values()->all();

                $produtos = Produto::query()
                    ->whereIn('id', $ids)
                    ->where('ativo', true)
                    ->with(['estoques' => fn ($q) => $q->where('loja_id', $lojaId)->lockForUpdate()])
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $linhas = [];
                $total = 0.0;

                foreach ($dados['itens'] as $item) {
                    $produto = $produtos[$item['produto_id']] ?? null;

                    if (! $produto || ! $produto->temEstoque($item['quantidade'])) {
                        throw new \RuntimeException(texto('admin_mesa_pedido', 'erro.estoque', 'Um dos itens esgotou no meio do caminho — revise o pedido.'));
                    }

                    $estoqueLoja = $produto->estoqueNaLoja();
                    if ($estoqueLoja && $estoqueLoja->estoque !== null) {
                        $estoqueLoja->decrement('estoque', $item['quantidade']);
                    }

                    $itemVenda = ItemFactory::produto($produto->nome, (float) $produto->preco, (int) $item['quantidade']);

                    $complementosSnap = [];
                    $idsComplementos = collect($item['complementos'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();

                    if (! empty($idsComplementos)) {
                        $doBanco = ProdutoComplemento::whereIn('id', $idsComplementos)->get()->keyBy('id');

                        foreach ($idsComplementos as $idComplemento) {
                            $comp = $doBanco[$idComplemento] ?? null;
                            if (! $comp) {
                                continue;
                            }

                            $objeto = new Complemento(
                                $comp->nome,
                                $comp->ehRemocao() ? ComplementoTipo::REMOCAO : ComplementoTipo::ADICIONAL,
                                (float) $comp->preco
                            );

                            $itemVenda->adicionarComplemento($objeto);
                            $complementosSnap[] = $objeto->toArray() + ['complemento_id' => $comp->id];
                        }
                    }

                    $linhas[] = [
                        'produto' => $produto,
                        'quantidade' => (int) $item['quantidade'],
                        'snapshot_complementos' => $complementosSnap,
                        'subtotal' => $itemVenda->getTotal(),
                    ];

                    $total += $itemVenda->getTotal();
                }

                $pedido = Pedido::create([
                    'loja_id' => $lojaId,
                    'codigo' => $this->gerarCodigo(),
                    'mesa_id' => $mesa->id,
                    'nome_cliente' => trim((string) ($dados['nome_cliente'] ?? '')) ?: $mesa->nome ?: ($mesa->codigo ?: ('Mesa #'.$mesa->id)),
                    'telefone' => '',
                    'tipo_entrega' => 'mesa',
                    'forma_pagamento' => $dados['forma_pagamento'],
                    'subtotal' => round($total, 2),
                    'total' => round($total, 2),
                    'observacoes' => $dados['observacoes'] ?? null,
                    'status' => 'novo',
                ]);

                foreach ($linhas as $linha) {
                    PedidoItem::create([
                        'pedido_id' => $pedido->id,
                        'produto_id' => $linha['produto']->id,
                        'nome_produto' => $linha['produto']->nome,
                        'preco_unitario' => $linha['produto']->preco,
                        'complementos' => $linha['snapshot_complementos'] ?: null,
                        'quantidade' => $linha['quantidade'],
                    ]);
                }

                return $pedido;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['mensagem' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['mensagem' => texto('admin_mesa_pedido', 'erro.geral', 'Não foi possível enviar o pedido — tente de novo.')], 500);
        }

        return response()->json([
            'mensagem' => texto('admin_mesa_pedido', 'sucesso.enviado', 'Pedido enviado para a cozinha!'),
            'pedido' => [
                'id' => $pedido->id,
                'codigo' => $pedido->codigo,
                'total' => (float) $pedido->total,
            ],
        ]);
    }

    private function gerarCodigo(): string
    {
        do {
            $codigo = 'T' . strtoupper(substr(uniqid(), -7));
        } while (Pedido::where('codigo', $codigo)->exists());

        return $codigo;
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
                'estado' => $temNovo
                    ? 'novo'
                    : ($temPreparo
                        ? 'em_preparo'
                        : ($pedidos->isNotEmpty() ? 'em_entrega' : 'livre')),
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
<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Services\Carrinho as CarrinhoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarrinhoController extends Controller
{
    public function __construct(protected CarrinhoService $carrinho) {}

    public function index(): View
    {
        $itens = $this->carrinho->itens();

        if (! empty($itens)) {
            $produtoIds = collect($itens)->pluck('produto.id')->unique()->values();
            $lojaId = loja_atual_id();
            $reloaded = Produto::whereIn('id', $produtoIds)
                ->with(['complementosAtivos', 'estoques' => fn ($q) => $q->when($lojaId, fn ($e) => $e->where('loja_id', $lojaId))])
                ->get()
                ->keyBy('id');

            foreach ($itens as &$item) {
                $item['produto'] = $reloaded[$item['produto']->id] ?? $item['produto'];
            }
            unset($item);
        }

        return view('carrinho.index', [
            'itens' => $itens,
            'subtotal' => $this->carrinho->subtotal(),
        ]);
    }

    public function adicionar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'produto_id' => ['required', 'integer'],
            'quantidade' => ['nullable', 'integer', 'min:1', 'max:99'],
            'complementos' => ['nullable', 'array'],
            'complementos.*' => ['integer'],
        ]);

        $produto = Produto::ativos()->with('complementosAtivos')->find($dados['produto_id']);

        if (! $produto) {
            return response()->json([
                'mensagem' => texto('carrinho', 'erro.produto_indisponivel', 'Produto indisponível.'),
            ], 404);
        }

        $quantidade = (int) ($dados['quantidade'] ?? 1);

        // Complementos escolhidos devem pertencer a este produto e estar ativos
        $idsEscolhidos = array_unique(array_map('intval', $dados['complementos'] ?? []));
        $complementos = $produto->complementosAtivos->whereIn('id', $idsEscolhidos)->values();

        if (count($idsEscolhidos) !== count($complementos)) {
            return response()->json([
                'mensagem' => texto('carrinho', 'erro.complemento_invalido', 'Uma das personalizações escolhidas não está mais disponível.'),
            ], 422);
        }

        $snapshot = $complementos->map(fn ($c) => [
            'complemento_id' => $c->id,
            'tipo' => $c->tipo,
            'nome' => $c->nome,
            'preco' => (float) $c->preco,
        ])->values()->all();

        $noCarrinho = $this->carrinho->quantidadeDe($produto->id);
        $totalDesejado = $noCarrinho + $quantidade;

        $estoqueLoja = $produto->estoqueNaLoja();
        $qtdEstoque = $estoqueLoja?->estoque;

        if (! $produto->temEstoque($totalDesejado)) {
            $motivo = $produto->semQuantidade()
                ? texto('carrinho', 'erro.indisponivel', 'Este item está indisponível no momento.')
                : ($produto->esgotado()
                    ? texto('carrinho', 'erro.esgotado', 'Ops! Este doce esgotou.')
                    : str_replace(':disponivel', (string) max(0, ($qtdEstoque ?? 0) - $noCarrinho),
                        texto('carrinho', 'erro.estoque_insuficiente', 'Estoque insuficiente. Restam :disponivel.')));

            return response()->json([
                'mensagem' => $motivo,
                // Gatilho para o comprador: valores mudaram — atualiza a vitrine dele
                'atualizar_vitrine' => true,
                'produto' => [
                    'id' => $produto->id,
                    'nome' => $produto->nome,
                    'preco' => preco_br($produto->preco),
                    'estoque' => $qtdEstoque,
                ],
            ], 422);
        }

        $this->carrinho->adicionar($produto, $quantidade, $snapshot);

        return response()->json([
            'mensagem' => texto('carrinho', 'sucesso.adicionado', 'Adicionado ao carrinho!'),
            'contagem' => $this->carrinho->contagem(),
            'subtotal' => preco_br($this->carrinho->subtotal()),
        ]);
    }

    public function atualizar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'chave' => ['required', 'string'],
            'quantidade' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $itens = $this->carrinho->itens();
        $linha = collect($itens)->firstWhere('chave', $dados['chave']);

        if ($dados['quantidade'] > 0) {
            if (! $linha) {
                return response()->json([
                    'mensagem' => texto('carrinho', 'erro.produto_indisponivel', 'Produto indisponível.'),
                ], 404);
            }

            $produto = $linha['produto'];

            if (! $produto->temEstoque((int) $dados['quantidade'])) {
                return response()->json([
                    'mensagem' => texto('carrinho', 'erro.estoque_insuficiente', 'Estoque insuficiente.'),
                ], 422);
            }
        }

        $this->carrinho->atualizar($dados['chave'], (int) $dados['quantidade']);

        return response()->json([
            'contagem' => $this->carrinho->contagem(),
            'subtotal' => preco_br($this->carrinho->subtotal()),
            'vazio' => $this->carrinho->contagem() === 0,
        ]);
    }

    public function remover(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'chave' => ['required', 'string'],
        ]);

        $this->carrinho->remover($dados['chave']);

        return response()->json([
            'mensagem' => texto('carrinho', 'sucesso.removido', 'Item removido.'),
            'contagem' => $this->carrinho->contagem(),
            'subtotal' => preco_br($this->carrinho->subtotal()),
            'vazio' => $this->carrinho->contagem() === 0,
        ]);
    }
}

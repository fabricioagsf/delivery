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
        return view('carrinho.index', [
            'itens' => $this->carrinho->itens(),
            'subtotal' => $this->carrinho->subtotal(),
        ]);
    }

    public function adicionar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'produto_id' => ['required', 'integer'],
            'quantidade' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $produto = Produto::ativos()->find($dados['produto_id']);

        if (! $produto) {
            return response()->json([
                'mensagem' => texto('carrinho', 'erro.produto_indisponivel', 'Produto indisponível.'),
            ], 404);
        }

        $quantidade = (int) ($dados['quantidade'] ?? 1);
        $noCarrinho = $this->carrinho->quantidadeDe($produto->id);
        $totalDesejado = $noCarrinho + $quantidade;

        if (! $produto->temEstoque($totalDesejado)) {
            $motivo = $produto->semQuantidade()
                ? texto('carrinho', 'erro.indisponivel', 'Este item está indisponível no momento.')
                : ($produto->esgotado()
                    ? texto('carrinho', 'erro.esgotado', 'Ops! Este doce esgotou.')
                    : str_replace(':disponivel', (string) max(0, $produto->estoque - $noCarrinho),
                        texto('carrinho', 'erro.estoque_insuficiente', 'Estoque insuficiente. Restam :disponivel.')));

            return response()->json([
                'mensagem' => $motivo,
                // Gatilho para o comprador: valores mudaram — atualiza a vitrine dele
                'atualizar_vitrine' => true,
                'produto' => [
                    'id' => $produto->id,
                    'nome' => $produto->nome,
                    'preco' => preco_br($produto->preco),
                    'estoque' => $produto->estoque,
                ],
            ], 422);
        }

        $this->carrinho->adicionar($produto, $quantidade);

        return response()->json([
            'mensagem' => texto('carrinho', 'sucesso.adicionado', 'Adicionado ao carrinho!'),
            'contagem' => $this->carrinho->contagem(),
            'subtotal' => preco_br($this->carrinho->subtotal()),
        ]);
    }

    public function atualizar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'produto_id' => ['required', 'integer'],
            'quantidade' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        if ($dados['quantidade'] > 0) {
            $produto = Produto::ativos()->find($dados['produto_id']);

            if (! $produto || ! $produto->temEstoque((int) $dados['quantidade'])) {
                return response()->json([
                    'mensagem' => texto('carrinho', 'erro.estoque_insuficiente', 'Estoque insuficiente.'),
                ], 422);
            }
        }

        $this->carrinho->atualizar((int) $dados['produto_id'], (int) $dados['quantidade']);

        return response()->json([
            'contagem' => $this->carrinho->contagem(),
            'subtotal' => preco_br($this->carrinho->subtotal()),
            'vazio' => $this->carrinho->contagem() === 0,
        ]);
    }

    public function remover(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'produto_id' => ['required', 'integer'],
        ]);

        $this->carrinho->remover((int) $dados['produto_id']);

        return response()->json([
            'mensagem' => texto('carrinho', 'sucesso.removido', 'Item removido.'),
            'contagem' => $this->carrinho->contagem(),
            'subtotal' => preco_br($this->carrinho->subtotal()),
            'vazio' => $this->carrinho->contagem() === 0,
        ]);
    }
}

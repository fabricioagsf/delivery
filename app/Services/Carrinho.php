<?php

namespace App\Services;

use App\Models\Produto;
use Fabricioagsf\ItemVenda\ItemFactory;

class Carrinho
{
    protected const CHAVE_SESSAO = 'carrinho';

    public function adicionar(Produto $produto, int $quantidade = 1): void
    {
        $carrinho = session(self::CHAVE_SESSAO, []);
        $atual = $carrinho[$produto->id] ?? null;
        $quantidadeAnterior = is_array($atual) ? (int) $atual['qtd'] : (int) $atual;

        $carrinho[$produto->id] = [
            'qtd' => $quantidadeAnterior + max(1, $quantidade),
            // Guarda o preço da época para poder avisar se mudou até o pagamento
            'preco_adicionado' => $produto->preco,
        ];

        session([self::CHAVE_SESSAO => $carrinho]);
    }

    public function atualizar(int $produtoId, int $quantidade): void
    {
        $carrinho = session(self::CHAVE_SESSAO, []);

        if ($quantidade <= 0) {
            unset($carrinho[$produtoId]);
        } else {
            $atual = $carrinho[$produtoId] ?? null;
            $carrinho[$produtoId] = [
                'qtd' => $quantidade,
                'preco_adicionado' => is_array($atual) ? ($atual['preco_adicionado'] ?? null) : null,
            ];
        }

        session([self::CHAVE_SESSAO => $carrinho]);
    }

    public function remover(int $produtoId): void
    {
        $carrinho = session(self::CHAVE_SESSAO, []);
        unset($carrinho[$produtoId]);

        session([self::CHAVE_SESSAO => $carrinho]);
    }

    public function limpar(): void
    {
        session()->forget(self::CHAVE_SESSAO);
    }

    public function quantidadeDe(int $produtoId): int
    {
        $atual = session(self::CHAVE_SESSAO, [])[$produtoId] ?? 0;

        return is_array($atual) ? (int) $atual['qtd'] : (int) $atual;
    }

    /**
     * Itens do carrinho com preços SEMPRE revalidados no banco.
     * Marca os itens cujo valor mudou desde que foram adicionados —
     * o pagamento usa sempre o preço atual do banco, nunca o antigo.
     */
    public function itens(): array
    {
        $carrinho = session(self::CHAVE_SESSAO, []);
        if (empty($carrinho)) {
            return [];
        }

        $produtos = Produto::ativos()
            ->whereIn('id', array_keys($carrinho))
            ->get()
            ->keyBy('id');

        $itens = [];
        foreach ($carrinho as $produtoId => $entrada) {
            if (! isset($produtos[$produtoId])) {
                continue;
            }

            $produto = $produtos[$produtoId];
            $quantidade = is_array($entrada) ? (int) $entrada['qtd'] : (int) $entrada;
            $precoAdicionado = is_array($entrada) ? ($entrada['preco_adicionado'] ?? null) : null;

            // Subtotal do item calculado pela lib item-venda (Produto: preco x quantidade)
            $itemVenda = ItemFactory::produto($produto->nome, (float) $produto->preco, $quantidade);

            $itens[] = [
                'produto' => $produto,
                'quantidade' => $quantidade,
                'subtotal' => $itemVenda->getSubtotal(),
                'preco_adicionado' => $precoAdicionado,
                'preco_mudou' => $precoAdicionado !== null && (float) $precoAdicionado !== (float) $produto->preco,
            ];
        }

        return $itens;
    }

    public function contagem(): int
    {
        return array_sum(array_map(
            fn ($entrada) => is_array($entrada) ? (int) $entrada['qtd'] : (int) $entrada,
            session(self::CHAVE_SESSAO, [])
        ));
    }

    public function subtotal(): float
    {
        return array_sum(array_column($this->itens(), 'subtotal'));
    }

    /**
     * Algum item mudou de preço desde que foi adicionado?
     */
    public function temMudancaDePreco(): bool
    {
        foreach ($this->itens() as $item) {
            if ($item['preco_mudou']) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Services;

use App\Models\Produto;
use App\Models\ProdutoComplemento;
use Fabricioagsf\ItemVenda\Complemento;
use Fabricioagsf\ItemVenda\ComplementoTipo;
use Fabricioagsf\ItemVenda\ItemFactory;

class Carrinho
{
    protected const CHAVE_SESSAO = 'carrinho_linhas';

    /**
     * Chave estável de uma linha: produto + personalizações escolhidas.
     * A mesma gostosura com complementos diferentes vira uma linha própria.
     */
    protected function chaveLinha(int $produtoId, array $complementos = []): string
    {
        $ids = collect($complementos)->pluck('complemento_id')->sort()->values()->all();

        return md5(json_encode([$produtoId, $ids]));
    }

    public function adicionar(Produto $produto, int $quantidade = 1, array $complementos = []): void
    {
        $chave = $this->chaveLinha($produto->id, $complementos);
        $carrinho = session(self::CHAVE_SESSAO, []);
        $atual = $carrinho[$chave] ?? null;

        $carrinho[$chave] = [
            'produto_id' => $produto->id,
            'qtd' => (is_array($atual) ? (int) $atual['qtd'] : 0) + max(1, $quantidade),
            // Preços da época para avisar se mudaram até o pagamento
            'preco_adicionado' => $produto->preco,
            // Snapshot das personalizações escolhidas (descrição + preço no momento)
            'complementos' => $complementos,
        ];

        session([self::CHAVE_SESSAO => $carrinho]);
    }

    public function atualizar(string $chave, int $quantidade): void
    {
        $carrinho = session(self::CHAVE_SESSAO, []);
        if (! isset($carrinho[$chave])) {
            return;
        }

        if ($quantidade <= 0) {
            unset($carrinho[$chave]);
        } else {
            $carrinho[$chave]['qtd'] = $quantidade;
        }

        session([self::CHAVE_SESSAO => $carrinho]);
    }

    public function remover(string $chave): void
    {
        $carrinho = session(self::CHAVE_SESSAO, []);
        unset($carrinho[$chave]);

        session([self::CHAVE_SESSAO => $carrinho]);
    }

    public function limpar(): void
    {
        session()->forget(self::CHAVE_SESSAO);
    }

    /**
     * Quantidade total de um produto somando todas as linhas (todas as
     * combinações de personalização). Usada no controle de estoque.
     */
    public function quantidadeDe(int $produtoId): int
    {
        $total = 0;
        foreach (session(self::CHAVE_SESSAO, []) as $entrada) {
            if ((int) $entrada['produto_id'] === $produtoId) {
                $total += (int) $entrada['qtd'];
            }
        }

        return $total;
    }

    /**
     * Itens do carrinho com preços SEMPRE revalidados no banco (regra de ouro).
     * Soma os complementos da linha pelo preço atual de cada personalização;
     * se a personalização sumiu do catálogo, usa o valor congelado no momento.
     */
    public function itens(): array
    {
        $carrinho = session(self::CHAVE_SESSAO, []);
        if (empty($carrinho)) {
            return [];
        }

        $produtos = Produto::ativos()
            ->whereIn('id', collect($carrinho)->pluck('produto_id')->all())
            ->get()
            ->keyBy('id');

        $itens = [];
        foreach ($carrinho as $chave => $entrada) {
            $produtoId = (int) $entrada['produto_id'];
            if (! isset($produtos[$produtoId])) {
                continue;
            }

            $produto = $produtos[$produtoId];
            $quantidade = (int) $entrada['qtd'];
            $precoAdicionado = $entrada['preco_adicionado'] ?? null;
            $snapshot = $entrada['complementos'] ?? [];

            $itemVenda = ItemFactory::produto($produto->nome, (float) $produto->preco, $quantidade);

            // Complementos do banco (preço atual) indexados por id
            $ids = collect($snapshot)->pluck('complemento_id')->filter()->all();
            $doBanco = empty($ids)
                ? collect()
                : ProdutoComplemento::whereIn('id', $ids)->get()->keyBy('id');

            $complementosEscolhidos = [];
            foreach ($snapshot as $c) {
                $complementoId = $c['complemento_id'] ?? null;
                $atual = $complementoId ? ($doBanco[$complementoId] ?? null) : null;

                $tipo = $atual?->ehRemocao() ? ComplementoTipo::REMOCAO
                    : ($atual?->ehAdicional() ? ComplementoTipo::ADICIONAL
                        : ComplementoTipo::tryFrom((string) ($c['tipo'] ?? 'adicional')) ?? ComplementoTipo::ADICIONAL);
                $nome = $atual?->nome ?? (string) ($c['nome'] ?? '');
                $preco = $atual ? (float) $atual->preco : (float) ($c['preco'] ?? 0);

                $compObj = new Complemento($nome, $tipo, $preco);
                $itemVenda->adicionarComplemento($compObj);
                $complementosEscolhidos[] = $compObj->toArray();
            }

            // Um complemento mudou de preço desde que foi adicionado?
            $precoCompAdicionado = $this->valorComplementosSnapshot($snapshot);
            $precoCompAtual = $itemVenda->getValorComplementos();

            $itens[] = [
                'chave' => $chave,
                'produto' => $produto,
                'quantidade' => $quantidade,
                'subtotal' => $itemVenda->getTotal(),
                'complementos' => $complementosEscolhidos,
                'valor_complementos' => $precoCompAtual,
                'preco_adicionado' => $precoAdicionado,
                'preco_mudou' => $precoAdicionado !== null && (float) $precoAdicionado !== (float) $produto->preco,
                'preco_complementos_mudou' => abs($precoCompAdicionado - $precoCompAtual) > 0.004,
            ];
        }

        return $itens;
    }

    protected function valorComplementosSnapshot(array $snapshot): float
    {
        return round(collect($snapshot)->reduce(function ($soma, $c) {
            $tipo = (string) ($c['tipo'] ?? 'adicional');

            return $soma + ($tipo === 'adicional' ? (float) ($c['preco'] ?? 0) : 0.0);
        }, 0.0), 2);
    }

    public function contagem(): int
    {
        return array_sum(array_map(
            fn ($entrada) => (int) $entrada['qtd'],
            session(self::CHAVE_SESSAO, [])
        ));
    }

    public function subtotal(): float
    {
        return array_sum(array_column($this->itens(), 'subtotal'));
    }

    /**
     * Algum item mudou de preço (base ou complemento) desde que foi adicionado?
     */
    public function temMudancaDePreco(): bool
    {
        foreach ($this->itens() as $item) {
            if ($item['preco_mudou'] || $item['preco_complementos_mudou']) {
                return true;
            }
        }

        return false;
    }
}

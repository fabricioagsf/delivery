<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mercado Pago — Checkout Pro (redirecionamento).
 *
 * O cliente é levado à tela do Mercado Pago e paga com cartão (ou Pix/saldo)
 * lá dentro: NENHUM dado de cartão passa pelo nosso servidor. A confirmação
 * chega pelo webhook e também é conferida na tela do pedido.
 *
 * Credenciais na tabela configuracoes: mercadopago_ativo + mercadopago_access_token.
 */
class MercadoPago
{
    private const API = 'https://api.mercadopago.com';

    public function disponivel(): bool
    {
        return config_loja('mercadopago_ativo') === '1'
            && trim((string) config_loja('mercadopago_access_token')) !== '';
    }

    public function emSandbox(): bool
    {
        return str_starts_with(trim((string) config_loja('mercadopago_access_token')), 'TEST-');
    }

    /**
     * Cria a preferência de pagamento e devolve a URL de Checkout Pro.
     *
     * @return array{url: string, preferencia_id: string}
     *
     * @throws \RuntimeException com mensagem humana quando falha
     */
    public function criarPreferencia(Pedido $pedido): array
    {
        $token = trim((string) config_loja('mercadopago_access_token'));

        try {
            $resposta = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->post(self::API.'/checkout/preferences', [
                    'items' => $pedido->itens->map(fn ($item) => [
                        'title' => $item->nome_produto,
                        'quantity' => (int) $item->quantidade,
                        'unit_price' => (float) $item->preco_unitario,
                        'currency_id' => 'BRL',
                    ])->values()->all(),
                    'payer' => [
                        'name' => $pedido->nome_cliente,
                        'email' => $pedido->email ?? '',
                    ],
                    'external_reference' => $pedido->codigo,
                    'back_urls' => [
                        'success' => route('pedido.confirmacao', $pedido->codigo),
                        'pending' => route('pedido.confirmacao', $pedido->codigo),
                        'failure' => route('pedido.confirmacao', $pedido->codigo),
                    ],
                    'auto_return' => 'approved',
                    'notification_url' => route('webhook.mercadopago'),
                ]);
        } catch (\Throwable) {
            throw new \RuntimeException(texto('pagamentos', 'mp.erro_conexao', 'Não foi possível falar com o Mercado Pago agora — tente de novo.'));
        }

        if (! $resposta->successful() || empty($resposta->json('init_point'))) {
            Log::warning('Mercado Pago: preferência recusada.', [
                'pedido' => $pedido->codigo,
                'status' => $resposta->status(),
                'resposta' => $resposta->json(),
            ]);

            throw new \RuntimeException(texto('pagamentos', 'mp.erro_preferencia', 'O Mercado Pago recusou o pagamento — confira as credenciais em Configurações.'));
        }

        return [
            'url' => (string) $resposta->json('init_point'),
            'preferencia_id' => (string) $resposta->json('id'),
        ];
    }

    /**
     * Consulta o pagamento mais recente do pedido pelo external_reference.
     *
     * @return array{status: string, pagamento_id: string}|null
     */
    public function consultarPorReferencia(string $codigo): ?array
    {
        $token = trim((string) config_loja('mercadopago_access_token'));

        try {
            $resposta = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->get(self::API.'/v1/payments/search', [
                    'external_reference' => $codigo,
                    'sort' => 'date_created',
                    'criteria' => 'desc',
                    'limit' => 1,
                ]);
        } catch (\Throwable) {
            return null;
        }

        $resultado = $resposta->json('results.0');

        if (! $resultado) {
            return null;
        }

        return [
            'status' => (string) ($resultado['status'] ?? 'pending'),
            'pagamento_id' => (string) ($resultado['id'] ?? ''),
        ];
    }

    /**
     * Busca um pagamento pelo id (webhook) — null quando não existe.
     *
     * @return array{status: string, external_reference: string}|null
     */
    public function buscarPagamento(string $pagamentoId): ?array
    {
        $token = trim((string) config_loja('mercadopago_access_token'));

        try {
            $resposta = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->get(self::API.'/v1/payments/'.$pagamentoId);
        } catch (\Throwable) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        return [
            'status' => (string) $resposta->json('status'),
            'external_reference' => (string) $resposta->json('external_reference'),
        ];
    }
}

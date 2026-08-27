<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Efí (Gerencianet) — Pix API v2: cobrança imediata com copia e cola.
 *
 * O txid da cobrança é o código do pedido (idempotente: chamar de novo
 * devolve a mesma cobrança). O status é conferido na tela do pedido e
 * atualizado pelo webhook.
 *
 * Credenciais na tabela configuracoes: efi_ativo + efi_client_id +
 * efi_client_secret + efi_pix_chave (+ efi_sandbox para homologação).
 */
class Efi
{
    public function disponivel(): bool
    {
        return config_loja('efi_ativo') === '1'
            && trim((string) config_loja('efi_client_id')) !== ''
            && trim((string) config_loja('efi_client_secret')) !== ''
            && trim((string) config_loja('efi_pix_chave')) !== '';
    }

    private function base(): string
    {
        return config_loja('efi_sandbox') === '1'
            ? 'https://pix.api-hmg.efi.com.br'
            : 'https://pix.api.efi.com.br';
    }

    /**
     * Token OAuth (client_credentials) — cacheado por 50 minutos.
     */
    private function token(): string
    {
        $cache = cache()->get('efi_pix_token');

        if (is_string($cache) && $cache !== '') {
            return $cache;
        }

        try {
            $resposta = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->withBasicAuth(
                    trim((string) config_loja('efi_client_id')),
                    trim((string) config_loja('efi_client_secret'))
                )
                ->post($this->base().'/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'scope' => 'cob.write cob.read webhook.write webhook.read',
                ]);
        } catch (\Throwable) {
            throw new \RuntimeException(texto('pagamentos', 'efi.erro_conexao', 'Não foi possível falar com a Efí agora — tente de novo.'));
        }

        $token = (string) $resposta->json('access_token');

        if (! $resposta->successful() || $token === '') {
            Log::warning('Efí: OAuth recusado.', [
                'status' => $resposta->status(),
                'resposta' => $resposta->json(),
            ]);

            throw new \RuntimeException(texto('pagamentos', 'efi.erro_token', 'A Efí recusou as credenciais — confira em Configurações.'));
        }

        cache()->put('efi_pix_token', $token, now()->addMinutes(50));

        return $token;
    }

    private function txid(Pedido $pedido): string
    {
        // txid Efí: 26–35 caracteres alfanuméricos; DOC-XXXXXXXX (12) precisa completar
        return str_pad(preg_replace('/[^A-Za-z0-9]/', '', $pedido->codigo), 26, 'G0', STR_PAD_RIGHT);
    }

    /**
     * Cria (ou devolve a existente) a cobrança Pix do pedido.
     *
     * @return array{txid: string, copia_e_cola: string, status: string}
     *
     * @throws \RuntimeException com mensagem humana quando falha
     */
    public function criarCobranca(Pedido $pedido): array
    {
        $txid = $this->txid($pedido);

        // Idempotente: PUT na mesma cobrança devolve a existente
        try {
            $resposta = Http::withToken($this->token())
                ->acceptJson()
                ->timeout(20)
                ->put($this->base().'/v2/cob/'.$txid, [
                    'calendario' => ['expiracao' => 86400],
                    'devedor' => [
                        'nome' => mb_substr($pedido->nome_cliente, 0, 200),
                    ],
                    'valor' => ['original' => number_format((float) $pedido->total, 2, '.', '')],
                    'chave' => trim((string) config_loja('efi_pix_chave')),
                    'solicitacaoPagador' => mb_substr('Pedido '.$pedido->codigo.' — Gostosuras', 0, 140),
                ]);
        } catch (\Throwable) {
            throw new \RuntimeException(texto('pagamentos', 'efi.erro_conexao', 'Não foi possível falar com a Efí agora — tente de novo.'));
        }

        if (! $resposta->successful() || empty($resposta->json('pixCopiaECola'))) {
            Log::warning('Efí: cobrança Pix recusada.', [
                'pedido' => $pedido->codigo,
                'status' => $resposta->status(),
                'resposta' => $resposta->json(),
            ]);

            throw new \RuntimeException(texto('pagamentos', 'efi.erro_cobranca', 'A Efí recusou a cobrança Pix — confira as credenciais e a chave Pix em Configurações.'));
        }

        return [
            'txid' => (string) $resposta->json('txid'),
            'copia_e_cola' => (string) $resposta->json('pixCopiaECola'),
            'status' => (string) $resposta->json('status'),
        ];
    }

    /**
     * Consulta a cobrança pelo txid — null quando não existe/indisponível.
     */
    public function consultarCobranca(string $txid): ?array
    {
        try {
            $resposta = Http::withToken($this->token())
                ->acceptJson()
                ->timeout(15)
                ->get($this->base().'/v2/cob/'.$txid);
        } catch (\Throwable) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        return [
            'status' => (string) $resposta->json('status'),
            'txid' => (string) $resposta->json('txid'),
        ];
    }
}

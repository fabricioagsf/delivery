<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * WhatsApp Cloud API (Meta) — envio 100% server-side, sem abrir janelas.
 *
 * Dentro da janela de atendimento de 24h do cliente, texto livre é aceito.
 * Fora dela, a Meta rejeita (exige template aprovado) — nesse caso o painel
 * cai no modo link (wa.me) como fallback.
 *
 * Credenciais ficam na tabela configuracoes (editáveis no painel):
 *   whatsapp_ativo   — '1' para usar a API
 *   whatsapp_token   — token permanente do app Meta
 *   whatsapp_phone_id— Phone Number ID do número de negócio
 */
class WhatsApp
{
    private const API = 'https://graph.facebook.com/v21.0/';

    public function disponivel(): bool
    {
        return config_loja('whatsapp_ativo') === '1'
            && trim((string) config_loja('whatsapp_token')) !== ''
            && trim((string) config_loja('whatsapp_phone_id')) !== '';
    }

    /**
     * @return array{ok: bool, erro: ?string}
     */
    public function enviarTexto(string $telefone, string $mensagem): array
    {
        $digitos = preg_replace('/\D/', '', $telefone);

        if (strlen($digitos) <= 11) {
            $digitos = '55'.$digitos;
        }

        try {
            $resposta = Http::withToken(trim((string) config_loja('whatsapp_token')))
                ->acceptJson()
                ->timeout(15)
                ->post(self::API.trim((string) config_loja('whatsapp_phone_id')).'/messages', [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $digitos,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $mensagem,
                    ],
                ]);
        } catch (\Throwable) {
            return [
                'ok' => false,
                'erro' => texto('whatsapp', 'erro.conexao', 'Não foi possível conectar ao WhatsApp agora — tente de novo.'),
            ];
        }

        if ($resposta->successful()) {
            return ['ok' => true, 'erro' => null];
        }

        $codigo = (int) ($resposta->json('error.error_subcode') ?? $resposta->json('error.code') ?? 0);

        $erro = match (true) {
            $resposta->status() === 401,
            $codigo === 190 => texto('whatsapp', 'erro.token', 'Token do WhatsApp inválido ou expirado — confira em Configurações.'),
            $codigo === 131047 => texto('whatsapp', 'erro.janela', 'Fora da janela de 24h deste cliente — a Meta só aceita template aprovado nesse caso.'),
            $resposta->status() === 404 => texto('whatsapp', 'erro.phone_id', 'Phone Number ID do WhatsApp não encontrado — confira em Configurações.'),
            default => texto('whatsapp', 'erro.falha', 'O WhatsApp recusou o envio para este número.'),
        };

        return ['ok' => false, 'erro' => $erro];
    }
}

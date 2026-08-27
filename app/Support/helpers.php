<?php

use App\Models\Configuracao;
use App\Models\Texto;

if (! function_exists('texto')) {
    /**
     * Retorna um texto da interface a partir da tabela `textos`.
     * O fallback é exibido (e semeado) quando a chave não existe.
     *
     * Resiliente: se o banco estiver indisponível, devolve o fallback
     * em vez de lançar um segundo erro por cima do primeiro.
     */
    function texto(string $pagina, string $chave, string $fallback): string
    {
        try {
            $registro = Texto::query()
                ->where('pagina', $pagina)
                ->where('chave', $chave)
                ->first();

            return $registro?->valor ?? $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }
}

if (! function_exists('config_loja')) {
    /**
     * Retorna uma configuração da loja (tabela `configuracoes`).
     */
    function config_loja(string $chave, ?string $fallback = null): ?string
    {
        return Configuracao::query()
            ->where('chave', $chave)
            ->value('valor') ?? $fallback;
    }
}

if (! function_exists('preco_br')) {
    /**
     * Formata um valor decimal do banco como moeda brasileira.
     */
    function preco_br($valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }
}

if (! function_exists('status_pedido')) {
    /**
     * Rótulo do status do pedido (tabela textos, página 'status').
     */
    function status_pedido(string $status): string
    {
        return texto('status', 'status.'.$status, ucfirst(str_replace('_', ' ', $status)));
    }
}

if (! function_exists('forma_pagamento_label')) {
    /**
     * Rótulo da forma de pagamento (tabela textos, página 'pagamentos').
     */
    function forma_pagamento_label(string $forma): string
    {
        return texto('pagamentos', 'forma.'.$forma, ucfirst($forma));
    }
}

if (! function_exists('detectar_bandeira')) {
    /**
     * Detecta a bandeira de um cartão pelos dígitos iniciais.
     */
    function detectar_bandeira(string $numero): string
    {
        $numero = preg_replace('/\D/', '', $numero);

        $regras = [
            'Visa' => '/^4/',
            'Mastercard' => '/^(5[1-5]|2[2-7])/',
            'American Express' => '/^3[47]/',
            'Elo' => '/^(4011|4312|4389|4514|4576|5041|5066|5067|509|627780|636297|6362|6363|650|6516|6550)/',
            'Hipercard' => '/^(606282|3841)/',
            'Diners Club' => '/^3(?:0[0-5]|[68])/',
            'Discover' => '/^(6011|65|64[4-9])/',
            'Aura' => '/^50/',
        ];

        foreach ($regras as $bandeira => $padrao) {
            if (preg_match($padrao, $numero)) {
                return $bandeira;
            }
        }

        return 'Outro';
    }
}

<?php

namespace App\Support;

/**
 * Serviço de cupons promocionais: valida um código contra um subtotal e
 * aplica (incrementa o contador de usos) quando o pedido é fechado.
 */
class Cupom
{
    /**
     * Tenta resolver e validar um cupom pelo código para um subtotal.
     * Retorna o modelo quando aplicável e null quando não.
     */
    public function resolver(string $codigo, float $subtotal): ?\App\Models\Cupom
    {
        $cupom = \App\Models\Cupom::query()->where('codigo', $codigo)->first();

        if (! $cupom || ! $cupom->aplicavel($subtotal)) {
            return null;
        }

        return $cupom;
    }

    /**
     * Motivo de recusa legível para exibir ao cliente quando o cupom não vale.
     * Devolve null quando o cupom é aplicável.
     */
    public function recusa(string $codigo, float $subtotal): ?string
    {
        $cupom = \App\Models\Cupom::query()->where('codigo', $codigo)->first();

        if (! $cupom) {
            return texto('cupom', 'erro.inexistente', 'Cupom não encontrado.');
        }

        if (! $cupom->ativo) {
            return texto('cupom', 'erro.inativo', 'Este cupom não está mais ativo.');
        }

        if (! $cupom->vigente()) {
            return texto('cupom', 'erro.expirado', 'Este cupom expirou.');
        }

        if (! $cupom->temUsosDisponiveis()) {
            return texto('cupom', 'erro.esgotado', 'Este cupom já foi todo usado.');
        }

        if ($cupom->valor_minimo !== null && $subtotal < $cupom->valor_minimo) {
            return str_replace(
                ':valor',
                preco_br($cupom->valor_minimo),
                texto('cupom', 'erro.minimo', 'Use este cupom em pedidos a partir de :valor.')
            );
        }

        if ($cupom->desconto($subtotal) <= 0) {
            return texto('cupom', 'erro.nulo', 'Este cupom não gera desconto neste pedido.');
        }

        return null;
    }

    /**
     * Registra o uso de um cupom (incrementa o contador). Chamado ao fechar o pedido.
     */
    public function registrarUso(\App\Models\Cupom $cupom): void
    {
        $cupom->increment('usos');
    }
}

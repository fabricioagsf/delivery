<?php

namespace App\Support;

/**
 * Serviço de fidelidade (pontos): converte valor de compra em pontos e
 * pontos em desconto no checkout. Regras configuráveis por chave da tabela
 * `configuracoes`:
 *   - fidelidade_ativo      (1/0) — módulo ligado/desligado
 *   - fidelidade_ganho      pontos ganhos por R$ 1,00 de subtotal (padrão 1)
 *   - fidelidade_ponto_valor valor em R$ de cada ponto no resgate (padrão 0.10
 *                            → 10 pontos = R$ 1,00 de desconto)
 */
class Fidelidade
{
    /** Módulo de fidelidade está ligado? */
    public function ativo(): bool
    {
        return config_loja('fidelidade_ativo', '0') === '1';
    }

    /** Pontos ganhos por R$ 1,00 de subtotal (mínimo 1). */
    public function ganho(): float
    {
        return max((float) (config_loja('fidelidade_ganho', '1') ?: 1), 1);
    }

    /** Valor em R$ de cada ponto no momento do resgate. */
    public function pontoValor(): float
    {
        return max((float) (config_loja('fidelidade_ponto_valor', '0.10') ?: 0.10), 0.01);
    }

    /**
     * Pontos que um pedido de subtotal X rende ao cliente.
     * Base arredondada para baixo: R$ 12,80 com ganho 1 → 12 pontos.
     */
    public function pontosParaPedido(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0;
        }

        return floor($subtotal * $this->ganho());
    }

    /**
     * Quantos pontos são necessários para obter um desconto de $valor.
     */
    public function pontosParaValor(float $valor): float
    {
        $valorPorPonto = $this->pontoValor();

        if ($valorPorPonto <= 0) {
            return 0;
        }

        return round($valor / $valorPorPonto, 2);
    }

    /**
     * Desconto em R$ que $pontos geram no resgate.
     */
    public function descontoDePontos(float $pontos): float
    {
        if ($pontos <= 0) {
            return 0;
        }

        return (float) round($pontos * $this->pontoValor(), 2);
    }

    /**
     * Desconto efetivo para um saldo de $pontos sobre um subtotal:
     * nunca passa do subtotal.
     */
    public function descontoMaximo(float $pontos, float $subtotal): float
    {
        return (float) min($this->descontoDePontos($pontos), max($subtotal, 0));
    }

    /** Saldo de pontos do cliente (ou 0 se não logado). */
    public function saldoDoCliente(): float
    {
        $cliente = auth('cliente')->user();

        return $cliente ? (float) $cliente->pontos : 0;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    // Sem Auditoravel: itens de linha do pedido não poluem o log —
    // o pedido (pagamento) é quem entra na auditoria.
    protected $table = 'pedido_itens';

    protected $fillable = [
        'pedido_id',
        'produto_id',
        'nome_produto',
        'preco_unitario',
        'complementos',
        'quantidade',
    ];

    protected function casts(): array
    {
        return [
            'complementos' => 'array',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Valor dos complementos ADICIONAIS (pagas) para TODAS as unidades da linha.
     */
    public function valorComplementos(): float
    {
        $soma = 0.0;
        foreach ($this->complementos ?? [] as $c) {
            if (($c['tipo'] ?? 'adicional') === 'adicional') {
                $soma += (float) ($c['preco'] ?? 0);
            }
        }

        return round($soma * (int) $this->quantidade, 2);
    }

    /**
     * Subtotal da linha (base + adicionais) considerando a quantidade.
     */
    public function subtotal(): float
    {
        return round(((float) $this->preco_unitario * (int) $this->quantidade) + $this->valorComplementos(), 2);
    }
}

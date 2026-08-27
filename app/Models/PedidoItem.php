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
        'quantidade',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}

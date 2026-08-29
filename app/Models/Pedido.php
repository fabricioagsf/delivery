<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use App\Support\PossuiLoja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use Auditoravel;
    use PossuiLoja;

    protected $fillable = [
        'loja_id',
        'codigo',
        'cliente_id',
        'endereco_id',
        'cartao_id',
        'mesa_id',
        'nome_cliente',
        'telefone',
        'email',
        'tipo_entrega',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'cep',
        'forma_pagamento',
        'troco_para',
        'subtotal',
        'taxa_entrega',
        'total',
        'cupom_id',
        'cupom_codigo',
        'cupom_desconto',
        'pontos_ganhos',
        'pontos_utilizados',
        'pontos_desconto',
        'observacoes',
        'status',
        'pagamento_status',
        'pagamento_id',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function notas(): HasMany
    {
        return $this->hasMany(NotaFiscal::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }
}

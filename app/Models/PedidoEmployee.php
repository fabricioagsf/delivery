<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoEmployee extends Model
{
    protected $table = 'pedido_employees';

    protected $fillable = [
        'pedido_id',
        'employee_id',
        'comissao_valor',
        'registrado_em',
    ];

    protected $casts = [
        'registrado_em' => 'datetime',
        'comissao_valor' => 'decimal:2',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Saas\Employee::class, 'employee_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Fabricioagsf\AuthMulti\Models\Tenant as Loja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoEstoque extends Model
{
    use Auditoravel;

    protected $fillable = [
        'produto_id',
        'loja_id',
        'estoque',
        'estoque_minimo',
    ];

    protected function casts(): array
    {
        return [
            'estoque' => 'integer',
            'estoque_minimo' => 'integer',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    use Auditoravel;

    protected $fillable = [
        'categoria_id',
        'nome',
        'slug',
        'descricao',
        'preco',
        'estoque',
        'estoque_minimo',
        'imagem',
        'destaque',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'destaque' => 'boolean',
            'ativo' => 'boolean',
            'preco' => 'float',
            'estoque' => 'integer',
            'estoque_minimo' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function complementos(): HasMany
    {
        return $this->hasMany(ProdutoComplemento::class)->orderBy('ordem')->orderBy('id');
    }

    public function complementosAtivos(): HasMany
    {
        return $this->complementos()->where('ativo', true);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Regra de venda: o produto só é vendido com quantidade maior que zero.
     * Sem quantidade definida (null) o produto NÃO está à venda.
     */
    public function temEstoque(int $quantidade = 1): bool
    {
        return $this->estoque !== null
            && $this->estoque > 0
            && $this->estoque >= $quantidade;
    }

    /**
     * Indisponível para venda: sem quantidade definida ou zerado.
     */
    public function esgotado(): bool
    {
        return $this->estoque === null || $this->estoque <= 0;
    }

    /**
     * Sem quantidade definida (diferente de esgotado de verdade).
     */
    public function semQuantidade(): bool
    {
        return $this->estoque === null;
    }
}

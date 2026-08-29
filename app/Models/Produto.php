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

    public function estoques(): HasMany
    {
        return $this->hasMany(ProdutoEstoque::class);
    }

    /**
     * Estoque do produto para a loja ativa (ou null quando não há registro
     * para a loja — produto sem controle de estoque na loja).
     */
    public function estoqueNaLoja(?int $lojaId = null): ?ProdutoEstoque
    {
        $lojaId ??= loja_atual_id();

        if ($lojaId === null) {
            return null;
        }

        return $this->estoques()->where('loja_id', $lojaId)->first();
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Regra de venda: o produto só é vendido com quantidade maior que zero.
     * Sem quantidade definida (null) o produto NÃO está à venda na loja.
     */
    public function temEstoque(int $quantidade = 1): bool
    {
        $estoque = $this->estoqueNaLoja();

        if ($estoque === null) {
            return false;
        }

        $qtd = $estoque->estoque;

        return $qtd !== null && $qtd > 0 && $qtd >= $quantidade;
    }

    /**
     * Indisponível para venda na loja ativa: sem quantidade definida ou zerado.
     */
    public function esgotado(): bool
    {
        $estoque = $this->estoqueNaLoja();

        if ($estoque === null) {
            return true;
        }

        $qtd = $estoque->estoque;

        return $qtd === null || $qtd <= 0;
    }

    /**
     * Sem quantidade definida (diferente de esgotado de verdade).
     */
    public function semQuantidade(): bool
    {
        $estoque = $this->estoqueNaLoja();

        return $estoque === null || $estoque->estoque === null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoComplemento extends Model
{
    public const TIPOS = ['adicional', 'remocao'];

    protected $fillable = [
        'produto_id',
        'tipo',
        'nome',
        'preco',
        'ativo',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'float',
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function ehAdicional(): bool
    {
        return $this->tipo === 'adicional';
    }

    public function ehRemocao(): bool
    {
        return $this->tipo === 'remocao';
    }
}

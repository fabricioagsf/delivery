<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Endereco extends Model
{
    use Auditoravel;

    protected $fillable = [
        'cliente_id',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'cep',
        'principal',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}

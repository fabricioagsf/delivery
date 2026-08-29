<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use App\Support\PossuiLoja;
use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    use Auditoravel;
    use PossuiLoja;

    protected $fillable = [
        'nome',
        'codigo',
        'capacidade',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'capacidade' => 'integer',
        ];
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    public function estaAtiva(): bool
    {
        return $this->ativo;
    }
}
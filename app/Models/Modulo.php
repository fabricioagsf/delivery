<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Model;

/**
 * Módulos do sistema: ligados/desligados APENAS diretamente no banco,
 * pelo flag `ativo` (1 = ligado, 0 = desligado). A tela do painel apenas
 * exibe o estado. `loja_id` NULL = regra global; quando existe linha com a
 * loja atual, ela tem prioridade sobre a global (consultas feitas no
 * helper `modulo_ativo()` e no painel).
 */
class Modulo extends Model
{
    use Auditoravel;

    protected $fillable = [
        'loja_id',
        'slug',
        'nome',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
}
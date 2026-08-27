<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cartao extends Model
{
    use Auditoravel;

    protected $table = 'cartoes';

    protected $fillable = [
        'cliente_id',
        'apelido',
        'bandeira',
        'numero_final',
        'validade',
        'titular',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaFiscal extends Model
{
    use Auditoravel;

    protected $table = 'notas_fiscais';

    protected $fillable = [
        'pedido_id',
        'modelo',
        'status',
        'numero',
        'serie',
        'chave_acesso',
        'xml_path',
        'danfe_path',
        'mensagem',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}

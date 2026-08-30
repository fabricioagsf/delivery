<?php

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaConfig extends Model
{
    protected $table = 'saas_configs';

    protected $fillable = [
        'empresa_id',
        'chave',
        'valor',
    ];
}

<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    use Auditoravel;

    protected $table = 'configuracoes';

    protected $fillable = ['chave', 'valor'];
}

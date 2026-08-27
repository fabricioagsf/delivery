<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Model;

class Texto extends Model
{
    use Auditoravel;

    protected $table = 'textos';

    protected $fillable = ['pagina', 'chave', 'valor'];
}

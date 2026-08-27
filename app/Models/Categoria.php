<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use Auditoravel;

    protected $fillable = ['nome', 'slug', 'ativo'];

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }
}

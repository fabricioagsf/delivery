<?php

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Filial extends Model
{
    protected $table = 'saas_filiais';

    protected $fillable = [
        'empresa_id',
        'nome',
        'slug',
        'dominio',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function tenant(): ?\Fabricioagsf\AuthMulti\Models\Tenant
    {
        return \Fabricioagsf\AuthMulti\Models\Tenant::where('slug', $this->slug)->first();
    }
}

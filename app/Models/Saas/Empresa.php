<?php

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'saas_empresas';

    protected $fillable = [
        'nome',
        'slug',
        'cnpj',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function filiais(): HasMany
    {
        return $this->hasMany(Filial::class, 'empresa_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'empresa_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'empresa_id');
    }

    public function configs(): HasMany
    {
        return $this->hasMany(EmpresaConfig::class, 'empresa_id');
    }

    public function config(string $chave, ?string $fallback = null): ?string
    {
        $row = $this->configs()->where('chave', $chave)->first();

        return $row?->valor ?? $fallback;
    }

    public function configFloat(string $chave, float $fallback = 0.0): float
    {
        $valor = $this->config($chave);
        if ($valor === null) {
            return $fallback;
        }

        return (float) str_replace(',', '.', $valor);
    }
}

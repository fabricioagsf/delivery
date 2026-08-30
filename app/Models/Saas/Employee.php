<?php

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Employee extends Authenticatable
{
    protected $table = 'saas_employees';

    protected $fillable = [
        'empresa_id',
        'name',
        'email',
        'password',
        'cargo',
        'ativo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'password' => 'hashed',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filiais(): BelongsToMany
    {
        return $this->belongsToMany(Filial::class, 'saas_employee_filial', 'employee_id', 'filial_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'saas_employee_role', 'employee_id', 'role_id')
            ->withPivot('filial_id')
            ->withTimestamps();
    }

    public function temAcessoFilial(int $filialId): bool
    {
        return $this->filiais()->where('saas_filiais.id', $filialId)->exists();
    }

    public function pedidos(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Pedido::class,
            'pedido_employees',
            'employee_id',
            'pedido_id'
        )->withPivot('comissao_valor', 'registrado_em')
         ->withTimestamps();
    }
}

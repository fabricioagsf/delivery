<?php

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'saas_roles';

    protected $fillable = [
        'empresa_id',
        'nome',
        'slug',
        'descricao',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function getSlugAttribute(): ?string
    {
        return $this->attributes['slug']
            ?? \Illuminate\Support\Str::slug($this->attributes['nome'] ?? '');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'saas_employee_role', 'role_id', 'employee_id')
            ->withPivot('filial_id')
            ->withTimestamps();
    }

    public function temPermissao(string $permissao): bool
    {
        $perms = $this->permissions ?? [];

        return in_array($permissao, $perms, true)
            || in_array('*', $perms, true);
    }
}

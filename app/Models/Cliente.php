<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Cliente extends Authenticatable
{
    use Auditoravel;

    /**
     * Senha padrão do reenvio feito pelo admin ("Nova senha no WhatsApp").
     * Ao entrar com ela, o sistema obriga o cliente a cadastrar uma nova.
     */
    public const SENHA_TEMPORARIA = '123Mudar';

    protected $guard = 'cliente';

    protected $fillable = [
        'nome',
        'telefone',
        'email',
        'senha',
        'chave_seguranca',
        'pontos',
    ];

    protected $hidden = [
        'senha',
        'chave_seguranca',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'chave_seguranca' => 'hashed',
            'pontos' => 'float',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\Fabricioagsf\AuthMulti\Models\Usuario::class, 'usuario_id');
    }

    /**
     * Escopo por loja/tenant: o cliente pertence à loja pelo `usuario.tenant_id`
     * (a conta auth-multi é quem define o isolamento, não a tabela clientes).
     */
    public function scopeDaLoja($query, ?int $tenantId = null)
    {
        $tenantId ??= loja_atual_id();

        if ($tenantId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('usuario', fn ($q) => $q->where('tenant_id', $tenantId));
    }

    public function enderecos(): HasMany
    {
        return $this->hasMany(Endereco::class)->orderByDesc('principal')->orderBy('id');
    }

    public function cartoes(): HasMany
    {
        return $this->hasMany(Cartao::class)->orderBy('id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class)->orderByDesc('id');
    }
}

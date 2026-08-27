<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
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
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->senha;
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

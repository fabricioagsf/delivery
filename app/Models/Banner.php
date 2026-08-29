<?php

namespace App\Models;

use App\Models\Concerns\Auditoravel;
use App\Support\PossuiLoja;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use Auditoravel;
    use PossuiLoja;

    protected $fillable = [
        'loja_id',
        'titulo',
        'imagem',
        'link',
        'ordem',
        'inicio_em',
        'fim_em',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'inicio_em' => 'datetime',
            'fim_em' => 'datetime',
            'ativo' => 'boolean',
        ];
    }

    /**
     * Banners no ar agora: ativos, dentro do agendamento, ordenados.
     */
    public function scopeNoAr($query)
    {
        $agora = now();

        return $query
            ->where('ativo', true)
            ->where(fn ($q) => $q->whereNull('inicio_em')->orWhere('inicio_em', '<=', $agora))
            ->where(fn ($q) => $q->whereNull('fim_em')->orWhere('fim_em', '>=', $agora))
            ->orderBy('ordem')
            ->orderBy('id');
    }

    public function agendado(): bool
    {
        return $this->inicio_em !== null || $this->fim_em !== null;
    }

    public function estaNoAr(): bool
    {
        if (! $this->ativo) {
            return false;
        }

        $agora = now();

        return ($this->inicio_em === null || $this->inicio_em->lte($agora))
            && ($this->fim_em === null || $this->fim_em->gte($agora));
    }

    /**
     * Situação legível para o painel, com textos da tabela `textos`.
     */
    public function situacaoLegivel(): string
    {
        if (! $this->ativo) {
            return texto('admin_banners', 'status.desligado', 'Desligado');
        }

        if ($this->inicio_em !== null && $this->inicio_em->isFuture()) {
            return texto('admin_banners', 'status.agendado', 'Agendado');
        }

        if ($this->fim_em !== null && $this->fim_em->isPast()) {
            return texto('admin_banners', 'status.expirado', 'Expirado');
        }

        return texto('admin_banners', 'status.no_ar', 'No ar');
    }
}

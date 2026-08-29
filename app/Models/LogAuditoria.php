<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAuditoria extends Model
{
    protected $table = 'logs_auditoria';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = null;

    protected $fillable = [
        'origem',
        'acao',
        'tabela',
        'registro_id',
        'dados_antigos',
        'dados_novos',
        'autor',
        'ip',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'dados_antigos' => 'array',
            'dados_novos' => 'array',
            'criado_em' => 'datetime',
        ];
    }

    /**
     * Rótulo legível da ação.
     */
    public function getAcaoLegivelAttribute(): string
    {
        return match ($this->acao) {
            'INSERT' => 'CRIADO',
            'UPDATE' => 'ALTERADO',
            'DELETE' => 'EXCLUÍDO',
            default => $this->acao,
        };
    }

    /**
     * Slug sem acento usado como classe CSS (status-pilula--criacao).
     */
    public function getAcaoClasseAttribute(): string
    {
        return match ($this->acao) {
            'INSERT' => 'criacao',
            'UPDATE' => 'alteracao',
            'DELETE' => 'exclusao',
            default => strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $this->acao)),
        };
    }
}

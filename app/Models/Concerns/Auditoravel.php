<?php

namespace App\Models\Concerns;

use App\Models\LogAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

trait Auditoravel
{
    /** Colunas sensíveis que NUNCA entram nos snapshots (regra 8 da skill). */
    protected const CAMPOS_SENSIVEIS = ['senha', 'password', 'chave_seguranca', 'remember_token'];

    public static function bootAuditoravel(): void
    {
        static::created(function (Model $modelo) {
            $modelo->registrarAuditoria('INSERT', null, $modelo->getAttributes());
        });

        static::updated(function (Model $modelo) {
            if ($modelo->wasChanged()) {
                $modelo->registrarAuditoria(
                    'UPDATE',
                    array_intersect_key($modelo->getOriginal(), $modelo->getChanges()),
                    $modelo->getAttributes()
                );
            }
        });

        static::deleted(function (Model $modelo) {
            $modelo->registrarAuditoria('DELETE', $modelo->getAttributes(), null);
        });
    }

    protected function registrarAuditoria(string $acao, ?array $antigos, ?array $novos): void
    {
        try {
            $usuario = auth('auth_multi')->user() ?: auth('cliente')->user() ?: auth()->user();

            $autor = $usuario
                ? class_basename($usuario).'#'.$usuario->getKey().' '.($usuario->email ?? '')
                : 'sistema';

            // A auditoria NUNCA pode derrubar o fluxo de negócio: qualquer falha
            // fica só no log (a camada de gatilhos do banco continua gravando).
            LogAuditoria::query()->create([
                'origem' => 'aplicacao',
                'acao' => $acao,
                'tabela' => $this->getTable(),
                'loja_id' => $this->loja_id ?? $novos['loja_id'] ?? $antigos['loja_id'] ?? null,
                'registro_id' => (string) $this->getKey(),
                'dados_antigos' => $this->semSensiveis($antigos),
                'dados_novos' => $this->semSensiveis($novos),
                'autor' => $autor,
                'ip' => request()?->ip(),
                // Só o caminho, sem query string: enxuto e sem expor parâmetros
                'url' => mb_substr((string) request()?->getPathInfo(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::error('Auditoria (aplicação) falhou para '.$this->getTable().'#'.$this->getKey().': '.$e->getMessage());
        }
    }

    protected function semSensiveis(?array $dados): ?array
    {
        if ($dados === null) {
            return null;
        }

        return array_diff_key($dados, array_flip(self::CAMPOS_SENSIVEIS));
    }
}

<?php

namespace App\Services;

use App\Models\LogAuditoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Auditoria
{
    /**
     * Colunas sensíveis que nunca entram nos snapshots.
     */
    public const COLUNAS_SENSIVEIS = ['senha', 'password', 'chave_seguranca', 'remember_token'];

    /**
     * Lista paginada de eventos com filtros.
     */
    public function listar(array $filtros = [], int $porPagina = 25): LengthAwarePaginator
    {
        return LogAuditoria::query()
            ->when(! empty($filtros['tabela']), fn ($q) => $q->where('tabela', $filtros['tabela']))
            ->when(! empty($filtros['acao']), fn ($q) => $q->where('acao', $filtros['acao']))
            ->when(! empty($filtros['origem']), fn ($q) => $q->where('origem', $filtros['origem']))
            ->when(! empty($filtros['registro']), function ($q) use ($filtros) {
                $q->where(function ($w) use ($filtros) {
                    $w->where('registro_id', $filtros['registro'])
                        ->orWhere('autor', 'like', '%'.$filtros['registro'].'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * Retorna o registro ao ESTADO registrado no evento escolhido
     * (viagem no tempo por registro — não apenas desfazer o passo anterior).
     *
     * INSERT/UPDATE → aplica o snapshot `dados_novos` (estado logo após o evento);
     * DELETE → traz o registro de volta com o snapshot `dados_antigos`.
     *
     * Exige a senha master (env MASTER_SENHA). Usa upsert, então funciona mesmo
     * que o registro tenha sido excluído depois do evento escolhido.
     *
     * @throws InvalidArgumentException
     */
    public function restaurar(LogAuditoria $log, string $senhaMaster): string
    {
        $this->verificarSenhaMaster($senhaMaster);

        if (! preg_match('/^[A-Za-z0-9_]+$/', $log->tabela)) {
            throw new InvalidArgumentException('Tabela inválida no log de auditoria.');
        }

        if (! $log->registro_id) {
            throw new InvalidArgumentException('Este evento não tem registro vinculado.');
        }

        $snapshot = in_array($log->acao, ['INSERT', 'UPDATE'], true)
            ? ($log->dados_novos ?? [])
            : ($log->dados_antigos ?? []);

        if (empty($snapshot)) {
            throw new InvalidArgumentException('Este evento não guarda um estado completo para retornar.');
        }

        unset($snapshot['id']);
        $snapshot['id'] = $log->registro_id;

        DB::table($log->tabela)->upsert([$snapshot], ['id']);

        return "Registro {$log->tabela}#{$log->registro_id} retornado ao estado do evento #{$log->id} "
            ."({$this->acaoLegivel($log->acao)} de ".($log->criado_em?->format('d/m/Y H:i') ?? '-').').';
    }

    /**
     * A restauração só existe com a senha master configurada e correta.
     */
    protected function verificarSenhaMaster(string $senhaInformada): void
    {
        $senhaMaster = (string) env('MASTER_SENHA');

        if ($senhaMaster === '') {
            throw new InvalidArgumentException(
                'Restauração desabilitada: defina MASTER_SENHA no .env.'
            );
        }

        if (! hash_equals($senhaMaster, $senhaInformada)) {
            throw new InvalidArgumentException('Senha master incorreta.');
        }
    }

    protected function acaoLegivel(string $acao): string
    {
        return match ($acao) {
            'INSERT' => 'criação',
            'UPDATE' => 'alteração',
            'DELETE' => 'exclusão',
            default => strtolower($acao),
        };
    }
}

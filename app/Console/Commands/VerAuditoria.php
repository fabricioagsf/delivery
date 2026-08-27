<?php

namespace App\Console\Commands;

use App\Models\LogAuditoria;
use App\Services\Auditoria;
use Illuminate\Console\Command;

class VerAuditoria extends Command
{
    protected $signature = 'auditoria:ver
                            {--tabela= : Filtra por tabela}
                            {--acao= : INSERT, UPDATE ou DELETE}
                            {--registro= : Id do registro ou parte do autor}
                            {--origem= : gatilho ou aplicacao}
                            {--limite=30 : Quantidade de eventos}';

    protected $description = 'Mostra o histórico de auditoria do banco (criado/alterado/excluído)';

    public function handle(Auditoria $auditoria): int
    {
        $eventos = LogAuditoria::query()
            ->when($this->option('tabela'), fn ($q) => $q->where('tabela', $this->option('tabela')))
            ->when($this->option('acao'), fn ($q) => $q->where('acao', strtoupper((string) $this->option('acao'))))
            ->when($this->option('origem'), fn ($q) => $q->where('origem', $this->option('origem')))
            ->when($this->option('registro'), function ($q) {
                $termo = (string) $this->option('registro');
                $q->where(fn ($w) => $w
                    ->where('registro_id', $termo)
                    ->orWhere('autor', 'like', "%{$termo}%"));
            })
            ->orderByDesc('id')
            ->limit(max(1, (int) $this->option('limite')))
            ->get();

        if ($eventos->isEmpty()) {
            $this->info('Nenhum evento encontrado com esses filtros.');

            return self::SUCCESS;
        }

        $linhas = $eventos->map(fn (LogAuditoria $evento) => [
            'id' => '#'.$evento->id,
            'quando' => $evento->criado_em?->format('d/m/Y H:i:s'),
            'origem' => str_pad($evento->origem, 9),
            'acao' => str_pad($evento->acao_legivel, 8),
            'tabela.id' => $evento->tabela.'.'.$evento->registro_id,
            'autor/url' => $evento->autor ?? $evento->url ?? '-',
        ])->all();

        $this->table(['ID', 'QUANDO', 'ORIGEM', 'AÇÃO', 'REGISTRO', 'AUTOR / URL'], $linhas);
        $this->newLine();
        $this->line('Detalhe de um evento: php artisan auditoria:ver --registro=ID  |  Restaurar: php artisan auditoria:restaurar ID');

        // Referência cruzada opcional pelo serviço (mantém filtros centralizados)
        if (! empty($this->option('tabela'))) {
            $auditoria->listar(['tabela' => $this->option('tabela')], 1);
        }

        return self::SUCCESS;
    }
}

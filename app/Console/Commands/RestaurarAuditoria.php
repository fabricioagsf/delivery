<?php

namespace App\Console\Commands;

use App\Models\LogAuditoria;
use App\Services\Auditoria;
use Illuminate\Console\Command;
use Throwable;

class RestaurarAuditoria extends Command
{
    protected $signature = 'auditoria:restaurar
                            {id? : Id do evento no histórico de auditoria}
                            {--ultimo : Usa o evento mais recente}
                            {--senha= : Senha master (ou será pedida de forma oculta)}
                            {--sem-confirmar : Executa sem pedir confirmação}';

    protected $description = 'Retorna um registro ao estado exato de qualquer evento do histórico (exige a senha master)';

    public function handle(Auditoria $auditoria): int
    {
        $log = $this->option('ultimo')
            ? LogAuditoria::query()->orderByDesc('id')->first()
            : LogAuditoria::query()->find($this->argument('id'));

        if (! $log) {
            $this->error('Evento não encontrado no histórico.');

            return self::FAILURE;
        }

        $this->apresentar($log);

        if (! $this->option('sem-confirmar') && ! $this->confirm('Confirma a restauração deste estado?')) {
            $this->line('Cancelado. Nada foi alterado.');

            return self::SUCCESS;
        }

        $senhaMaster = (string) ($this->option('senha') ?? '') ?: $this->secret('Senha master:');

        try {
            $mensagem = $auditoria->restaurar($log, $senhaMaster);
            $this->info('✓ '.$mensagem);

            return self::SUCCESS;
        } catch (Throwable $erro) {
            $this->error('Não foi possível restaurar: '.$erro->getMessage());

            return self::FAILURE;
        }
    }

    protected function apresentar(LogAuditoria $log): void
    {
        $this->line("Evento #{$log->id} — {$log->acao_legivel} em {$log->tabela}.{$log->registro_id}");
        $this->line('Quando: ' . ($log->criado_em?->format('d/m/Y H:i:s') ?? '-') . " | Origem: {$log->origem}" . ($log->autor ? " | Autor: {$log->autor}" : ''));

        // Estado alvo: logo após INSERT/UPDATE; antes da exclusão em DELETE.
        $alvo = in_array($log->acao, ['INSERT', 'UPDATE'], true)
            ? ($log->dados_novos ?? [])
            : ($log->dados_antigos ?? []);

        if (! empty($alvo)) {
            $this->newLine();
            $this->line('<comment>Estado que será aplicado ao registro:</comment>');

            foreach (collect($alvo)->take(15) as $campo => $valor) {
                $rotulo = \App\Support\AuditoriaFormatador::campo($campo);
                $texto = \App\Support\AuditoriaFormatador::valor($campo, $valor);
                $this->line("  {$rotulo}: {$texto}");
            }
        }
    }
}

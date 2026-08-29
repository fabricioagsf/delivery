<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SincronizarAuditoria extends Command
{
    protected $signature = 'auditoria:sincronizar';

    protected $description = '(Re)gera os gatilhos de auditoria (INSERT/UPDATE/DELETE) para todas as tabelas do banco';

    private const TABELA_LOG = 'logs_auditoria';

    /**
     * Tabelas internas/ruído — não auditam.
     * pedido_itens: itens de linha do pedido; o pedido em si já é auditado.
     */
    private const TABELAS_EXCLUIDAS = [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'pedido_itens',
    ];

    public function handle(): int
    {
        if (! in_array(self::TABELA_LOG, $this->tabelas(), true)) {
            $this->error('Tabela logs_auditoria não existe. Rode php artisan migrate primeiro.');

            return self::FAILURE;
        }

        $total = 0;
        $falhas = [];
        $erroPrivilegio = false;

        foreach ($this->tabelas() as $tabela) {
            if ($tabela === self::TABELA_LOG || in_array($tabela, self::TABELAS_EXCLUIDAS, true)) {
                continue;
            }

            $colunas = $this->colunas($tabela);

            if (empty($colunas)) {
                continue;
            }

            $temLoja = in_array('loja_id', $colunas, true);

            foreach (['ins' => 'INSERT', 'upd' => 'UPDATE', 'del' => 'DELETE'] as $sufixo => $evento) {
                $gatilho = "au_{$tabela}_{$sufixo}";

                try {
                    DB::statement("DROP TRIGGER IF EXISTS `{$gatilho}`");
                    DB::unprepared($this->criarGatilho($gatilho, $tabela, $evento, $colunas, $temLoja));
                    $total++;
                } catch (\Throwable $erro) {
                    if (str_contains($erro->getMessage(), '1419')) {
                        $erroPrivilegio = true;
                        break;
                    }

                    $falhas[] = "{$gatilho}: {$erro->getMessage()}";
                }
            }

            if ($erroPrivilegio) {
                break;
            }

            $this->line("  ✓ {$tabela} (3 gatilhos)");
        }

        if ($total > 0) {
            $this->info("{$total} gatilhos de auditoria sincronizados.");
        }

        // Limpa gatilhos de tabelas que saíram do escopo (ex.: pedido_itens)
        $removidos = 0;

        foreach (self::TABELAS_EXCLUIDAS as $tabela) {
            foreach (['ins', 'upd', 'del'] as $sufixo) {
                $gatilho = "au_{$tabela}_{$sufixo}";

                if (DB::selectOne('SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = ?', [$gatilho])) {
                    DB::statement("DROP TRIGGER IF EXISTS `{$gatilho}`");
                    $removidos++;
                }
            }
        }

        if ($removidos > 0) {
            $this->line("{$removidos} gatilhos fora do escopo foram removidos (ex.: pedido_itens).");
        }

        if ($erroPrivilegio) {
            $this->warn('Banco recusou CREATE TRIGGER (erro 1419).');
            $this->line('Peça ao administrador do MySQL para executar uma vez como root:');
            $this->line('  SET GLOBAL log_bin_trust_function_creators = 1;');
            $this->line('Depois rode novamente: php artisan auditoria:sincronizar');
            $this->line('Enquanto isso, a auditoria da camada de aplicação (trait Auditoravel) segue ativa.');
        }

        foreach ($falhas as $falha) {
            $this->warn("Falhou: {$falha}");
        }

        return ($total > 0 || $erroPrivilegio === false) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Todas as tabelas do banco atual.
     */
    protected function tabelas(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($t) => array_values((array) $t)[0])
            ->values()
            ->all();
    }

    /**
     * Colunas reais da tabela (exclui geradas/virtuais).
     */
    protected function colunas(string $tabela): array
    {
        return collect(
            DB::select(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS '
                .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '
                ."AND COALESCE(EXTRA, '') NOT LIKE '%GENERATED%' "
                .'ORDER BY ORDINAL_POSITION',
                [$tabela]
            )
        )
            ->pluck('COLUMN_NAME')
            ->all();
    }

    protected function criarGatilho(string $nome, string $tabela, string $evento, array $colunas, bool $temLoja = false): string
    {
        // Em INSERT não existem valores OLD; em DELETE não existem NEW.
        $momento = $evento === 'INSERT' ? ['null', 'novo'] : ($evento === 'DELETE' ? ['velho', 'null'] : ['velho', 'novo']);

        $jsonAntigos = $momento[0] === 'null'
            ? 'NULL'
            : $this->jsonObject($colunas, 'OLD');

        $jsonNovos = $momento[1] === 'null'
            ? 'NULL'
            : $this->jsonObject($colunas, 'NEW');

        $registroId = $evento === 'DELETE' ? 'OLD.`id`' : 'NEW.`id`';

        // loja_id no log quando a tabela tem a coluna (para isolamento do painel)
        $lojaId = ! $temLoja
            ? 'NULL'
            : ($evento === 'INSERT'
                ? 'NEW.`loja_id`'
                : ($evento === 'DELETE'
                    ? 'OLD.`loja_id`'
                    : 'COALESCE(NEW.`loja_id`, OLD.`loja_id`)'));

        return <<<SQL
CREATE TRIGGER `{$nome}` AFTER {$evento} ON `{$tabela}`
FOR EACH ROW
INSERT INTO `logs_auditoria`
    (`origem`, `acao`, `tabela`, `loja_id`, `registro_id`, `dados_antigos`, `dados_novos`, `criado_em`)
VALUES
    ('gatilho', '{$evento}', '{$tabela}', {$lojaId}, {$registroId}, {$jsonAntigos}, {$jsonNovos}, NOW())
SQL;
    }

    /**
     * Monta JSON_OBJECT('coluna', PREFIXO.`coluna`, ...) com nomes escapados.
     */
    protected function jsonObject(array $colunas, string $prefixo): string
    {
        $pares = [];

        foreach ($colunas as $coluna) {
            $segura = str_replace('`', '``', $coluna);
            $pares[] = "'{$segura}', {$prefixo}.`{$segura}`";
        }

        return 'JSON_OBJECT('.implode(', ', $pares).')';
    }
}

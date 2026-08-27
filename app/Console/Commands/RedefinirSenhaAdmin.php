<?php

namespace App\Console\Commands;

use Fabricioagsf\AuthMulti\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class RedefinirSenhaAdmin extends Command
{
    protected $signature = 'admin:senha {--email= : E-mail do admin (se omitido, pergunta)}';

    protected $description = 'Cadastra ou redefine o acesso do painel /admin gravando direto no banco (tabela usuarios)';

    public function handle(): int
    {
        $email = strtolower(trim($this->option('email') ?: $this->ask('E-mail do admin')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Informe um e-mail válido.');

            return self::FAILURE;
        }

        $senha = $this->secret('Nova senha (mínimo 8 caracteres)');

        if (strlen((string) $senha) < 8) {
            $this->error('A senha deve ter no mínimo 8 caracteres.');

            return self::FAILURE;
        }

        $confirma = $this->secret('Confirmar a nova senha');

        if ($senha !== $confirma) {
            $this->error('As senhas não conferem.');

            return self::FAILURE;
        }

        // Grava no BANCO (INSERT se não existe, UPDATE se já existe).
        // O hash é aplicado aqui e a senha nunca mais aparece em código ou config.
        $admin = Usuario::updateOrCreate(
            ['email' => $email, 'tipo' => 'admin'],
            [
                'nome' => 'Administração',
                'senha' => Hash::make($senha),
                'ativo' => true,
            ]
        );

        $this->info('Acesso gravado no banco com sucesso (tabela usuarios):');
        $this->line('  E-mail: '.$admin->email);
        $this->line('  Entrar em: '.route('authmulti.admin.tela'));

        return self::SUCCESS;
    }
}

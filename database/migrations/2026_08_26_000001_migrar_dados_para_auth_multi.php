<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Criar tenant padrao se nao existir
        $tenantExistente = DB::table('tenants')->where('slug', 'gostosuras')->first();

        if (! $tenantExistente) {
            DB::table('tenants')->insert([
                'nome' => 'Gostosuras',
                'slug' => 'gostosuras',
                'dominio' => null,
                'status' => 'ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tenantId = DB::table('tenants')->where('slug', 'gostosuras')->value('id');

        // 2. Migrar users existentes para usuarios (tipo admin)
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $jaExiste = DB::table('usuarios')
                ->where('tenant_id', $tenantId)
                ->where('email', $user->email)
                ->exists();

            if (! $jaExiste) {
                DB::table('usuarios')->insert([
                    'tenant_id' => $tenantId,
                    'tipo' => 'admin',
                    'nome' => $user->name ?? $user->email,
                    'email' => $user->email,
                    'senha' => $user->password,
                    'ativo' => true,
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => $user->updated_at ?? now(),
                ]);
            }
        }

        // 3. Migrar clientes existentes para usuarios (tipo cliente)
        $clientes = DB::table('clientes')->get();

        foreach ($clientes as $cliente) {
            $jaExiste = DB::table('usuarios')
                ->where('tenant_id', $tenantId)
                ->where('email', $cliente->email)
                ->exists();

            if (! $jaExiste) {
                DB::table('usuarios')->insert([
                    'tenant_id' => $tenantId,
                    'tipo' => 'cliente',
                    'nome' => $cliente->nome,
                    'email' => $cliente->email,
                    'senha' => $cliente->senha,
                    'ativo' => true,
                    'created_at' => $cliente->created_at ?? now(),
                    'updated_at' => $cliente->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Nao remove dados - regra 2: nao destruir sem permissao
    }
};

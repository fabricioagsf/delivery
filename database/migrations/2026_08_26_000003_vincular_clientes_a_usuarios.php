<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('slug', 'gostosuras')->value('id');

        if (! $tenantId) {
            return;
        }

        // Vincular clientes existentes aos usuarios do auth-multi
        $clientes = DB::table('clientes')->whereNull('usuario_id')->get();

        foreach ($clientes as $cliente) {
            $usuario = DB::table('usuarios')
                ->where('tenant_id', $tenantId)
                ->where('email', $cliente->email)
                ->where('tipo', 'cliente')
                ->first();

            if ($usuario) {
                DB::table('clientes')
                    ->where('id', $cliente->id)
                    ->update(['usuario_id' => $usuario->id]);
            }
        }
    }

    public function down(): void
    {
        DB::table('clientes')->update(['usuario_id' => null]);
    }
};

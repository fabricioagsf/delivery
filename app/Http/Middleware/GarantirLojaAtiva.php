<?php

namespace App\Http\Middleware;

use Closure;
use Fabricioagsf\AuthMulti\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que toda requisição web tenha uma `loja_id` na sessão antes de
 * chegar aos controllers. Se não houver (sessão nova, cookie expirado, etc.),
 * fixa a primeira loja ativa como padrão — nunca deixa o sistema cair no
 * modo "vê tudo" porque isso quebraria o isolamento de caixa/estoque/vendas.
 *
 * O middleware de auth-multi (que vem antes) já trata o `loja_id` para o
 * admin, mas visitantes da loja pública precisam de fallback seguro aqui.
 */
class GarantirLojaAtiva
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('loja_id')) {
            $loja = Tenant::where('status', 'ativo')
                ->orderBy('id')
                ->first();

            if ($loja) {
                session(['loja_id' => $loja->id]);
            }
        }

        return $next($request);
    }
}

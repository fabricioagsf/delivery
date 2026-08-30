<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SaaSEmployeeAdmin
{
    public function handle(Request $request, Closure $next, ?string $permissao = null): Response
    {
        $employee = saas_employee_atual();

        if (! $employee) {
            if ($request->expectsJson()) {
                return response()->json(['mensagem' => 'Não autenticado.'], 401);
            }

            return redirect()->route('saas.login')->with('info', 'Faça login como funcionário.');
        }

        if (! $employee->ativo) {
            abort(403, 'Funcionário inativo.');
        }

        if ($permissao && ! $employee->hasPermission($permissao)) {
            abort(403, 'Sem permissão para esta ação.');
        }

        return $next($request);
    }
}

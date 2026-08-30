<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OperadorAutenticado
{
    public function handle(Request $request, Closure $next, ?string $permissao = null): Response
    {
        $employee = saas_employee_atual();

        if ($employee) {
            if (! $employee->ativo) {
                abort(403, 'Funcionário inativo.');
            }
            if ($permissao && ! $employee->hasPermission($permissao)) {
                abort(403, 'Sem permissão.');
            }
            return $next($request);
        }

        if (auth('auth_multi')->check() || auth('web')->check()) {
            return $next($request);
        }

        return redirect()->guest(route('saas.login'));
    }
}

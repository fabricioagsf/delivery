<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SaasAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('saas_employee_id')) {
            return redirect()->route('saas.login');
        }

        return $next($request);
    }
}

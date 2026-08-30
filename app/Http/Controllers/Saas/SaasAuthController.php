<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SaasAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('saas.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $employee = Employee::where('email', $dados['email'])->where('ativo', true)->first();

        if (! $employee || ! Hash::check($dados['password'], $employee->password)) {
            return back()->withErrors(['email' => 'Credenciais inválidas.'])->withInput();
        }

        session(['saas_employee_id' => $employee->id]);
        session(['saas_empresa_id' => $employee->empresa_id]);

        return redirect()->route('saas.dashboard');
    }

    public function logout(): RedirectResponse
    {
        session()->forget(['saas_employee_id', 'saas_empresa_id']);
        return redirect()->route('saas.login');
    }
}

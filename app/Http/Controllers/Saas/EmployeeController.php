<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\Employee;
use App\Models\Saas\Filial;
use App\Models\Saas\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $empresa = saas_empresa_atual();
        $employees = Employee::where('empresa_id', $empresa?->id)
            ->with(['filiais', 'roles'])
            ->orderBy('name')
            ->get();

        return view('saas.employees.index', [
            'employees' => $employees,
        ]);
    }

    public function create(): View
    {
        $empresa = saas_empresa_atual();
        return view('saas.employees.form', [
            'employee' => new Employee,
            'filiais' => Filial::where('empresa_id', $empresa?->id)->orderBy('nome')->get(),
            'roles' => Role::where('empresa_id', $empresa?->id)->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = saas_empresa_atual();
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:saas_employees,email'],
            'password' => ['required', 'string', 'min:6'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'ativo' => ['nullable', 'boolean'],
            'filiais' => ['array'],
            'filiais.*' => ['integer', 'exists:saas_filiais,id'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:saas_roles,id'],
        ]);
        $dados['empresa_id'] = $empresa->id;
        $dados['password'] = Hash::make($dados['password']);
        $dados['ativo'] = (bool) ($dados['ativo'] ?? true);

        $filiais = $dados['filiais'] ?? [];
        $roles = $dados['roles'] ?? [];
        unset($dados['filiais'], $dados['roles']);

        $employee = Employee::create($dados);
        if ($filiais) {
            $employee->filiais()->sync($filiais);
        }
        if ($roles) {
            $employee->roles()->sync($roles);
        }

        return redirect()
            ->route('saas.employees.index')
            ->with('sucesso', 'Funcionário criado.');
    }

    public function edit(Employee $employee): View
    {
        $empresa = saas_empresa_atual();
        abort_unless($employee->empresa_id === $empresa?->id, 403);
        return view('saas.employees.form', [
            'employee' => $employee,
            'filiais' => Filial::where('empresa_id', $empresa->id)->orderBy('nome')->get(),
            'roles' => Role::where('empresa_id', $empresa->id)->orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $empresa = saas_empresa_atual();
        abort_unless($employee->empresa_id === $empresa->id, 403);

        $dados = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:saas_employees,email,'.$employee->id],
            'password' => ['nullable', 'string', 'min:6'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'ativo' => ['nullable', 'boolean'],
            'filiais' => ['array'],
            'filiais.*' => ['integer', 'exists:saas_filiais,id'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:saas_roles,id'],
        ]);
        $filiais = $dados['filiais'] ?? [];
        $roles = $dados['roles'] ?? [];
        unset($dados['filiais'], $dados['roles']);

        if (! empty($dados['password'])) {
            $dados['password'] = Hash::make($dados['password']);
        } else {
            unset($dados['password']);
        }
        $dados['ativo'] = (bool) ($dados['ativo'] ?? false);

        $employee->update($dados);
        $employee->filiais()->sync($filiais);
        $employee->roles()->sync($roles);

        return redirect()
            ->route('saas.employees.index')
            ->with('sucesso', 'Funcionário atualizado.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $empresa = saas_empresa_atual();
        abort_unless($employee->empresa_id === $empresa?->id, 403);
        $employee->delete();

        return redirect()
            ->route('saas.employees.index')
            ->with('sucesso', 'Funcionário removido.');
    }
}

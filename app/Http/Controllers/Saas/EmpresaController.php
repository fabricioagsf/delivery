<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\Empresa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index(): View
    {
        $empresas = Empresa::withCount(['filiais', 'employees'])->orderBy('nome')->get();

        return view('saas.empresas.index', [
            'empresas' => $empresas,
        ]);
    }

    public function create(): View
    {
        return view('saas.empresas.form', ['empresa' => new Empresa]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:saas_empresas,slug'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'ativo' => ['nullable', 'boolean'],
        ]);
        $dados['ativo'] = (bool) ($dados['ativo'] ?? true);

        $empresa = Empresa::create($dados);

        return redirect()
            ->route('saas.empresas.index')
            ->with('sucesso', 'Empresa criada com sucesso.');
    }

    public function edit(Empresa $empresa): View
    {
        return view('saas.empresas.form', ['empresa' => $empresa]);
    }

    public function update(Request $request, Empresa $empresa): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:saas_empresas,slug,'.$empresa->id],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'ativo' => ['nullable', 'boolean'],
        ]);
        $dados['ativo'] = (bool) ($dados['ativo'] ?? false);

        $empresa->update($dados);

        return redirect()
            ->route('saas.empresas.index')
            ->with('sucesso', 'Empresa atualizada.');
    }

    public function destroy(Empresa $empresa): RedirectResponse
    {
        $empresa->delete();

        return redirect()
            ->route('saas.empresas.index')
            ->with('sucesso', 'Empresa removida.');
    }
}

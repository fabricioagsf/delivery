<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\Filial;
use App\Models\Saas\Empresa;
use Fabricioagsf\AuthMulti\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FilialController extends Controller
{
    public function index(): View
    {
        $empresa = saas_empresa_atual();
        $filiais = Filial::where('empresa_id', $empresa?->id)->orderBy('nome')->get();

        return view('saas.filiais.index', [
            'filiais' => $filiais,
        ]);
    }

    public function create(): View
    {
        return view('saas.filiais.form', [
            'filial' => new Filial,
            'empresas' => Empresa::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = saas_empresa_atual();
        $dados = $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:saas_empresas,id'],
            'nome' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:saas_filiais,slug'],
            'dominio' => ['nullable', 'string', 'max:190'],
            'ativo' => ['nullable', 'boolean'],
        ]);
        $dados['ativo'] = (bool) ($dados['ativo'] ?? true);

        $filial = Filial::create($dados);

        Tenant::create([
            'saas_empresa_id' => $filial->empresa_id,
            'nome' => $filial->nome,
            'slug' => $filial->slug,
            'dominio' => $filial->dominio,
            'status' => $filial->ativo ? 'ativo' : 'suspenso',
        ]);

        return redirect()
            ->route('saas.filiais.index')
            ->with('sucesso', 'Filial criada e loja criada no sistema.');
    }

    public function edit(Filial $filial): View
    {
        $empresa = saas_empresa_atual();
        abort_unless($filial->empresa_id === $empresa?->id, 403);
        return view('saas.filiais.form', [
            'filial' => $filial,
            'empresas' => Empresa::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, Filial $filial): RedirectResponse
    {
        $empresa = saas_empresa_atual();
        abort_unless($filial->empresa_id === $empresa?->id, 403);

        $dados = $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:saas_empresas,id'],
            'nome' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:saas_filiais,slug,'.$filial->id],
            'dominio' => ['nullable', 'string', 'max:190'],
            'ativo' => ['nullable', 'boolean'],
        ]);
        $dados['ativo'] = (bool) ($dados['ativo'] ?? false);

        $filial->update($dados);

        $tenant = Tenant::where('slug', $filial->slug)->first();
        if ($tenant) {
            $tenant->update([
                'nome' => $filial->nome,
                'dominio' => $filial->dominio,
                'status' => $filial->ativo ? 'ativo' : 'suspenso',
            ]);
        }

        return redirect()
            ->route('saas.filiais.index')
            ->with('sucesso', 'Filial atualizada.');
    }

    public function destroy(Filial $filial): RedirectResponse
    {
        $empresa = saas_empresa_atual();
        abort_unless($filial->empresa_id === $empresa?->id, 403);
        $filial->delete();

        return redirect()
            ->route('saas.filiais.index')
            ->with('sucesso', 'Filial removida.');
    }
}

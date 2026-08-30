<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\EmpresaConfig;
use App\Models\Saas\Role;
use App\Models\Saas\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmpresaConfigController extends Controller
{
    public function index(\App\Models\Saas\Empresa $empresa): View
    {
        abort_unless(saas_empresa_atual()?->id === $empresa->id, 403);

        $roles = Role::where('empresa_id', $empresa->id)->orderBy('nome')->get();
        $configs = EmpresaConfig::where('empresa_id', $empresa->id)
            ->orderBy('chave')
            ->get()
            ->keyBy('chave');

        $comissoes = [];
        foreach ($roles as $role) {
            $comissoes[$role->id] = (float) ($configs['comissao.' . $role->slug]?->valor ?? 0);
        }

        return view('saas.empresas.config', [
            'empresa' => $empresa,
            'roles' => $roles,
            'comissoes' => $comissoes,
            'configs' => $configs,
        ]);
    }

    public function salvar(Request $request, \App\Models\Saas\Empresa $empresa): RedirectResponse
    {
        abort_unless(saas_empresa_atual()?->id === $empresa->id, 403);

        $dados = $request->validate([
            'comissao' => ['array'],
            'comissao.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($roles = Role::where('empresa_id', $empresa->id)->get() as $role) {
            $valor = (float) ($dados['comissao'][$role->id] ?? 0);
            EmpresaConfig::updateOrCreate(
                ['empresa_id' => $empresa->id, 'chave' => 'comissao.' . $role->slug],
                ['valor' => number_format($valor, 2, ',', '')],
            );
        }

        return redirect()
            ->route('saas.empresas.config', $empresa)
            ->with('sucesso', 'Configurações salvas.');
    }
}

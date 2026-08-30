<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\EmpresaConfig;
use App\Models\Saas\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfiguracaoController extends Controller
{
    public function index(): View
    {
        $empresa = saas_empresa_atual();
        $roles = Role::where('empresa_id', $empresa->id)->orderBy('nome')->get();
        $configs = EmpresaConfig::where('empresa_id', $empresa->id)
            ->orderBy('chave')
            ->get()
            ->keyBy('chave');

        $comissoes = [];
        foreach ($roles as $role) {
            $chave = 'comissao.' . $role->slug;
            $comissoes[$role->id] = (float) ($configs[$chave]->valor ?? 0);
        }

        return view('saas.configuracoes.index', [
            'empresa' => $empresa,
            'roles' => $roles,
            'comissoes' => $comissoes,
            'configs' => $configs,
        ]);
    }

    public function salvar(Request $request): RedirectResponse
    {
        $empresa = saas_empresa_atual();

        $dados = $request->validate([
            'comissao' => ['array'],
            'comissao.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $roles = Role::where('empresa_id', $empresa->id)->get();
        foreach ($roles as $role) {
            $chave = 'comissao.' . $role->slug;
            $valor = (float) ($dados['comissao'][$role->id] ?? 0);
            EmpresaConfig::updateOrCreate(
                ['empresa_id' => $empresa->id, 'chave' => $chave],
                ['valor' => number_format($valor, 2, '.', '')],
            );
        }

        return redirect()
            ->route('saas.configuracoes.index')
            ->with('sucesso', 'Configurações salvas.');
    }
}

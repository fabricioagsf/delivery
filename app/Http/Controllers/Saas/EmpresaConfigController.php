<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\EmpresaConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmpresaConfigController extends Controller
{
    public function index(\App\Models\Saas\Empresa $empresa): View
    {
        abort_unless(saas_empresa_atual()?->id === $empresa->id, 403);

        $configs = EmpresaConfig::where('empresa_id', $empresa->id)
            ->orderBy('chave')
            ->get()
            ->keyBy('chave');

        return view('saas.empresas.config', [
            'empresa' => $empresa,
            'comissaoPadrao' => (float) ($configs['comissao_padrao']->valor ?? 0),
            'configs' => $configs,
        ]);
    }

    public function salvar(Request $request, \App\Models\Saas\Empresa $empresa): RedirectResponse
    {
        abort_unless(saas_empresa_atual()?->id === $empresa->id, 403);

        $dados = $request->validate([
            'comissao_padrao' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        EmpresaConfig::updateOrCreate(
            ['empresa_id' => $empresa->id, 'chave' => 'comissao_padrao'],
            ['valor' => number_format((float) $dados['comissao_padrao'], 2, ',', '')]
        );

        return redirect()
            ->route('saas.empresas.config', $empresa)
            ->with('sucesso', 'Configurações salvas.');
    }
}

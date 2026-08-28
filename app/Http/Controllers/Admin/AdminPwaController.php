<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PwaController;
use App\Models\Configuracao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminPwaController extends Controller
{
    public function index(): View
    {
        return view('admin.pwa', [
            'ativo' => config_loja('pwa_ativo', '1') === '1',
            'versao' => (int) (config_loja('pwa_cache_versao') ?? 1),
            'totalImagens' => PwaController::totalImagens(),
            'manifestUrl' => url('/manifest.webmanifest'),
            'serviceWorkerUrl' => url('/sw.js'),
            'cardapioUrl' => url('/cardapio'),
        ]);
    }

    public function atualizar(Request $request): RedirectResponse
    {
        Configuracao::updateOrCreate(
            ['chave' => 'pwa_ativo'],
            ['valor' => $request->boolean('pwa_ativo') ? '1' : '0', 'updated_at' => now()]
        );

        if ($request->boolean('pwa_renovar_cache')) {
            Configuracao::updateOrCreate(
                ['chave' => 'pwa_cache_versao'],
                ['valor' => (string) ((int) (config_loja('pwa_cache_versao') ?? 1) + 1), 'updated_at' => now()]
            );
        }

        return back()->with(
            'sucesso_pwa',
            texto('admin_pwa', 'sucesso.salvo', 'Configuração do app (PWA) salva!')
        );
    }
}

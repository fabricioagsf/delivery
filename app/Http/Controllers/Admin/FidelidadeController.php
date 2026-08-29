<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Configuracao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FidelidadeController extends Controller
{
    public function index(): View
    {
        return view('admin.fidelidade', [
            'ativo' => config_loja('fidelidade_ativo', '0') === '1',
            'ganho' => config_loja('fidelidade_ganho', '1'),
            'pontoValor' => config_loja('fidelidade_ponto_valor', '0.10'),
            'totalClientes' => Cliente::where('pontos', '>', 0)->count(),
            'totalPontos' => Cliente::sum('pontos'),
        ]);
    }

    public function atualizar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'ganho' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'ponto_valor' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
        ], [
            '*.numeric' => texto('admin_fidelidade', 'erro.numero', 'Informe um número válido.'),
        ]);

        Configuracao::updateOrCreate(
            ['loja_id' => loja_atual_id(), 'chave' => 'fidelidade_ativo'],
            ['valor' => $request->boolean('fidelidade_ativo') ? '1' : '0', 'updated_at' => now()]
        );

        Configuracao::updateOrCreate(
            ['loja_id' => loja_atual_id(), 'chave' => 'fidelidade_ganho'],
            ['valor' => (string) ($dados['ganho'] ?? 1), 'updated_at' => now()]
        );

        Configuracao::updateOrCreate(
            ['loja_id' => loja_atual_id(), 'chave' => 'fidelidade_ponto_valor'],
            ['valor' => (string) ($dados['ponto_valor'] ?? 0.10), 'updated_at' => now()]
        );

        return back()->with(
            'sucesso_fidelidade',
            texto('admin_fidelidade', 'sucesso.salvo', 'Configuração do programa de fidelidade salva!')
        );
    }
}

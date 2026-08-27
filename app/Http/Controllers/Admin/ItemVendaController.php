<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Produto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemVendaController extends Controller
{
    public function index(): View
    {
        $tipo = config_loja('item_venda_tipo', 'produtos');

        return view('admin.item_venda', [
            'ativo' => config_loja('item_venda_ativo', '0') === '1',
            'tipo' => $tipo,
            'permiteProdutos' => in_array($tipo, ['produtos', 'ambos'], true),
            'permiteServicos' => in_array($tipo, ['servicos', 'ambos'], true),
            'totalProdutos' => Produto::count(),
            'totalCategorias' => \App\Models\Categoria::count(),
            'totalAtivos' => Produto::ativos()->count(),
            'totalPedidos' => \App\Models\PedidoItem::distinct('pedido_id')->count('pedido_id'),
        ]);
    }

    public function atualizar(Request $request): RedirectResponse
    {
        $tipo = $request->input('item_venda_tipo', 'produtos');
        if (! in_array($tipo, ['produtos', 'servicos', 'ambos'], true)) {
            $tipo = 'produtos';
        }

        Configuracao::updateOrCreate(
            ['chave' => 'item_venda_ativo'],
            ['valor' => $request->boolean('item_venda_ativo') ? '1' : '0', 'updated_at' => now()]
        );

        Configuracao::updateOrCreate(
            ['chave' => 'item_venda_tipo'],
            ['valor' => $tipo, 'updated_at' => now()]
        );

        return back()->with(
            'sucesso_item_venda',
            texto('admin_item_venda', 'sucesso.salvo', 'Módulo de produtos e serviços atualizado!')
        );
    }
}

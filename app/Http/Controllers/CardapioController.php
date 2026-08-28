<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\View\View;

class CardapioController extends Controller
{
    /**
     * Cardápio digital público: os produtos ativos agrupados por categoria,
     * em um layout de menu. O cliente pode pedir direto daqui (o pedido usa o
     * mesmo fluxo de carrinho/checkout da loja).
     */
    public function index(): View
    {
        $categorias = Categoria::query()
            ->where('ativo', true)
            ->with(['produtos' => fn ($q) => $q
                ->ativos()
                ->with('complementosAtivos')
                ->orderBy('nome')
            ])
            ->orderBy('nome')
            ->get()
            ->filter(fn ($categoria) => $categoria->produtos->isNotEmpty());

        return view('cardapio.index', [
            'categorias' => $categorias,
            'totalItens' => $categorias->sum(fn ($c) => $c->produtos->count()),
        ]);
    }
}

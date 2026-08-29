<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Mesa;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CardapioController extends Controller
{
    /**
     * Cardápio digital público: os produtos ativos agrupados por categoria,
     * em um layout de menu. O cliente pode pedir direto daqui (o pedido usa o
     * mesmo fluxo de carrinho/checkout da loja).
     *
     * Aceita ?mesa=ID — vincula o pedido à mesa correspondente e ajusta o
     * cardápio para o tipo de pedido "mesa" (taxa de entrega dispensada, etc.).
     */
    public function index(Request $request): View
    {
        $mesaId = $request->query('mesa');
        $mesa = null;

        if ($mesaId) {
            $mesa = Mesa::ativas()->find($mesaId);

            if ($mesa) {
                session(['mesa_id' => $mesa->id, 'mesa_nome' => $mesa->nome ?: $mesa->codigo ?: ('Mesa #'.$mesa->id)]);
            }
        }

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
            'mesa' => $mesa,
        ]);
    }
}

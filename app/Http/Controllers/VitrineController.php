<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\ProdutoComplemento;
use Illuminate\Http\Request;

class VitrineController extends Controller
{
    public function index(Request $request)
    {
        $slugCategoria = $request->query('categoria');

        $categorias = Categoria::query()
            ->where('ativo', true)
            ->withCount(['produtos' => fn ($q) => $q->where('ativo', true)])
            ->orderBy('nome')
            ->get();

        $produtos = Produto::ativos()
            ->with(['categoria:id,nome,slug', 'complementosAtivos'])
            ->when(
                $slugCategoria,
                fn ($q) => $q->whereHas(
                    'categoria',
                    fn ($c) => $c->where('slug', $slugCategoria)->where('ativo', true)
                )
            )
            ->orderByDesc('destaque')
            ->orderBy('nome')
            ->get();

        // Destaques só fazem sentido na vitrine sem filtro (comportamento fixo).
        $destaques = Produto::ativos()
            ->where('destaque', true)
            ->with('complementosAtivos')
            ->orderBy('nome')
            ->take(4)
            ->get();

        $dados = [
            'categorias' => $categorias,
            'produtos' => $produtos,
            'destaques' => $destaques,
            'categoriaAtiva' => $slugCategoria,
            'banners' => Banner::noAr()->get(),
        ];

        // Filtro por categoria via AJAX: devolve só o bloco de resultados
        // (chips + grade), sem recarregar a página inteira.
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()
                ->view('vitrine.partials.resultados', $dados, 200)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return view('vitrine.index', $dados);
    }

    /**
     * Gatilho de atualização: hash de preço/estoque dos produtos exibidos.
     * O cliente consulta de tempo em tempo; mudou o hash, recarrega a vitrine.
     */
    public function versao(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->filter(fn ($v) => ctype_digit($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $impressao = Produto::ativos()
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->get(['id', 'preco', 'estoque'])
            ->map(fn ($p) => $p->id.':'.$p->preco.':'.($p->estoque ?? 'x'))
            ->join('|');

        // Complementos também entram no hash: preço/nome mudando deve
        // renovar a vitrine para o comprador nunca escolher valor velho.
        $compImpressao = ProdutoComplemento::whereHas('produto', fn ($q) => $q->where('ativo', true))
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('produto_id', $ids->all()))
            ->orderBy('id')
            ->get(['id', 'produto_id', 'tipo', 'nome', 'preco', 'ativo'])
            ->map(fn ($c) => $c->id.':'.$c->produto_id.':'.$c->tipo.':'.$c->nome.':'.$c->preco.':'.($c->ativo ? '1' : '0'))
            ->join('|');

        return response()->json(['hash' => md5($impressao.'#'.$compImpressao)]);
    }
}

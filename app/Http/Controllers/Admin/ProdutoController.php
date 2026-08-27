<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProdutoController extends Controller
{
    public function create(Request $request): View
    {
        return view('admin.produto_form', [
            'produto' => new Produto,
            'categorias' => $this->categorias(),
            'voltandoPara' => $request->query('estoque'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        $dados['slug'] = $this->slugUnico($dados['nome']);
        $dados['imagem'] = $this->salvarImagem($request);

        Produto::create($dados);

        return redirect()
            ->route('admin.produtos.index')
            ->with('sucesso_produtos', texto('admin_produtos', 'sucesso.criado', 'Produto criado e disponível na vitrine!'));
    }

    public function edit(Produto $produto): View
    {
        return view('admin.produto_form', [
            'produto' => $produto,
            'categorias' => $this->categorias(),
        ]);
    }

    public function update(Request $request, Produto $produto): RedirectResponse
    {
        $dados = $this->validar($request, $produto);

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $this->salvarImagem($request);
        }

        $produto->update($dados);

        return redirect()
            ->route('admin.produtos.index')
            ->with('sucesso_produtos', texto('admin_produtos', 'sucesso.atualizado', 'Produto atualizado!'));
    }

    public function destroy(Produto $produto): RedirectResponse
    {
        $produto->delete();

        return redirect()
            ->route('admin.produtos.index')
            ->with('sucesso_produtos', texto('admin_produtos', 'sucesso.removido', 'Produto removido — é possível restaurá-lo pela Auditoria.'));
    }

    protected function categorias()
    {
        return Categoria::orderBy('nome')->get();
    }

    protected function validar(Request $request, ?Produto $produto = null): array
    {
        $dados = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nome' => ['required', 'string', 'min:2', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'preco' => ['required', 'numeric', 'min:0', 'max:99999'],
            'estoque' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'estoque_minimo' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'destaque' => ['nullable', 'boolean'],
            'ativo' => ['nullable', 'boolean'],
        ], [
            'required' => texto('admin_produtos', 'erro.campo_obrigatorio', 'Preencha este campo.'),
            'categoria_id.exists' => texto('admin_produtos', 'erro.categoria', 'Escolha uma categoria válida.'),
            '*.numeric' => texto('admin_produtos', 'erro.numero', 'Informe um número válido.'),
            '*.integer' => texto('admin_produtos', 'erro.estoque_invalido', 'Informe um número inteiro.'),
            '*.min' => texto('admin_produtos', 'erro.numero', 'Informe um número válido.'),
        ]);

        $dados['estoque'] = $dados['estoque'] ?? null;
        $dados['estoque_minimo'] = (int) ($dados['estoque_minimo'] ?? 5);
        $dados['destaque'] = $request->boolean('destaque');
        $dados['ativo'] = $request->boolean('ativo');

        if ($produto && $produto->estoque === null && $request->input('estoque') === null) {
            $dados['estoque'] = null; // preserva "sem controle"
        }

        return $dados;
    }

    protected function slugUnico(string $nome): string
    {
        $base = Str::slug($nome);
        $slug = $base;
        $i = 2;

        while (Produto::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function salvarImagem(Request $request): ?string
    {
        if (! $request->hasFile('imagem')) {
            return null;
        }

        $arquivo = $request->file('imagem');
        $nome = Str::slug(pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME))
            .'-'.time().'.'.strtolower($arquivo->getClientOriginalExtension());

        $pasta = public_path('img/produtos');

        if (! is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }

        $arquivo->move($pasta, $nome);

        return 'img/produtos/'.$nome;
    }

    public function index(Request $request): View
    {
        $busca = $request->query('q');
        $filtro = $request->query('estoque');

        $produtos = Produto::query()
            ->with('categoria:id,nome,slug')
            ->when($busca, fn ($q) => $q->where(fn ($w) => $w
                ->where('nome', 'like', "%{$busca}%")
                ->orWhereHas('categoria', fn ($c) => $c->where('nome', 'like', "%{$busca}%"))))
            ->when($filtro === 'critico', fn ($q) => $q
                ->whereNotNull('estoque')
                ->whereColumn('estoque', '<=', 'estoque_minimo'))
            ->when($filtro === 'esgotado', fn ($q) => $q->whereNotNull('estoque')->where('estoque', '<=', 0))
            ->orderByRaw('(estoque IS NULL) asc, estoque asc, nome asc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.produtos', [
            'produtos' => $produtos,
            'busca' => $busca,
            'filtro' => $filtro,
            'totalCriticos' => Produto::query()
                ->whereNotNull('estoque')
                ->whereColumn('estoque', '<=', 'estoque_minimo')
                ->count(),
        ]);
    }

    public function atualizarEstoque(Request $request, Produto $produto): JsonResponse
    {
        // Vazio = sem quantidade definida → produto fica INDISPONÍVEL na venda
        // (regra da loja: só se vende com quantidade maior que zero).
        $dados = $request->validate([
            'estoque' => ['nullable', 'regex:/^\d{1,6}$/'],
            'estoque_minimo' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [
            'estoque.regex' => texto('admin_produtos', 'erro.estoque_invalido', 'Informe um número inteiro.'),
            '*.integer' => texto('admin_produtos', 'erro.estoque_invalido', 'Informe um número inteiro.'),
            '*.min' => texto('admin_produtos', 'erro.estoque_invalido', 'Informe um número inteiro.'),
        ]);

        if (array_key_exists('estoque', $dados)) {
            $produto->estoque = ($dados['estoque'] === null || trim((string) $dados['estoque']) === '')
                ? null
                : (int) $dados['estoque'];
        }

        if (! empty($dados['estoque_minimo'])) {
            $produto->estoque_minimo = (int) $dados['estoque_minimo'];
        }

        $produto->save();

        return response()->json([
            'mensagem' => texto('admin_produtos', 'sucesso.atualizado', 'Produto atualizado!'),
            'estoque' => $produto->estoque,
            'estoque_minimo' => $produto->estoque_minimo,
        ]);
    }

    public function alternarAtivo(Produto $produto): JsonResponse
    {
        $produto->update(['ativo' => ! $produto->ativo]);

        return response()->json([
            'mensagem' => texto('admin_produtos', 'sucesso.atualizado', 'Produto atualizado!'),
            'ativo' => $produto->ativo,
        ]);
    }

    public function alternarDestaque(Produto $produto): JsonResponse
    {
        $produto->update(['destaque' => ! $produto->destaque]);

        return response()->json([
            'mensagem' => texto('admin_produtos', 'sucesso.atualizado', 'Produto atualizado!'),
            'destaque' => $produto->destaque,
        ]);
    }
}

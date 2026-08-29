<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\ProdutoComplemento;
use App\Models\ProdutoEstoque;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

        $produto = Produto::create($dados);
        $this->sincronizarComplementos($produto, $request);
        $this->salvarEstoque($produto, $request);

        return redirect()
            ->route('admin.produtos.index')
            ->with('sucesso_produtos', texto('admin_produtos', 'sucesso.criado', 'Produto criado e disponível na vitrine!'));
    }

    public function edit(Produto $produto): View
    {
        $produto->load('complementos');

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
        $this->sincronizarComplementos($produto, $request);
        $this->salvarEstoque($produto, $request);

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
            'imagem' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'destaque' => ['nullable', 'boolean'],
            'ativo' => ['nullable', 'boolean'],
            'complementos' => ['nullable', 'array'],
            'complementos.*.id' => ['nullable', 'integer'],
            'complementos.*.tipo' => ['nullable', 'in:'.implode(',', ProdutoComplemento::TIPOS)],
            'complementos.*.nome' => ['nullable', 'string', 'max:120'],
            'complementos.*.preco' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'complementos.*.ordem' => ['nullable', 'integer', 'min:0'],
        ], [
            'required' => texto('admin_produtos', 'erro.campo_obrigatorio', 'Preencha este campo.'),
            'categoria_id.exists' => texto('admin_produtos', 'erro.categoria', 'Escolha uma categoria válida.'),
            'imagem.image' => texto('admin_produtos', 'erro.imagem_invalida', 'O arquivo precisa ser uma imagem JPG, PNG ou WEBP.'),
            'imagem.mimes' => texto('admin_produtos', 'erro.imagem_invalida', 'O arquivo precisa ser uma imagem JPG, PNG ou WEBP.'),
            'imagem.max' => texto('admin_produtos', 'erro.imagem_grande', 'A imagem passa de 4 MB — escolha uma mais leve.'),
            '*.numeric' => texto('admin_produtos', 'erro.numero', 'Informe um número válido.'),
        ]);

        $dados['destaque'] = $request->boolean('destaque');
        $dados['ativo'] = $request->boolean('ativo');

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
        $extensao = $arquivo->extension() ?: 'jpg';

        if (! in_array(strtolower($extensao), ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extensao = 'jpg';
        }

        $nome = Str::slug(pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME))
            .'-'.time().'.'.strtolower($extensao);

        $pasta = public_path('img/produtos');

        if (! is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }

        $arquivo->move($pasta, $nome);

        return 'img/produtos/'.$nome;
    }

    protected function sincronizarComplementos(Produto $produto, Request $request): void
    {
        $linhas = $request->input('complementos', []);
        $existentes = ProdutoComplemento::where('produto_id', $produto->id)->get()->keyBy('id');
        $mantidos = [];

        foreach ((array) $linhas as $indice => $linha) {
            $linha = (array) $linha;
            $nome = trim((string) ($linha['nome'] ?? ''));

            if ($nome === '') {
                continue;
            }

            $tipo = in_array($linha['tipo'] ?? null, ProdutoComplemento::TIPOS, true) ? $linha['tipo'] : 'adicional';
            $preco = $tipo === 'remocao' ? 0.0 : (float) ($linha['preco'] ?? 0);
            $ordem = (int) ($linha['ordem'] ?? $indice * 10);

            $dados = [
                'tipo' => $tipo,
                'nome' => $nome,
                'preco' => round($preco, 2),
                'ordem' => $ordem,
                'ativo' => true,
            ];

            $id = $linha['id'] ?? null;
            if ($id && isset($existentes[$id])) {
                $existentes[$id]->update($dados);
                $mantidos[] = (int) $id;
            } else {
                $criado = $produto->complementos()->create($dados);
                $mantidos[] = (int) $criado->id;
            }
        }

        foreach ($existentes as $id => $complemento) {
            if (! in_array((int) $id, $mantidos, true)) {
                $complemento->delete();
            }
        }
    }

    protected function salvarEstoque(Produto $produto, Request $request): void
    {
        $lojaId = loja_atual_id();

        if ($lojaId === null) {
            return;
        }

        $dados = $request->validate([
            'estoque' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'estoque_minimo' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [
            '*.integer' => texto('admin_produtos', 'erro.estoque_invalido', 'Informe um número inteiro.'),
        ]);

        $dados['estoque'] = array_key_exists('estoque', $dados) ? (int) $dados['estoque'] : null;

        if (array_key_exists('estoque', $dados) && $dados['estoque'] === null) {
            $dados['estoque'] = null;
        }

        $minimo = (int) ($dados['estoque_minimo'] ?? 5);

        $estoqueAtual = ProdutoEstoque::where('produto_id', $produto->id)
            ->where('loja_id', $lojaId)
            ->first();

        if ($estoqueAtual) {
            if (array_key_exists('estoque', $dados)) {
                $estoqueAtual->estoque = $dados['estoque'];
            }
            $estoqueAtual->estoque_minimo = $minimo;
            $estoqueAtual->save();
        } else {
            ProdutoEstoque::create([
                'produto_id' => $produto->id,
                'loja_id' => $lojaId,
                'estoque' => $dados['estoque'] ?? null,
                'estoque_minimo' => $minimo,
            ]);
        }
    }

    public function index(Request $request): View
    {
        $busca = $request->query('q');
        $filtro = $request->query('estoque');
        $lojaId = loja_atual_id();

        $produtos = Produto::query()
            ->with(['categoria:id,nome,slug', 'estoques' => fn ($q) => $q->when($lojaId, fn ($e) => $e->where('loja_id', $lojaId))])
            ->when($busca, fn ($q) => $q->where(fn ($w) => $w
                ->where('nome', 'like', "%{$busca}%")
                ->orWhereHas('categoria', fn ($c) => $c->where('nome', 'like', "%{$busca}%"))))
            ->when($filtro === 'critico', fn ($q) => $q
                ->whereHas('estoques', fn ($e) => $e
                    ->whereNotNull('estoque')
                    ->whereColumn('estoque', '<=', 'estoque_minimo')
                    ->when($lojaId, fn ($s) => $s->where('loja_id', $lojaId))))
            ->when($filtro === 'esgotado', fn ($q) => $q
                ->whereHas('estoques', fn ($e) => $e
                    ->whereNotNull('estoque')
                    ->where('estoque', '<=', 0)
                    ->when($lojaId, fn ($s) => $s->where('loja_id', $lojaId))))
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $totalCriticos = (clone $produtos)->total();

        return view('admin.produtos', [
            'produtos' => $produtos,
            'busca' => $busca,
            'filtro' => $filtro,
            'totalCriticos' => $totalCriticos,
        ]);
    }

    public function atualizarEstoque(Request $request, Produto $produto): JsonResponse
    {
        $lojaId = loja_atual_id();

        $dados = $request->validate([
            'estoque' => ['nullable', 'regex:/^\d{1,6}$/'],
            'estoque_minimo' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [
            'estoque.regex' => texto('admin_produtos', 'erro.estoque_invalido', 'Informe um número inteiro.'),
            '*.integer' => texto('admin_produtos', 'erro.estoque_invalido', 'Informe um número inteiro.'),
            '*.min' => texto('admin_produtos', 'erro.estoque_invalido', 'Informe um número inteiro.'),
        ]);

        $novoEstoque = array_key_exists('estoque', $dados)
            ? (trim((string) $dados['estoque']) === '' ? null : (int) $dados['estoque'])
            : null;

        $novoMinimo = ! empty($dados['estoque_minimo']) ? (int) $dados['estoque_minimo'] : 5;

        if ($lojaId !== null) {
            ProdutoEstoque::updateOrCreate(
                ['produto_id' => $produto->id, 'loja_id' => $lojaId],
                ['estoque' => $novoEstoque, 'estoque_minimo' => $novoMinimo]
            );
        }

        $estoqueRetorno = $lojaId !== null
            ? ProdutoEstoque::where('produto_id', $produto->id)->where('loja_id', $lojaId)->first()
            : null;

        return response()->json([
            'mensagem' => texto('admin_produtos', 'sucesso.atualizado', 'Produto atualizado!'),
            'estoque' => $estoqueRetorno?->estoque,
            'estoque_minimo' => $estoqueRetorno?->estoque_minimo ?? 5,
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

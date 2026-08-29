<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Produto;
use Fabricioagsf\AuthMulti\Models\Tenant as Loja;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LojasController extends Controller
{
    public function index(): View
    {
        $totalProdutos = Produto::where('ativo', true)->count();

        $lojas = Loja::query()->orderBy('id')->get()->map(function (Loja $loja) use ($totalProdutos) {
            $loja->total_produtos = $totalProdutos;
            $loja->total_pedidos = Pedido::semLoja()->where('loja_id', $loja->id)->count();

            return $loja;
        });

        return view('admin.lojas', [
            'lojas' => $lojas,
            'lojaAtual' => loja_atual(),
        ]);
    }

    public function create(): View
    {
        return view('admin.lojas_form', ['loja' => new Loja]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        Loja::create($dados);

        return redirect()
            ->route('admin.lojas.index')
            ->with('sucesso_lojas', texto('admin_lojas', 'sucesso.criado', 'Loja criada!'));
    }

    public function edit(Loja $loja): View
    {
        return view('admin.lojas_form', ['loja' => $loja]);
    }

    public function update(Request $request, Loja $loja): RedirectResponse
    {
        $dados = $this->validar($request, $loja);

        $loja->update($dados);

        return redirect()
            ->route('admin.lojas.index')
            ->with('sucesso_lojas', texto('admin_lojas', 'sucesso.atualizado', 'Loja atualizada!'));
    }

    /**
     * Troca a loja ativa do painel (sessão).
     */
    public function trocar(Request $request): RedirectResponse
    {
        $dados = $request->validate(
            ['loja_id' => ['required', 'integer', 'exists:tenants,id']],
            ['loja_id.required' => texto('admin_lojas', 'erro.obrigatorio', 'Escolha uma loja.')]
        );

        $loja = Loja::find((int) $dados['loja_id']);

        if (! $loja || $loja->status !== 'ativo') {
            return back()->withErrors(['loja_id' => texto('admin_lojas', 'erro.suspensa', 'Essa loja está suspensa.')]);
        }

        session(['loja_id' => $loja->id]);

        return back()->with(
            'sucesso_lojas',
            str_replace(':nome', $loja->nome, texto('admin_lojas', 'sucesso.trocada', 'Você está na loja :nome.'))
        );
    }

    public function alternarStatus(Loja $loja): JsonResponse
    {
        $loja->update(['status' => $loja->status === 'ativo' ? 'suspenso' : 'ativo']);

        return response()->json([
            'mensagem' => $loja->status === 'ativo'
                ? texto('admin_lojas', 'sucesso.ativa', 'Loja ativada — aparece no seletor dos clientes.')
                : texto('admin_lojas', 'sucesso.suspensa', 'Loja suspensa — não aparece para os clientes.'),
            'status' => $loja->status,
        ]);
    }

    protected function validar(Request $request, ?Loja $loja = null): array
    {
        $id = $loja?->id;

        return $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:tenants,slug'.($id ? ','.$id : '')],
            'dominio' => ['nullable', 'string', 'max:190', 'unique:tenants,dominio'.($id ? ','.$id : '')],
            'status' => ['required', 'in:ativo,suspenso'],
        ], [
            'nome.required' => texto('admin_lojas', 'erro.nome_obrigatorio', 'Informe o nome da loja.'),
            'slug.required' => texto('admin_lojas', 'erro.slug_obrigatorio', 'Informe o identificador da loja.'),
            'slug.regex' => texto('admin_lojas', 'erro.slug_invalido', 'Use apenas letras minúsculas, números e hífen.'),
            'slug.unique' => texto('admin_lojas', 'erro.slug_duplicado', 'Já existe uma loja com esse identificador.'),
            'dominio.unique' => texto('admin_lojas', 'erro.dominio_duplicado', 'Já existe uma loja com esse domínio.'),
            'status.in' => texto('admin_lojas', 'erro.obrigatorio', 'Escolha uma opção válida.'),
        ], [
            'nome' => texto('admin_lojas', 'campo.nome', 'Nome'),
            'slug' => texto('admin_lojas', 'campo.slug', 'Identificador'),
            'dominio' => texto('admin_lojas', 'campo.dominio', 'Domínio (opcional)'),
            'status' => texto('admin_lojas', 'campo.status', 'Situação'),
        ]);
    }
}

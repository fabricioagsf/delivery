<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners', [
            'banners' => Banner::query()->orderBy('ordem')->orderByDesc('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.banners_form', ['banner' => new Banner]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate($this->regras(), $this->mensagens());

        $dados['imagem'] = $this->salvarImagem($request);
        $dados['ativo'] = $request->boolean('ativo');

        Banner::create($dados);

        return redirect()
            ->route('admin.banners.index')
            ->with('sucesso_banners', texto('admin_banners', 'sucesso.criado', 'Banner criado! Entra e sai do ar conforme o agendamento.'));
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners_form', ['banner' => $banner]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $dados = $request->validate($this->regras(false), $this->mensagens());

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $this->salvarImagem($request);
        }

        $dados['ativo'] = $request->boolean('ativo');
        $banner->update($dados);

        return redirect()
            ->route('admin.banners.index')
            ->with('sucesso_banners', texto('admin_banners', 'sucesso.atualizado', 'Banner atualizado!'));
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $caminho = public_path($banner->imagem);

        if (Str::startsWith($banner->imagem, 'img/banners/') && file_exists($caminho)) {
            @unlink($caminho);
        }

        $banner->delete();

        return redirect()
            ->route('admin.banners.index')
            ->with('sucesso_banners', texto('admin_banners', 'sucesso.removido', 'Banner removido.'));
    }

    public function alternarAtivo(Banner $banner): JsonResponse
    {
        $banner->update(['ativo' => ! $banner->ativo]);

        $mensagem = $banner->ativo
            ? texto('admin_banners', 'sucesso.ligado', 'Banner ligado — entra no ar conforme o agendamento.')
            : texto('admin_banners', 'sucesso.desligado', 'Banner desligado — saiu do ar.');

        return response()->json(['mensagem' => $mensagem, 'ativo' => $banner->ativo]);
    }

    protected function regras(bool $criando = true): array
    {
        return [
            'titulo' => ['nullable', 'string', 'max:120'],
            'imagem' => $criando
                ? ['required', 'image', 'mimes:jpeg,png,webp', 'max:4096']
                : ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'link' => ['nullable', 'string', 'max:255'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:999'],
            'inicio_em' => ['nullable', 'date'],
            'fim_em' => ['nullable', 'date', 'after:inicio_em'],
        ];
    }

    protected function mensagens(): array
    {
        return [
            'imagem.required' => texto('admin_banners', 'erro.imagem_obrigatoria', 'Escolha uma imagem para o banner.'),
            'imagem.image' => texto('admin_banners', 'erro.imagem_invalida', 'O arquivo precisa ser uma imagem JPG, PNG ou WEBP.'),
            'imagem.mimes' => texto('admin_banners', 'erro.imagem_invalida', 'O arquivo precisa ser uma imagem JPG, PNG ou WEBP.'),
            'imagem.max' => texto('admin_banners', 'erro.imagem_grande', 'A imagem passa de 4 MB — escolha uma mais leve.'),
            'fim_em.after' => texto('admin_banners', 'erro.periodo_invalido', 'O fim do agendamento precisa ser depois do início.'),
            '*.integer' => texto('admin_banners', 'erro.ordem_invalida', 'A ordem precisa ser um número.'),
        ];
    }

    protected function salvarImagem(Request $request): string
    {
        $arquivo = $request->file('imagem');

        $nome = Str::slug(pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME))
            .'-'.time().'.'.strtolower($arquivo->getClientOriginalExtension());

        $pasta = public_path('img/banners');

        if (! is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }

        $arquivo->move($pasta, $nome);

        return 'img/banners/'.$nome;
    }
}

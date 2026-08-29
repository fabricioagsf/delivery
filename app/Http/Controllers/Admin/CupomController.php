<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Cupom;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CupomController extends Controller
{
    public function index(): View
    {
        return view('admin.cupons', [
            'cupons' => Cupom::query()->orderByDesc('id')->get(),
            'destaque' => config_loja('cupom_destaque'),
        ]);
    }

    public function create(): View
    {
        return view('admin.cupons_form', ['cupom' => new Cupom]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate($this->regras(), $this->mensagens(), $this->atributos());
        $dados['ativo'] = $request->boolean('ativo');
        $dados['tipo'] = $request->input('tipo', 'percentual');

        Cupom::create($dados);

        return redirect()
            ->route('admin.cupons.index')
            ->with('sucesso_cupons', texto('admin_cupons', 'sucesso.criado', 'Cupom criado!'));
    }

    public function edit(Cupom $cupom): View
    {
        return view('admin.cupons_form', ['cupom' => $cupom]);
    }

    public function update(Request $request, Cupom $cupom): RedirectResponse
    {
        $dados = $request->validate($this->regras(), $this->mensagens(), $this->atributos());
        $dados['ativo'] = $request->boolean('ativo');
        $dados['tipo'] = $request->input('tipo', 'percentual');

        $cupom->update($dados);

        return redirect()
            ->route('admin.cupons.index')
            ->with('sucesso_cupons', texto('admin_cupons', 'sucesso.atualizado', 'Cupom atualizado!'));
    }

    public function destroy(Cupom $cupom): RedirectResponse
    {
        $cupom->delete();

        return redirect()
            ->route('admin.cupons.index')
            ->with('sucesso_cupons', texto('admin_cupons', 'sucesso.removido', 'Cupom removido.'));
    }

    public function alternarAtivo(Cupom $cupom): JsonResponse
    {
        $cupom->update(['ativo' => ! $cupom->ativo]);

        $mensagem = $cupom->ativo
            ? texto('admin_cupons', 'sucesso.ligado', 'Cupom ativo — pode ser usado.')
            : texto('admin_cupons', 'sucesso.desligado', 'Cupom desativado — não aceita mais usos.');

        return response()->json(['mensagem' => $mensagem, 'ativo' => $cupom->ativo]);
    }

    public function divulgar(Cupom $cupom): RedirectResponse
    {
        Configuracao::updateOrCreate(
            ['loja_id' => loja_atual_id(), 'chave' => 'cupom_destaque'],
            ['valor' => $cupom->codigo]
        );

        return redirect()
            ->route('admin.cupons.index')
            ->with('sucesso_cupons', str_replace(':codigo', $cupom->codigo, texto('admin_cupons', 'sucesso.divulgado', 'Agora a promoção :codigo aparece na vitrine e no cardápio.')));
    }

    public function pararDivulgacao(): RedirectResponse
    {
        Configuracao::updateOrCreate(
            ['loja_id' => loja_atual_id(), 'chave' => 'cupom_destaque'],
            ['valor' => '']
        );

        return redirect()
            ->route('admin.cupons.index')
            ->with('sucesso_cupons', texto('admin_cupons', 'sucesso.parou_divulgacao', 'Promoção retirada da vitrine e do cardápio.'));
    }

    protected function regras(): array
    {
        return [
            'codigo' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'tipo' => ['required', 'in:percentual,fixo'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'valor_minimo' => ['nullable', 'numeric', 'min:0'],
            'limite_usos' => ['nullable', 'integer', 'min:1'],
            'inicio_em' => ['nullable', 'date'],
            'fim_em' => ['nullable', 'date', 'after:inicio_em'],
        ];
    }

    protected function mensagens(): array
    {
        return [
            'codigo.required' => texto('admin_cupons', 'erro.codigo_obrigatorio', 'Informe o código do cupom.'),
            'codigo.regex' => texto('admin_cupons', 'erro.codigo_invalido', 'Use apenas letras, números, _ e - no código.'),
            'valor.required' => texto('admin_cupons', 'erro.valor_obrigatorio', 'Informe o valor do desconto.'),
            '*.numeric' => texto('admin_cupons', 'erro.numero', 'Informe um número válido.'),
            '*.in' => texto('admin_cupons', 'erro.opcao_invalida', 'Escolha uma opção válida.'),
            'fim_em.after' => texto('admin_cupons', 'erro.periodo_invalido', 'O fim precisa ser depois do início.'),
        ];
    }

    protected function atributos(): array
    {
        return [
            'codigo' => texto('admin_cupons', 'campo.codigo', 'Código'),
            'valor' => texto('admin_cupons', 'campo.valor', 'Valor do desconto'),
            'valor_minimo' => texto('admin_cupons', 'campo.valor_minimo', 'Pedido mínimo'),
            'limite_usos' => texto('admin_cupons', 'campo.limite_usos', 'Limite de usos'),
            'inicio_em' => texto('admin_cupons', 'campo.inicio_em', 'Início'),
            'fim_em' => texto('admin_cupons', 'campo.fim_em', 'Fim'),
        ];
    }
}

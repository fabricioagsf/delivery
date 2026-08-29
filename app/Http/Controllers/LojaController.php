<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Fabricioagsf\AuthMulti\Models\Tenant as Loja;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LojaController extends Controller
{
    /**
     * Troca a loja ativa da sessão (cliente e admin).
     * Usado no seletor de loja da vitrine e na área do admin (tela de lojas).
     */
    public function trocar(Request $request): RedirectResponse|JsonResponse
    {
        $dados = $request->validate(
            ['loja_id' => ['required', 'integer', 'exists:tenants,id']],
            ['loja_id.required' => texto('loja', 'erro.obrigatorio', 'Escolha uma loja.')]
        );

        $loja = Loja::find((int) $dados['loja_id']);

        if (! $loja || $loja->status !== 'ativo') {
            return response()->json(['mensagem' => texto('loja', 'erro.suspensa', 'Essa loja está suspensa.')], 422);
        }

        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            session(['loja_id' => $loja->id]);

            return response()->json([
                'mensagem' => str_replace(':nome', $loja->nome, texto('loja', 'sucesso.trocada', 'Você está na loja :nome.')),
                'loja_id' => $loja->id,
                'nome' => $loja->nome,
                'slug' => $loja->slug,
            ]);
        }

        session(['loja_id' => $loja->id]);

        return redirect()->back()->with(
            'sucesso', str_replace(':nome', $loja->nome, texto('loja', 'sucesso.trocada', 'Você está na loja :nome.'))
        );
    }
}

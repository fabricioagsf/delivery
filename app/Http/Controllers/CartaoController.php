<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartaoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // O número completo NUNCA é salvo: usamos apenas para detectar a bandeira
        // e guardar os 4 últimos dígitos.
        $request->merge([
            'numero_limpo' => preg_replace('/\D/', '', (string) $request->input('numero')),
        ]);

        $dados = $request->validate([
            'apelido' => ['required', 'string', 'max:80'],
            'numero_limpo' => ['required', 'digits_between:13,19'],
            'validade' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'titular' => ['nullable', 'string', 'max:150'],
        ], [
            'required' => texto('conta', 'erro.campo_obrigatorio_attr', 'Preencha o campo :attribute.'),
            'numero_limpo.digits_between' => texto('conta', 'erro.cartao_invalido', 'Número de cartão inválido.'),
            'validade.regex' => texto('conta', 'erro.validade_formato', 'Use o formato MM/AA.'),
        ]);

        $numeroLimpo = $dados['numero_limpo'];
        unset($dados['numero_limpo']);

        $cartao = auth('cliente')->user()->cartoes()->create([
            ...$dados,
            'bandeira' => detectar_bandeira($numeroLimpo),
            'numero_final' => substr($numeroLimpo, -4),
        ]);

        return response()->json([
            'mensagem' => texto('conta', 'sucesso.cartao', 'Cartão salvo!'),
            'id' => $cartao->id,
        ]);
    }

    public function destroy(Cartao $cartao): JsonResponse
    {
        if (auth('cliente')->id() !== $cartao->cliente_id) {
            return response()->json([
                'mensagem' => texto('conta', 'erro.sem_permissao', 'Este registro não pertence à sua conta.'),
            ], 403);
        }

        $cartao->delete();

        return response()->json([
            'mensagem' => texto('conta', 'sucesso.cartao_removido', 'Cartão removido.'),
        ]);
    }
}

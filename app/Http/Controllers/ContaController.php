<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ContaController extends Controller
{
    /**
     * Painel do cliente (menu lateral): dados, endereços, cartões e pedidos.
     * A chave de segurança nunca é retornada — ela é validada na entrega.
     */
    public function painel(): JsonResponse
    {
        $cliente = auth('cliente')->user();

        return response()->json([
            'autenticado' => true,
            'cliente' => [
                'nome' => $cliente->nome,
                'telefone' => $cliente->telefone,
                'email' => $cliente->email,
            ],
            'enderecos' => $cliente->enderecos->map(fn ($e) => [
                'id' => $e->id,
                'rua' => $e->rua,
                'numero' => $e->numero,
                'complemento' => $e->complemento,
                'bairro' => $e->bairro,
                'cidade' => $e->cidade,
                'cep' => $e->cep,
                'principal' => $e->principal,
            ]),
            'cartoes' => $cliente->cartoes->map(fn ($c) => [
                'id' => $c->id,
                'apelido' => $c->apelido,
                'bandeira' => $c->bandeira,
                'numero_final' => $c->numero_final,
                'validade' => $c->validade,
            ]),
            'pedidos' => $cliente->pedidos->take(10)->map(fn ($p) => [
                'codigo' => $p->codigo,
                'status' => $p->status,
                'status_label' => status_pedido($p->status),
                'tipo_entrega' => $p->tipo_entrega,
                'tipo_label' => texto('conta', 'tipo.'.$p->tipo_entrega, ucfirst($p->tipo_entrega)),
                'forma_pagamento' => $p->forma_pagamento,
                'forma_label' => forma_pagamento_label($p->forma_pagamento),
                'total' => preco_br($p->total),
                'data' => $p->created_at?->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function atualizarDados(Request $request): JsonResponse
    {
        $cliente = auth('cliente')->user();

        $dados = $request->validate([
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'telefone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', 'unique:clientes,email,'.$cliente->id],
            'chave_seguranca_atual' => ['nullable', 'string'],
            'chave_seguranca_nova' => ['nullable', 'string', 'min:4', 'max:20'],
        ], [
            'required' => texto('conta', 'erro.campo_obrigatorio_attr', 'Preencha o campo :attribute.'),
            'email.unique' => 'Este e-mail já está em uso.',
        ], [
            'nome' => texto('conta', 'campo.nome', 'Nome completo'),
            'telefone' => texto('conta', 'campo.telefone', 'Telefone / WhatsApp'),
            'email' => texto('conta', 'campo.email', 'E-mail'),
        ]);

        if (! empty($dados['chave_seguranca_nova'])) {
            if (! Hash::check((string) ($dados['chave_seguranca_atual'] ?? ''), $cliente->chave_seguranca)) {
                return response()->json([
                    'erros' => ['chave_seguranca_atual' => [texto('conta', 'erro.chave_atual', 'Chave atual incorreta.')]],
                ], 422);
            }

            $dados['chave_seguranca'] = Hash::make($dados['chave_seguranca_nova']);
        }

        $cliente->update([
            'nome' => $dados['nome'],
            'telefone' => $dados['telefone'],
            'email' => strtolower($dados['email']),
            ...(isset($dados['chave_seguranca']) ? ['chave_seguranca' => $dados['chave_seguranca']] : []),
        ]);

        return response()->json([
            'mensagem' => texto('conta', 'sucesso.dados', 'Dados atualizados!'),
            'cliente' => [
                'nome' => $cliente->nome,
                'telefone' => $cliente->telefone,
                'email' => $cliente->email,
            ],
        ]);
    }

    /**
     * Completa o cadastro de quem entrou por login social:
     * telefone e chave de segurança (nome/e-mail já vieram do provedor).
     */
    public function completarCadastro(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'telefone' => ['required', 'string', 'max:30'],
            'chave_seguranca' => ['required', 'string', 'min:4', 'max:20', 'confirmed'],
        ], [
            'required' => texto('conta', 'erro.campo_obrigatorio_attr', 'Preencha o campo :attribute.'),
            '*.confirmed' => texto('conta', 'erro.chave_confirma', 'As chaves não conferem.'),
            '*.min' => texto('conta', 'erro.chave_min', 'A chave deve ter no mínimo 4 caracteres.'),
        ], [
            'telefone' => texto('conta', 'campo.telefone', 'Telefone / WhatsApp'),
            'chave_seguranca' => texto('conta', 'chave.titulo', 'Chave de segurança'),
        ]);

        auth('cliente')->user()->update([
            'telefone' => $dados['telefone'],
            'chave_seguranca' => Hash::make($dados['chave_seguranca']),
        ]);

        session()->forget('completar_cadastro');

        return response()->json([
            'mensagem' => texto('conta', 'sucesso.completo', 'Cadastro completo! Sua chave de segurança será pedida na entrega.'),
        ]);
    }

    /**
     * Troca de senha do cliente (espontânea ou obrigatória após reenvio).
     * Nova senha: qualquer uma com 6+ caracteres — pode repetir a antiga.
     */
    public function trocarSenha(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'senha' => ['required', 'confirmed', 'string', 'min:6', 'max:60'],
        ], [
            'required' => texto('conta', 'erro.campo_obrigatorio_attr', 'Preencha o campo :attribute.'),
            '*.confirmed' => texto('conta', 'erro.senha_confirma', 'As senhas não conferem.'),
            '*.min' => texto('conta', 'erro.senha_curta', 'A senha precisa ter pelo menos 6 caracteres.'),
        ], [
            'senha' => texto('conta', 'campo.nova_senha', 'Nova senha'),
        ]);

        auth('cliente')->user()->update(['senha' => Hash::make($dados['senha'])]);

        session()->forget('trocar_senha_obrigatoria');

        return response()->json([
            'mensagem' => texto('conta', 'sucesso.trocada', 'Nova senha salva! Use-a nos próximos acessos.'),
        ]);
    }
}

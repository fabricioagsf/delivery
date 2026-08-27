<?php

namespace App\Http\Controllers;

use App\Models\Endereco;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnderecoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'rua' => ['required', 'string', 'max:200'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'bairro' => ['required', 'string', 'max:120'],
            'cidade' => ['required', 'string', 'max:120'],
            'cep' => ['nullable', 'string', 'max:12'],
            'principal' => ['nullable', 'boolean'],
        ], [
            'required' => texto('conta', 'erro.campo_obrigatorio_attr', 'Preencha o campo :attribute.'),
        ]);

        $cliente = auth('cliente')->user();
        $primeiroEndereco = $cliente->enderecos()->count() === 0;

        if (! empty($dados['principal']) || $primeiroEndereco) {
            $cliente->enderecos()->update(['principal' => false]);
            $dados['principal'] = true;
        } else {
            $dados['principal'] = false;
        }

        $endereco = $cliente->enderecos()->create($dados);

        return response()->json([
            'mensagem' => texto('conta', 'sucesso.endereco', 'Endereço salvo!'),
            'id' => $endereco->id,
        ]);
    }

    public function destroy(Endereco $endereco): JsonResponse
    {
        $this->autorizar($endereco->cliente_id);
        $eraPrincipal = $endereco->principal;
        $endereco->delete();

        if ($eraPrincipal) {
            auth('cliente')->user()->enderecos()->first()?->update(['principal' => true]);
        }

        return response()->json([
            'mensagem' => texto('conta', 'sucesso.endereco_removido', 'Endereço removido.'),
        ]);
    }

    public function principal(Endereco $endereco): JsonResponse
    {
        $this->autorizar($endereco->cliente_id);

        auth('cliente')->user()->enderecos()->update(['principal' => false]);
        $endereco->update(['principal' => true]);

        return response()->json([
            'mensagem' => texto('conta', 'sucesso.endereco_principal', 'Endereço principal definido.'),
        ]);
    }

    protected function autorizar($clienteId): void
    {
        if (auth('cliente')->id() !== $clienteId) {
            throw new HttpResponseException(response()->json([
                'mensagem' => texto('conta', 'erro.sem_permissao', 'Este registro não pertence à sua conta.'),
            ], 403));
        }
    }
}

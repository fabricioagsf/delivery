<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\WhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(): View
    {
        $clientes = Cliente::query()
            ->withCount('pedidos')
            ->withSum('pedidos as total_gasto', 'total')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.clientes', [
            'clientes' => $clientes,
            'totalContas' => Cliente::count(),
            'comPedidos' => Cliente::has('pedidos')->count(),
            'novosNoMes' => Cliente::where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }

    /**
     * Redefine a senha do cliente para a padrão 123Mudar e envia pelo
     * WhatsApp (API oficial quando configurada; link pronto como fallback).
     * Ao entrar com essa senha temporária, o sistema obriga troca.
     */
    public function senhaWhatsapp(Cliente $cliente): JsonResponse
    {
        $cliente->update(['senha' => Hash::make(Cliente::SENHA_TEMPORARIA)]);

        $mensagem = str_replace(
            ':nome',
            explode(' ', $cliente->nome)[0],
            texto('admin_clientes', 'whats.mensagem_senha', 'Olá :nome! Sua senha de acesso à loja Gostosuras foi redefinida. Entre com a senha temporária 123Mudar — o sistema vai pedir uma nova senha na hora.')
        );

        $api = app(WhatsApp::class);
        $enviada = null;

        if ($api->disponivel()) {
            $enviada = $api->enviarTexto($cliente->telefone, $mensagem);
        }

        return response()->json([
            'mensagem' => $enviada === null
                ? str_replace(':nome', $cliente->nome, texto('admin_clientes', 'sucesso.senha', 'Nova senha gerada para :nome.'))
                : ($enviada['ok']
                    ? str_replace(':nome', $cliente->nome, texto('admin_clientes', 'sucesso.senha_api', 'Senha redefinida e enviada pelo WhatsApp para :nome.'))
                    : str_replace([':nome', ':motivo'], [$cliente->nome, $enviada['erro']], texto('admin_clientes', 'aviso.senha_api', 'Senha redefinida, mas o envio falhou (:motivo) — abra o link pronto.'))),
            'modo' => $enviada === null ? 'link' : ($enviada['ok'] ? 'api' : 'link'),
            'senha' => Cliente::SENHA_TEMPORARIA,
            'whats' => $this->linkWhatsapp($cliente->telefone, $mensagem),
        ]);
    }

    /**
     * Campanha/oferta: envia a mensagem para os clientes escolhidos (ou todos).
     * Com a API configurada o envio é direto; sem API, devolve os links
     * prontos para abrir as conversas.
     */
    public function campanha(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'mensagem' => ['required', 'string', 'min:5', 'max:500'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ], [
            'mensagem.required' => texto('admin_clientes', 'erro.mensagem', 'Escreva a mensagem da oferta.'),
            'mensagem.min' => texto('admin_clientes', 'erro.mensagem_curta', 'A mensagem está muito curta.'),
        ]);

        $clientes = Cliente::query()
            ->when(! empty($dados['ids']), fn ($q) => $q->whereIn('id', $dados['ids']))
            ->orderBy('nome')
            ->get();

        $api = app(WhatsApp::class);

        if ($api->disponivel()) {
            $enviados = 0;
            $falhas = [];

            foreach ($clientes as $cliente) {
                $mensagem = str_replace(':nome', explode(' ', $cliente->nome)[0], $dados['mensagem']);
                $resultado = $api->enviarTexto($cliente->telefone, $mensagem);

                if ($resultado['ok']) {
                    $enviados++;
                } else {
                    $falhas[] = ['nome' => $cliente->nome, 'erro' => $resultado['erro']];
                }
            }

            return response()->json([
                'modo' => 'api',
                'quantidade' => $clientes->count(),
                'enviados' => $enviados,
                'falhas' => $falhas,
            ]);
        }

        return response()->json([
            'modo' => 'link',
            'quantidade' => $clientes->count(),
            'links' => $clientes->map(fn ($cliente) => [
                'nome' => $cliente->nome,
                'whats' => $this->linkWhatsapp(
                    $cliente->telefone,
                    str_replace(':nome', explode(' ', $cliente->nome)[0], $dados['mensagem'])
                ),
            ]),
        ]);
    }

    protected function linkWhatsapp(string $telefone, string $mensagem): string
    {
        $digitos = preg_replace('/\D/', '', $telefone);

        // Assume Brasil quando faltam o código do país
        if (strlen($digitos) <= 11) {
            $digitos = '55' . $digitos;
        }

        return 'https://wa.me/' . $digitos . '?text=' . rawurlencode($mensagem);
    }
}

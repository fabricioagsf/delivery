<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Fabricioagsf\AuthMulti\Models\Usuario;
use App\Services\LoginSocial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Login social (Google / Facebook / Microsoft): cria a conta no primeiro
 * acesso ou entra na existente. Conta nova vem com senha temporária
 * (123Mudar) e chave provisória — o drawer obriga a completar o cadastro.
 */
class SocialLoginController extends Controller
{
    public function redirecionar(string $provedor): RedirectResponse
    {
        if (! LoginSocial::ativo($provedor)) {
            return redirect()
                ->route('vitrine')
                ->with('erro_social', texto('conta', 'social.indisponivel', 'Este login social não está disponível no momento.'));
        }

        return redirect()->away(LoginSocial::urlAutorizacao($provedor));
    }

    public function callback(Request $request, string $provedor): RedirectResponse
    {
        if (! LoginSocial::ativo($provedor)) {
            return redirect()
                ->route('vitrine')
                ->with('erro_social', texto('conta', 'social.indisponivel', 'Este login social não está disponível no momento.'));
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()
                ->route('vitrine')
                ->with('erro_social', texto('conta', 'social.cancelado', 'Login cancelado.'));
        }

        if (! hash_equals((string) session('login_social_estado', ''), (string) $request->query('state', ''))) {
            return redirect()
                ->route('vitrine')
                ->with('erro_social', texto('conta', 'social.estado_invalido', 'Sessão de login expirada — tente de novo.'));
        }

        session()->forget('login_social_estado');

        try {
            $usuario = LoginSocial::buscarUsuario($provedor, (string) $request->query('code'));
        } catch (\RuntimeException $e) {
            return redirect()->route('vitrine')->with('erro_social', $e->getMessage());
        }

        $tenantId = loja_atual_id();

        if ($tenantId === null) {
            return redirect()->route('vitrine')->with(
                'erro_social',
                texto('conta', 'erro.sem_loja', 'Nenhuma loja ativa no momento — tente de novo em instantes.')
            );
        }

        // Verificar se já existe um usuario auth-multi com este email nesta loja
        $usuarioAuth = Usuario::where('email', $usuario['email'])
            ->where('tipo', 'cliente')
            ->where('tenant_id', $tenantId)
            ->first();

        $cliente = Cliente::where('email', $usuario['email'])->first();
        $novoCadastro = false;

        if (! $usuarioAuth) {
            $usuarioAuth = Usuario::create([
                'tenant_id' => $tenantId,
                'tipo' => 'cliente',
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'senha' => Cliente::SENHA_TEMPORARIA,
            ]);
            $novoCadastro = true;
        }

        if (! $cliente) {
            $cliente = Cliente::create([
                'usuario_id' => $usuarioAuth->id,
                'nome' => $usuario['nome'],
                'telefone' => '',
                'email' => $usuario['email'],
                'senha' => Hash::make(Cliente::SENHA_TEMPORARIA),
                'chave_seguranca' => Hash::make(Str::random(8)),
            ]);
            $novoCadastro = true;
        } elseif (! $cliente->usuario_id) {
            $cliente->update(['usuario_id' => $usuarioAuth->id]);
        }

        Auth::guard('auth_multi')->login($usuarioAuth, true);
        $request->session()->regenerate();

        // Tambem loga no guard 'cliente' para compatibilidade com controllers existentes
        Auth::guard('cliente')->login($cliente);

        if ($novoCadastro) {
            session(['completar_cadastro' => true]);
        } elseif (Hash::check(Cliente::SENHA_TEMPORARIA, $usuarioAuth->senha)) {
            session(['trocar_senha_obrigatoria' => true]);
        }

        return redirect()->route('vitrine');
    }
}

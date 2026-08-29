<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Fabricioagsf\AuthMulti\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ClienteAuthController extends Controller
{
    public function registrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'telefone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', 'unique:clientes,email'],
            'senha' => ['required', 'confirmed', Password::min(6)],
            'chave_seguranca' => ['required', 'string', 'min:4', 'max:20', 'confirmed'],
        ], [
            'required' => texto('conta', 'erro.campo_obrigatorio_attr', 'Preencha o campo :attribute.'),
            'email.unique' => texto('conta', 'erro.email_ja_cadastrado', 'Este e-mail já está cadastrado.'),
            '*.confirmed' => texto('conta', 'erro.confirmar_senha', 'A confirmação não conferiu.'),
            'chave_seguranca.min' => texto('conta', 'erro.chave_min', 'A chave deve ter no mínimo 4 caracteres.'),
        ], [
            'nome' => texto('conta', 'campo.nome', 'Nome completo'),
            'telefone' => texto('conta', 'campo.telefone', 'Telefone / WhatsApp'),
            'email' => texto('conta', 'campo.email', 'E-mail'),
            'senha' => texto('conta', 'campo.senha', 'Senha'),
            'chave_seguranca' => texto('conta', 'chave.titulo', 'Chave de segurança'),
        ]);

        $tenantId = loja_atual_id();

        if ($tenantId === null) {
            return response()->json([
                'mensagem' => texto('conta', 'erro.sem_loja', 'Nenhuma loja ativa no momento — tente de novo em instantes.'),
            ], 422);
        }

        $usuario = Usuario::create([
            'tenant_id' => $tenantId,
            'tipo' => 'cliente',
            'nome' => $dados['nome'],
            'email' => strtolower($dados['email']),
            'senha' => $dados['senha'],
        ]);

        $cliente = Cliente::create([
            'usuario_id' => $usuario->id,
            'nome' => $dados['nome'],
            'telefone' => $dados['telefone'],
            'email' => strtolower($dados['email']),
            'senha' => Hash::make($dados['senha']),
            'chave_seguranca' => Hash::make($dados['chave_seguranca']),
        ]);

        Auth::guard('auth_multi')->login($usuario);

        // Tambem loga no guard 'cliente' para compatibilidade com controllers existentes
        Auth::guard('cliente')->login($cliente);

        return response()->json([
            'autenticado' => true,
            'mensagem' => texto('conta', 'sucesso.registrado', 'Conta criada com sucesso!'),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $credenciais = $request->validate([
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ], [
            'required' => texto('conta', 'erro.campo_obrigatorio_attr', 'Preencha o campo :attribute.'),
            'email' => texto('conta', 'erro.email_invalido', 'Informe um e-mail válido.'),
        ], [
            'email' => texto('conta', 'campo.email', 'E-mail'),
            'senha' => texto('conta', 'campo.senha', 'Senha'),
        ]);

        $lojaId = loja_atual_id();

        $usuario = $lojaId === null
            ? null
            : Usuario::where('email', strtolower($credenciais['email']))
                ->where('tipo', 'cliente')
                ->where('tenant_id', $lojaId)
                ->where('ativo', true)
                ->first();

        if (! $usuario || ! Hash::check($credenciais['senha'], $usuario->senha)) {
            $ehAdmin = $lojaId !== null
                && Usuario::where('email', strtolower($credenciais['email']))
                    ->where('tipo', 'admin')
                    ->where('tenant_id', $lojaId)
                    ->exists();

            if ($ehAdmin) {
                return response()->json([
                    'mensagem' => texto('conta', 'erro.eh_admin', 'Este e-mail é da administração da loja. Use o painel em /admin para entrar.'),
                ], 422);
            }

            return response()->json([
                'mensagem' => texto('conta', 'erro.credenciais', 'E-mail ou senha incorretos.'),
            ], 422);
        }

        Auth::guard('auth_multi')->login($usuario, $request->boolean('manter_conectado'));
        $request->session()->regenerate();

        $cliente = Cliente::where('usuario_id', $usuario->id)->first();

        // Tambem loga no guard 'cliente' para compatibilidade com controllers existentes
        if ($cliente) {
            Auth::guard('cliente')->login($cliente);
        }

        if ($cliente && Hash::check(Cliente::SENHA_TEMPORARIA, $usuario->senha)) {
            session(['trocar_senha_obrigatoria' => true]);
        }

        return response()->json([
            'autenticado' => true,
            'troca_obrigatoria' => $cliente ? Hash::check(Cliente::SENHA_TEMPORARIA, $usuario->senha) : false,
            'mensagem' => texto('conta', 'sucesso.logado', 'Bem-vindo de volta!'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('auth_multi')->logout();
        Auth::guard('cliente')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['autenticado' => false]);
    }
}

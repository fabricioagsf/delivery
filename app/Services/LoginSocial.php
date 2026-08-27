<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Login social (Google, Facebook e Microsoft/Windows Live) via OAuth 2.0
 * puro — sem pacotes externos. Credenciais ficam na tabela configuracoes
 * (editáveis no painel): {provedor}_login_ativo, {provedor}_client_id,
 * {provedor}_client_secret.
 */
class LoginSocial
{
    public const PROVEDORES = ['google', 'facebook', 'microsoft', 'instagram'];

    private const DEFINICOES = [
        'google' => [
            'autorizar' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token' => 'https://oauth2.googleapis.com/token',
            'usuario' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'escopos' => 'openid email profile',
        ],
        'facebook' => [
            'autorizar' => 'https://www.facebook.com/v21.0/dialog/oauth',
            'token' => 'https://graph.facebook.com/v21.0/oauth/access_token',
            'usuario' => 'https://graph.facebook.com/me?fields=name,email',
            'escopos' => 'email public_profile',
        ],
        'microsoft' => [
            'autorizar' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'usuario' => 'https://graph.microsoft.com/oidc/userinfo',
            'escopos' => 'openid email profile',
        ],
        'instagram' => [
            'autorizar' => 'https://www.facebook.com/v21.0/dialog/oauth',
            'token' => 'https://graph.facebook.com/v21.0/oauth/access_token',
            'usuario' => 'https://graph.facebook.com/me?fields=name,email',
            'escopos' => 'email public_profile',
        ],
    ];

    public static function existe(string $provedor): bool
    {
        return in_array($provedor, self::PROVEDORES, true);
    }

    public static function ativo(string $provedor): bool
    {
        return self::existe($provedor)
            && config_loja($provedor.'_login_ativo') === '1'
            && trim((string) config_loja($provedor.'_client_id')) !== ''
            && trim((string) config_loja($provedor.'_client_secret')) !== '';
    }

    public static function ativos(): array
    {
        return array_values(array_filter(
            self::PROVEDORES,
            fn ($p) => self::ativo($p)
        ));
    }

    public static function urlAutorizacao(string $provedor): string
    {
        $def = self::DEFINICOES[$provedor];
        $estado = bin2hex(random_bytes(16));
        session(['login_social_estado' => $estado]);

        return $def['autorizar'].'?'.http_build_query([
            'client_id' => trim((string) config_loja($provedor.'_client_id')),
            'redirect_uri' => route('cliente.social.callback', $provedor),
            'response_type' => 'code',
            'scope' => $def['escopos'],
            'state' => $estado,
        ]);
    }

    /**
     * Troca o código pelo token e busca nome/e-mail do usuário.
     *
     * @return array{nome: string, email: string}
     *
     * @throws \RuntimeException com mensagem humana quando falha
     */
    public static function buscarUsuario(string $provedor, string $codigo): array
    {
        $def = self::DEFINICOES[$provedor];

        try {
            $token = Http::asForm()->acceptJson()->timeout(15)->post($def['token'], [
                'client_id' => trim((string) config_loja($provedor.'_client_id')),
                'client_secret' => trim((string) config_loja($provedor.'_client_secret')),
                'redirect_uri' => route('cliente.social.callback', $provedor),
                'code' => $codigo,
                'grant_type' => 'authorization_code',
            ]);

            if (! $token->successful() || empty($token->json('access_token'))) {
                Log::warning("Login social [{$provedor}]: provedor recusou o token.", [
                    'status' => $token->status(),
                    'resposta' => $token->json(),
                ]);

                throw new \RuntimeException(texto('conta', 'social.erro_token', 'O provedor recusou a conexão — tente entrar de novo.'));
            }

            $usuario = Http::withToken($token->json('access_token'))
                ->acceptJson()
                ->timeout(15)
                ->get($def['usuario']);

            $email = strtolower((string) $usuario->json('email'));
            $nome = trim((string) ($usuario->json('name') ?? $usuario->json('displayName') ?? ''));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException(texto('conta', 'social.erro_email', 'O provedor não compartilhou seu e-mail — use outro jeito de entrar.'));
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning("Login social [{$provedor}]: falha na conexão/busca do usuário.", [
                'erro' => $e->getMessage(),
            ]);

            throw new \RuntimeException(texto('conta', 'social.erro_conexao', 'Não foi possível conectar ao provedor de login agora.'));
        }

        return ['nome' => $nome !== '' ? $nome : explode('@', $email)[0], 'email' => $email];
    }
}

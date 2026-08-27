<?php

return [

    /*
    |----------------------------------------------------------------------
    | Modo de operacao (defina no .env do projeto)
    |----------------------------------------------------------------------
    |
    | admin_cliente                  -> admin + cliente
    |                                  (cliente entra por e-mail/senha OU social)
    |
    | admin_prestador_cliente        -> admin + prestador de servicos + cliente
    |                                  (todos entram apenas por e-mail/senha)
    |
    | admin_prestador_cliente_social -> admin + prestador de servicos + cliente
    |                                  (cliente tambem pode entrar por social)
    */

    'modo' => env('AUTH_MULTI_MODO', 'admin_cliente'),

    /*
    |----------------------------------------------------------------------
    | Tabelas do banco de dados (MySQL) com primary key propria
    |----------------------------------------------------------------------
    */

    'tabelas' => [
        'tenants' => 'tenants',
        'usuarios' => 'usuarios',
        'sociais' => 'usuarios_sociais',
    ],

    /*
    |----------------------------------------------------------------------
    | Rotas
    |----------------------------------------------------------------------
    */

    'rotas' => [
        'prefixo_admin' => 'admin',
        'prefixo_social' => 'auth/social',
        'middleware_web' => ['web'],

        // O projeto define sua própria tela de login admin (via lib auth-multi).
        // Apontamos o middleware para a rota da lib.
        'admin_login' => 'authmulti.admin.tela',
    ],

    /*
    |----------------------------------------------------------------------
    | Para onde cada perfil vai depois de entrar / sair
    |----------------------------------------------------------------------
    */

    'redirecionamentos' => [
        'admin' => '/admin/painel',
        'prestador' => '/prestador',
        'cliente' => '/',
        'pos_logout' => '/',
    ],

    /*
    |----------------------------------------------------------------------
    | Suporte a sistemas SaaS (multi-tenant por dominio)
    |----------------------------------------------------------------------
    | Quando ativo, o dominio/subdominio acessado e casado com a coluna
    | `dominio` da tabela `tenants`. O login passa a considerar somente os
    | usuarios daquele tenant. Sem correspondencia, o acesso nao fica
    | vinculado a nenhum tenant (uso em projeto unico).
    */

    'tenant_por_dominio' => true,

    /*
    |----------------------------------------------------------------------
    | Login social nativo (OAuth2 via cURL, sem bibliotecas externas)
    |----------------------------------------------------------------------
    | As credenciais NUNCA ficam no codigo: venha sempre do .env.
    */

    'social' => [
        'criar_usuario_automatico' => true,

        'google' => [
            'habilitado' => env('AUTH_MULTI_GOOGLE_HABILITADO', false),
            'client_id' => env('AUTH_MULTI_GOOGLE_CLIENT_ID'),
            'client_secret' => env('AUTH_MULTI_GOOGLE_CLIENT_SECRET'),
            'redirect' => env('AUTH_MULTI_GOOGLE_REDIRECT'),
        ],

        'facebook' => [
            'habilitado' => env('AUTH_MULTI_FACEBOOK_HABILITADO', false),
            'client_id' => env('AUTH_MULTI_FACEBOOK_CLIENT_ID'),
            'client_secret' => env('AUTH_MULTI_FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('AUTH_MULTI_FACEBOOK_REDIRECT'),
        ],

        'microsoft' => [
            'habilitado' => env('AUTH_MULTI_MICROSOFT_HABILITADO', false),
            'client_id' => env('AUTH_MULTI_MICROSOFT_CLIENT_ID'),
            'client_secret' => env('AUTH_MULTI_MICROSOFT_CLIENT_SECRET'),
            'redirect' => env('AUTH_MULTI_MICROSOFT_REDIRECT'),
            'tenant' => env('AUTH_MULTI_MICROSOFT_TENANT', 'common'),
        ],

        'instagram' => [
            'habilitado' => env('AUTH_MULTI_INSTAGRAM_HABILITADO', false),
            'client_id' => env('AUTH_MULTI_INSTAGRAM_CLIENT_ID'),
            'client_secret' => env('AUTH_MULTI_INSTAGRAM_CLIENT_SECRET'),
            'redirect' => env('AUTH_MULTI_INSTAGRAM_REDIRECT'),
        ],
    ],
];

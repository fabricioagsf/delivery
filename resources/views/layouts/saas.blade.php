<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SaaS — @yield('titulo', 'Painel')</title>
    <link rel="stylesheet" href="{{ url('css/admin.css') . '?v=' . filemtime(public_path('css/admin.css')) }}">
    <style>
        .saas-badge { background: #6366f1; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 6px; }
        .saas-shell { background: #f5f3ff; }
        .saas-shell .lateral { background: #312e81; color: #ede9fe; }
        .saas-shell .lateral a { color: #c7d2fe; }
        .saas-shell .lateral a:hover { background: rgba(255,255,255,0.1); }
        .saas-shell .lateral__marca { color: white; }
    </style>
</head>
<body class="saas-shell">
<div class="admin-shell">
    <aside class="lateral">
        <div class="lateral__marca">
            SaaS
            <small>{{ saas_empresa_atual()?->nome ?? 'Painel' }}</small>
        </div>
        <nav class="lateral__menu">
            <a href="{{ route('saas.dashboard') }}" class="{{ request()->routeIs('saas.dashboard') ? 'ativo' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('saas.operar.index') }}" class="{{ request()->routeIs('saas.operar.*') ? 'ativo' : '' }}">
                Operar filial
            </a>
            <p class="lateral__grupo">Plataforma</p>
            <a href="{{ route('saas.empresas.index') }}" class="{{ request()->routeIs('saas.empresas.*') ? 'ativo' : '' }}">
                Empresas
            </a>
            <a href="{{ route('saas.employees.index') }}" class="{{ request()->routeIs('saas.employees.*') ? 'ativo' : '' }}">
                Funcionários
            </a>
            <a href="{{ route('saas.filiais.index') }}" class="{{ request()->routeIs('saas.filiais.*') ? 'ativo' : '' }}">
                Filiais
            </a>
            <a href="{{ route('saas.modulos.index') }}" class="{{ request()->routeIs('saas.modulos.*') ? 'ativo' : '' }}">
                Módulos
            </a>
            <p class="lateral__grupo">Empresa ativa</p>
            <a href="{{ route('saas.empresas.config', saas_empresa_atual()?->id) }}" class="{{ request()->routeIs('saas.empresas.config.*') ? 'ativo' : '' }}">
                Configurações
            </a>
            <a href="{{ route('saas.comissoes.index', saas_empresa_atual()?->id) }}" class="{{ request()->routeIs('saas.comissoes.*') ? 'ativo' : '' }}">
                Comissões
            </a>
        </nav>
        <form method="POST" action="{{ route('saas.logout') }}" class="saas-logout">
            @csrf
            <button type="submit" class="botao botao--fantasma">Sair</button>
        </form>
    </aside>
    <main class="principal">
        <header class="principal__topo">
            <h1>@yield('titulo_pagina', 'Painel SaaS')</h1>
            <div class="principal__acoes">
                @yield('acoes')
            </div>
        </header>
        @if(session('sucesso'))
            <div class="alerta alerta--sucesso">{{ session('sucesso') }}</div>
        @endif
        @yield('conteudo')
    </main>
</div>
</body>
</html>

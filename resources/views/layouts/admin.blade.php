<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', texto('admin_layout','titulo','Painel — Gostosuras'))</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
<div class="admin-shell">
    <aside class="lateral">
        <div class="lateral__marca">
            {{ texto('layout', 'site.nome', 'Gostosuras') }}
            <small>{{ texto('admin_layout', 'marca.painel', 'painel') }}</small>
        </div>

        <nav class="lateral__menu">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.dashboard', 'Dashboard') }}
            </a>
            <a href="{{ route('admin.pedidos.index') }}" class="{{ request()->routeIs('admin.pedidos.*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.pedidos', 'Pedidos') }}
            </a>
            <a href="{{ route('admin.produtos.index') }}" class="{{ request()->routeIs('admin.produtos.*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.produtos', 'Produtos e estoque') }}
                @if(isset($totalCriticos) && $totalCriticos > 0)
                    <span class="bolha">{{ $totalCriticos }}</span>
                @endif
            </a>
            <a href="{{ route('admin.relatorios') }}" class="{{ request()->routeIs('admin.relatorios*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.relatorios', 'Relatórios') }}
            </a>
            <a href="{{ route('admin.clientes.index') }}" class="{{ request()->routeIs('admin.clientes*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.clientes', 'Clientes') }}
            </a>
            <a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.banners', 'Banners') }}
            </a>
            <a href="{{ route('admin.auditoria.index') }}" class="{{ request()->routeIs('admin.auditoria.*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.auditoria', 'Auditoria') }}
            </a>
            <a href="{{ route('admin.item-venda.index') }}" class="{{ request()->routeIs('admin.item-venda.*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.item_venda', 'Produtos e serviços') }}
            </a>
            <a href="{{ route('admin.configuracoes.index') }}" class="{{ request()->routeIs('admin.configuracoes*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.configuracoes', 'Configurações') }}
            </a>
            <a href="{{ route('admin.help') }}" class="{{ request()->routeIs('admin.help') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.help', 'Ajuda') }}
            </a>
        </nav>

        <div class="lateral__pe">
            <a href="{{ route('vitrine') }}" target="_blank">{{ texto('admin_layout', 'menu.ver_loja', 'Ver loja') }}</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">{{ texto('conta', 'botao.sair', 'Sair da conta') }}</button>
            </form>
        </div>
    </aside>

    <main class="principal">
        <h1 class="principal__titulo">@yield('titulo_pagina')</h1>
        @yield('conteudo')
    </main>
</div>

<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
@stack('scripts')
</body>
</html>

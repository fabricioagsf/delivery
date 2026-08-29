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
            {{ loja_atual()?->nome ?? texto('layout', 'site.nome', 'Gostosuras') }}
            <small>{{ texto('admin_layout', 'marca.painel', 'painel') }}</small>
        </div>

        @php
            $lojasMenu = lojas_ativas();
        @endphp
        @if($lojasMenu->count() > 1)
            <div class="lateral__loja">
                <form method="POST" action="{{ route('admin.lojas.trocar') }}" class="loja-switcher">
                    @csrf
                    <label class="loja-switcher__rotulo">{{ texto('admin_layout', 'loja.atual', 'Loja ativa') }}</label>
                    <select name="loja_id" class="loja-switcher__select" onchange="this.form.submit()">
                        @foreach($lojasMenu as $loja)
                            <option value="{{ $loja->id }}" {{ loja_atual()?->id === $loja->id ? 'selected' : '' }}>
                                {{ $loja->nome }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @elseif(loja_atual())
            <div class="lateral__loja">
                <span class="lateral__loja-nome">{{ loja_atual()->nome }}</span>
            </div>
        @endif

        <nav class="lateral__menu">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.dashboard', 'Dashboard') }}
            </a>

            <p class="lateral__grupo">{{ texto('admin_layout', 'categoria.vendas', 'Vendas') }}</p>
            <a href="{{ route('admin.pedidos.index') }}" class="{{ request()->routeIs('admin.pedidos.*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.pedidos', 'Pedidos') }}
            </a>
            <a href="{{ route('admin.clientes.index') }}" class="{{ request()->routeIs('admin.clientes*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.clientes', 'Clientes') }}
            </a>
            <a href="{{ route('admin.relatorios') }}" class="{{ request()->routeIs('admin.relatorios*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.relatorios', 'Relatórios') }}
            </a>

            <p class="lateral__grupo">{{ texto('admin_layout', 'categoria.catalogo', 'Catálogo') }}</p>
            <a href="{{ route('admin.produtos.index') }}" class="{{ request()->routeIs('admin.produtos.*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.produtos', 'Produtos e estoque') }}
                @if(isset($totalCriticos) && $totalCriticos > 0)
                    <span class="bolha">{{ $totalCriticos }}</span>
                @endif
            </a>
            <a href="{{ route('admin.item-venda.index') }}" class="{{ request()->routeIs('admin.item-venda.*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.item_venda', 'Produtos e serviços') }}
            </a>

            <p class="lateral__grupo">{{ texto('admin_layout', 'categoria.promocao', 'Promoção') }}</p>
            <a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.banners', 'Banners') }}
            </a>
            <a href="{{ route('admin.cupons.index') }}" class="{{ request()->routeIs('admin.cupons*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.cupons', 'Cupons') }}
            </a>
            <a href="{{ route('admin.fidelidade.index') }}" class="{{ request()->routeIs('admin.fidelidade*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.fidelidade', 'Fidelidade') }}
            </a>

            <p class="lateral__grupo">{{ texto('admin_layout', 'categoria.sistema', 'Sistema') }}</p>
            <a href="{{ route('admin.configuracoes.index') }}" class="{{ request()->routeIs('admin.configuracoes*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.configuracoes', 'Configurações') }}
            </a>
            <a href="{{ route('admin.lojas.index') }}" class="{{ request()->routeIs('admin.lojas*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.lojas', 'Lojas') }}
            </a>
            <a href="{{ route('admin.pwa.index') }}" class="{{ request()->routeIs('admin.pwa*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.pwa', 'PWA / App') }}
            </a>
            <a href="{{ route('admin.mesas.index') }}" class="{{ request()->routeIs('admin.mesas*') && !request()->routeIs('admin.mesas-controle*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.mesas', 'Mesas (QR)') }}
            </a>
            <a href="{{ route('admin.mesas-controle.index') }}" class="{{ request()->routeIs('admin.mesas-controle*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.mesas_controle', 'Pedidos das mesas') }}
            </a>
            <a href="{{ route('admin.auditoria.index') }}" class="{{ request()->routeIs('admin.auditoria.*') ? 'ativo' : '' }}">
                {{ texto('admin_layout', 'menu.auditoria', 'Auditoria') }}
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

<script>
    window.Rotas = {
        produtoEstoque: @json(route('admin.produtos.estoque', ['produto' => 'ID'])),
        produtoAtivo: @json(route('admin.produtos.ativo', ['produto' => 'ID'])),
        produtoDestaque: @json(route('admin.produtos.destaque', ['produto' => 'ID'])),
        pedidoStatus: @json(route('admin.pedidos.status', ['pedido' => 'ID'])),
        mesasControleEstado: @json(route('admin.mesas-controle.estado')),
        mesasControleDetalhe: @json(route('admin.mesas-controle.detalhe', ['mesa' => '__ID__']))
    };
</script>
<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
@stack('scripts')
</body>
</html>

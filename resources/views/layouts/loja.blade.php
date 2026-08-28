<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffb59c">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon.svg') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('titulo', tema_texto('nome', 'Guloseimas'))</title>
    <link rel="stylesheet" href="{{ asset('css/loja.css') }}?v={{ filemtime(public_path('css/loja.css')) }}">
    @if($cssTema = tema_css())
        <link rel="stylesheet" href="{{ asset($cssTema) }}?v={{ filemtime(public_path($cssTema)) }}">
    @endif
</head>
<body>

<header class="topo">
    <div class="topo__conteudo">
        <a href="{{ route('vitrine') }}" class="logo">
            {{ tema_texto('nome', 'Guloseimas') }}
            <small>{{ tema_texto('slogan', 'doces artesanais') }}</small>
        </a>

        <nav class="topo__acoes">
            <a href="{{ route('cardapio') }}" class="botao-botao-conta">
                <span>{{ texto('layout', 'menu.cardapio', 'Cardápio') }}</span>
            </a>
            <button type="button" class="botao-botao-conta oculto" id="instalar-app">
                <span>{{ texto('layout', 'menu.instalar', 'Instalar app') }}</span>
            </button>
            <button type="button" class="botao-botao-conta" id="abrir-conta">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4 0-8 2-8 5v3h16v-3c0-3-4-5-8-5Z"/></svg>
                <span>{{ auth('cliente')->check() ? texto('layout','menu.conta','Minha conta') : texto('layout','menu.conta_entrar','Entrar') }}</span>
            </button>

            <a href="{{ route('carrinho.index') }}" class="botao-carrinho" aria-label="{{ texto('layout','menu.carrinho','Carrinho') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 18a2 2 0 1 0 2 2 2 2 0 0 0-2-2Zm10 0a2 2 0 1 0 2 2 2 2 0 0 0-2-2ZM8.2 14h9.9a2 2 0 0 0 1.9-1.4L22 5H6.2L5.5 3H2v2h2l3.6 7.6-1.4 2.5A2 2 0 0 0 7.9 18H19v-2H8.2Z"/></svg>
                <span class="carrinho-badge @if(app(App\Services\Carrinho::class)->contagem() === 0) oculto @endif" id="carrinho-badge">
                    {{ app(App\Services\Carrinho::class)->contagem() }}
                </span>
            </a>
        </nav>
    </div>
    <div class="topo__drips" aria-hidden="true">
        <svg viewBox="0 0 1440 40" preserveAspectRatio="none"><path d="M0,0 L1440,0 L1440,12 C1380,34 1330,8 1270,20 C1210,32 1160,10 1100,18 C1040,26 990,6 930,16 C870,26 820,8 760,18 C700,28 650,10 590,20 C530,30 480,6 420,16 C360,26 310,8 250,18 C190,28 140,10 80,20 C50,25 25,15 0,22 Z"/></svg>
    </div>
</header>

<main class="pagina">
    @yield('conteudo')
</main>

<footer class="rodape">
    <div class="rodape__colunas">
        <div>
            <h3>{{ tema_texto('nome', 'Guloseimas') }}</h3>
            <p>{{ tema_texto('sobre', 'Brigadeiros, chocolates e doces artesanais feitos com carinho para adoçar o seu dia.') }}</p>
        </div>
        <div>
            <h3>{{ texto('layout', 'rodape.pagamentos_titulo', 'Formas de pagamento') }}</h3>
            <ul class="rodape__lista">
                <li>{{ forma_pagamento_label('pix') }}</li>
                <li>{{ forma_pagamento_label('cartao') }}</li>
                <li>{{ forma_pagamento_label('dinheiro') }}</li>
            </ul>
        </div>
        <div>
            <h3>{{ texto('layout', 'rodape.entrega_titulo', 'Como você prefere receber') }}</h3>
            <ul class="rodape__lista">
                <li>{{ texto('layout', 'rodape.entrega_item', 'Entrega no seu endereço') }}</li>
                <li>{{ texto('layout', 'rodape.retirada_item', 'Retirada na loja') }}</li>
            </ul>
        </div>
    </div>
    <p class="rodape__direitos">
        {{ str_replace(':ano', now()->format('Y'), tema_texto('direitos', ':ano Guloseimas — feito com muito chocolate.')) }}
    </p>
</footer>

@include('partials.drawer')
@include('partials.modal-personalizar')

<script>
    window.Rotas = {
        carrinhoAdicionar: '{{ route('carrinho.adicionar') }}',
        carrinhoAtualizar: '{{ route('carrinho.atualizar') }}',
        carrinhoRemover: '{{ route('carrinho.remover') }}',
        versao: '{{ route('vitrine.versao') }}',
        csrf: '{{ route('cliente.csrf') }}',
        painel: '{{ route('cliente.painel') }}',
        login: '{{ route('cliente.login') }}',
        registro: '{{ route('cliente.registrar') }}',
        dados: '{{ route('cliente.dados') }}',
        enderecos: '{{ route('cliente.enderecos.store') }}',
        enderecoBase: '{{ url('cliente/enderecos') }}/ID',
        enderecoPrincipal: '{{ url('cliente/enderecos') }}/ID/principal',
        cartoes: '{{ route('cliente.cartoes.store') }}',
        cartaoBase: '{{ url('cliente/cartoes') }}/ID',
        logout: '{{ route('cliente.logout') }}',
        senha: '{{ route('cliente.senha') }}',
        completar: '{{ route('cliente.completar') }}'
    };
    window.Textos = {
        saudacao: '{{ texto('conta', 'js.saudacao', 'Olá, :nome!') }}',
        remover: '{{ texto('conta', 'js.remover', 'remover') }}',
        tornarPrincipal: '{{ texto('conta', 'js.tornar_principal', 'principal') }}',
        enderecoVazio: '{{ texto('conta', 'js.endereco_vazio', 'Nenhum endereço salvo ainda.') }}',
        cartaoVazio: '{{ texto('conta', 'js.cartao_vazio', 'Nenhum cartão salvo ainda.') }}',
        pedidoVazio: '{{ texto('conta', 'js.pedido_vazio', 'Você ainda não fez pedidos.') }}',
        erroInesperado: '{{ texto('conta', 'js.erro_inesperado', 'Algo saiu do ponto. Tente novamente.') }}',
        avisoAtualizacao: '{{ texto('vitrine', 'js.aviso_atualizacao', 'Valores e estoque acabaram de atualizar — vitrine renovada!') }}',
        modalAdicionais: '{{ texto('vitrine', 'modal.adicionais', 'Adicionais') }}',
        modalRemocoes: '{{ texto('vitrine', 'modal.remocoes', 'Remoções (grátis)') }}',
        modalCada: '{{ texto('vitrine', 'modal.cada', 'cada') }}',
        modalVazio: '{{ texto('vitrine', 'modal.vazio', 'Este produto não tem personalizações no momento.') }}',
        portaSenhaTitulo: '{{ texto('conta', 'porta.senha_titulo', 'Troque sua senha para continuar') }}',
        portaSenhaNota: '{{ texto('conta', 'porta.senha_nota', 'Você entrou com a senha temporária. Crie uma nova senha de pelo menos 6 caracteres para usar nos próximos acessos.') }}',
        portaCompletarTitulo: '{{ texto('conta', 'porta.completar_titulo', 'Falta pouco para terminar seu cadastro') }}',
        portaCompletarNota: '{{ texto('conta', 'porta.completar_nota', 'Confirme seu telefone e crie sua chave de segurança — ela será pedida na entrega do pedido.') }}'
    };
    window.ContaEstado = @json([
        'porta' => session('completar_cadastro') ? 'completar' : (session('trocar_senha_obrigatoria') ? 'senha' : null),
        'avisoSocial' => session('erro_social'),
    ]);
</script>
<script src="{{ asset('js/loja.js') }}?v={{ filemtime(public_path('js/loja.js')) }}"></script>
<script>
    (function () {
        // Registra o service worker (PWA — cardápio consultável offline) quando o módulo está ativo
        var pwaAtivo = {{ config_loja('pwa_ativo', '1') === '1' ? 'true' : 'false' }};
        if (pwaAtivo && 'serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ route('pwa.service_worker') }}').catch(function () {});
            });
        }

        // Botão "Instalar app" — aparece somente quando o navegador permite instalar
        var btnInstalar = document.getElementById('instalar-app');
        var promptInstalar = null;

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            promptInstalar = e;
            if (btnInstalar) btnInstalar.classList.remove('oculto');
        });

        if (btnInstalar) {
            btnInstalar.addEventListener('click', function () {
                if (!promptInstalar) return;
                promptInstalar.prompt();
                promptInstalar = null;
                btnInstalar.classList.add('oculto');
            });
        }
    })();
</script>
@stack('scripts')
</body>
</html>

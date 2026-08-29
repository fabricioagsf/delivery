@extends('layouts.admin')

@section('titulo', texto('admin_lojas', 'titulo', 'Lojas — Gostosuras'))
@section('titulo_pagina', texto('admin_lojas', 'titulo.pagina', 'Lojas da rede'))

@section('conteudo')
@if(session('sucesso_lojas'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_lojas') }}</div>
@endif

<div class="duas-colunas">
    <section class="painel-admin">
        <h2>{{ texto('admin_lojas', 'lista.titulo', 'Lojas cadastradas') }}</h2>
        <p class="nota-segura nota-segura--admin">{{ texto('admin_lojas', 'nota.explicacao', 'Cada loja tem seu próprio catálogo, pedidos, cupons e configurações. Nosso painel mostra os dados da loja ativa (seletor no topo) e a loja pública pode ser trocada pelo cliente no topo da vitrine.') }}</p>

        @forelse($lojas as $loja)
            <article class="cartao-cupom {{ $loja->status !== 'ativo' ? 'cartao-cupom--desligado' : '' }}">
                <div class="cartao-cupom__info">
                    <strong>
                        {{ $loja->nome }}
                        @if($lojaAtual?->id === $loja->id)
                            <span class="status-pilula status-pilula--entregue">{{ texto('admin_lojas', 'lista.ativa', 'ativa') }}</span>
                        @endif
                    </strong>
                    <small>
                        {{ texto('admin_lojas', 'lista.identificador', 'identificador') }}: <code>{{ $loja->slug }}</code>
                        @if($loja->dominio)
                            · <code>{{ $loja->dominio }}</code>
                        @endif
                        · {{ $loja->total_produtos }} {{ texto('admin_lojas', 'metrica.produtos', 'produtos') }}
                        · {{ $loja->total_pedidos }} {{ texto('admin_lojas', 'metrica.pedidos', 'pedidos') }}
                    </small>
                    <span class="status-pilula {{ $loja->status === 'ativo' ? 'status-pilula--entregue' : '' }}">
                        {{ $loja->status === 'ativo' ? texto('admin_lojas', 'status.ativo', 'Ativa') : texto('admin_lojas', 'status.suspenso', 'Suspensa') }}
                    </span>
                </div>
                <div class="cartao-cupom__acoes">
                    @if($lojaAtual?->id !== $loja->id)
                        <form method="POST" action="{{ route('admin.lojas.trocar') }}" class="form-inline">
                            @csrf
                            <input type="hidden" name="loja_id" value="{{ $loja->id }}">
                            <button type="submit" class="mini-botao mini-botao--salvar">{{ texto('admin_lojas', 'botao.trocar', 'Tornar ativa') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.lojas.edit', $loja) }}" class="mini-botao">{{ texto('admin_lojas', 'botao.editar', 'Editar') }}</a>
                    <button type="button" class="interruptor {{ $loja->status === 'ativo' ? 'ligado' : '' }}" data-funcao="alternar-loja" data-url="{{ route('admin.lojas.status', $loja) }}"
                            aria-label="{{ texto('admin_lojas', 'botao.suspender_ativar', 'Suspender ou ativar') }}"></button>
                </div>
            </article>
        @empty
            <p class="texto-suave">{{ texto('admin_lojas', 'lista.vazia', 'Nenhuma loja cadastrada ainda.') }}</p>
        @endforelse
    </section>

    <aside>
        <section class="painel-admin">
            <h2>{{ texto('admin_lojas', 'novo.titulo', 'Nova loja') }}</h2>
            <p class="texto-suave">{{ texto('admin_lojas', 'novo.dica', 'Crie uma loja nova da rede: ela começa com o catálogo vazio e usa os textos/configurações padrão da matriz.') }}</p>
            <a href="{{ route('admin.lojas.create') }}" class="botao botao--chefe bloco">{{ texto('admin_lojas', 'botao.criar', 'Criar loja') }}</a>
        </section>
    </aside>
</div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-funcao="alternar-loja"]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                fetch(botao.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).then(function (r) { return r.json(); }).then(function (r) {
                    var alerta = document.createElement('div');
                    alerta.className = 'alerta alerta--sucesso';
                    alerta.textContent = r.mensagem;
                    document.querySelector('.principal').prepend(alerta);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                });
            });
        });

        document.querySelectorAll('[data-confirmar]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirmar)) e.preventDefault();
            });
        });
    </script>
@endpush

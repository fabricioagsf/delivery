@extends('layouts.admin')

@section('titulo', texto('admin_banners', 'titulo', 'Banners — Gostosuras'))
@section('titulo_pagina', texto('admin_banners', 'titulo.pagina', 'Banners da vitrine'))

@section('conteudo')
@if(session('sucesso_banners'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_banners') }}</div>
@endif

<div class="duas-colunas">
    <section class="painel-admin">
        <h2>{{ texto('admin_banners', 'lista.titulo', 'Banners cadastrados') }}</h2>
        <p class="nota-segura nota-segura--admin">{{ texto('admin_banners', 'nota.agendamento', 'O banner entra no ar na data de início e sai na data de fim automaticamente. Deixe as datas vazias para ficar sempre no ar (enquanto estiver ligado).') }}</p>

        @forelse($banners as $banner)
            <article class="cartao-banner {{ ! $banner->ativo ? 'cartao-banner--desligado' : '' }}">
                <img src="{{ asset($banner->imagem) }}" alt="{{ $banner->titulo ?? texto('admin_banners', 'lista.sem_titulo', 'Banner') }}">
                <div class="cartao-banner__info">
                    <strong>{{ $banner->titulo ?? texto('admin_banners', 'lista.sem_titulo', 'Banner sem título') }}</strong>
                    <small>
                        @if($banner->agendado())
                            {{ texto('admin_banners', 'lista.de', 'De') }} {{ $banner->inicio_em?->format('d/m/Y H:i') ?? '—' }}
                            {{ texto('admin_banners', 'lista.ate', 'até') }} {{ $banner->fim_em?->format('d/m/Y H:i') ?? '—' }}
                        @else
                            {{ texto('admin_banners', 'lista.sempre', 'Sem agendamento — sempre no ar') }}
                        @endif
                    </small>
                    <span class="status-pilula {{ $banner->noAr() ? 'status-pilula--entregue' : '' }}">
                        {{ $banner->situacaoLegivel() }}
                    </span>
                </div>
                <div class="cartao-banner__acoes">
                    <a href="{{ route('admin.banners.edit', $banner) }}" class="mini-botao mini-botao--salvar">{{ texto('admin_banners', 'botao.editar', 'Editar') }}</a>
                    <button type="button" class="interruptor {{ $banner->ativo ? 'ligado' : '' }}" data-funcao="alternar-banner" data-url="{{ route('admin.banners.ativo', $banner) }}"
                            aria-label="{{ texto('admin_banners', 'botao.ligar_desligar', 'Ligar ou desligar') }}"></button>
                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" class="form-inline" data-confirmar="{{ texto('admin_banners', 'botao.confirmar_remover', 'Remover este banner?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="mini-botao mini-botao--perigo">{{ texto('admin_banners', 'botao.remover', 'Remover') }}</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="texto-suave">{{ texto('admin_banners', 'lista.vazia', 'Nenhum banner cadastrado — a vitrine usa o destaque padrão.') }}</p>
        @endforelse
    </section>

    <aside>
        <section class="painel-admin">
            <h2>{{ texto('admin_banners', 'novo.titulo', 'Novo banner') }}</h2>
            <p class="texto-suave">{{ texto('admin_banners', 'novo.dica', 'Imagem panorâmica funciona melhor (ex.: 1600×600).') }}</p>
            <a href="{{ route('admin.banners.create') }}" class="botao botao--chefe bloco">{{ texto('admin_banners', 'botao.criar', 'Criar banner') }}</a>
        </section>
    </aside>
</div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-funcao="alternar-banner"]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                fetch(botao.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).then(function (r) { return r.json(); }).then(function (r) {
                    botao.classList.toggle('ligado', r.ativo);
                    var alerta = document.createElement('div');
                    alerta.className = 'alerta alerta--sucesso';
                    alerta.textContent = r.mensagem;
                    document.querySelector('.principal').prepend(alerta);
                    setTimeout(function () { alerta.remove(); }, 3000);
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

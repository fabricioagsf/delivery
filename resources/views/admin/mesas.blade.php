@extends('layouts.admin')

@section('titulo', texto('admin_mesas', 'titulo', 'Mesas — Gostosuras'))
@section('titulo_pagina', texto('admin_mesas', 'titulo.pagina', 'Mesas da loja'))

@section('conteudo')
@if(session('sucesso_mesas'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_mesas') }}</div>
@endif

<div class="duas-colunas">
    <section class="painel-admin">
        <h2>{{ texto('admin_mesas', 'lista.titulo', 'Mesas cadastradas') }}</h2>
        <p class="nota-segura nota-segura--admin">{{ texto('admin_mesas', 'nota.explicacao', 'Cada mesa tem um QR code que abre o cardápio já vinculado a ela. Os pedidos feitos por esse cardápio caem direto na fila da cozinha com o número da mesa.') }}</p>

        @forelse($mesas as $mesa)
            <article class="cartao-cupom {{ ! $mesa->ativo ? 'cartao-cupom--desligado' : '' }}">
                <div class="cartao-cupom__info">
                    <strong>
                        {{ $mesa->nome ?: ($mesa->codigo ?: ('Mesa #'.$mesa->id)) }}
                    </strong>
                    <small>
                        @if($mesa->codigo)
                            {{ texto('admin_mesas', 'lista.codigo', 'código') }}: <code>{{ $mesa->codigo }}</code> ·
                        @endif
                        {{ $mesa->capacidade }} {{ $mesa->capacidade === 1 ? texto('admin_mesas', 'lista.pessoa_singular', 'pessoa') : texto('admin_mesas', 'lista.pessoa_plural', 'pessoas') }}
                    </small>
                    <span class="status-pilula {{ $mesa->ativo ? 'status-pilula--entregue' : '' }}">
                        {{ $mesa->ativo ? texto('admin_mesas', 'status.ativa', 'Ativa') : texto('admin_mesas', 'status.inativa', 'Inativa') }}
                    </span>
                </div>
                <div class="cartao-cupom__acoes">
                    <a href="{{ route('admin.configuracoes.index', ['mesa_id' => $mesa->id]) }}" class="mini-botao" target="_blank" rel="noopener">
                        {{ texto('admin_mesas', 'botao.qr', 'Ver QR') }}
                    </a>
                    <a href="{{ route('admin.mesas.edit', $mesa) }}" class="mini-botao">
                        {{ texto('admin_mesas', 'botao.editar', 'Editar') }}
                    </a>
                    <button type="button" class="interruptor {{ $mesa->ativo ? 'ligado' : '' }}" data-funcao="alternar-mesa" data-url="{{ route('admin.mesas.status', $mesa) }}"
                            aria-label="{{ texto('admin_mesas', 'botao.ligar_desligar', 'Ativar ou desativar mesa') }}"></button>
                </div>
            </article>
        @empty
            <p class="texto-suave">{{ texto('admin_mesas', 'lista.vazia', 'Nenhuma mesa cadastrada ainda. Crie a primeira para gerar o QR code e começar a receber pedidos por mesa.') }}</p>
        @endforelse
    </section>

    <aside>
        <section class="painel-admin">
            <h2>{{ texto('admin_mesas', 'novo.titulo', 'Nova mesa') }}</h2>
            <p class="texto-suave">{{ texto('admin_mesas', 'novo.dica', 'Dê um nome (ex.: Mesa 1, Varanda, Balcão 2) e a capacidade. O QR code fica disponível na tela de Configurações, já apontando para o cardápio desta mesa.') }}</p>
            <a href="{{ route('admin.mesas.create') }}" class="botao botao--chefe bloco">{{ texto('admin_mesas', 'botao.criar', 'Criar mesa') }}</a>
        </section>
    </aside>
</div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-funcao="alternar-mesa"]').forEach(function (botao) {
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
                    setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                });
            });
        });
    </script>
@endpush

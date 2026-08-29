@extends('layouts.admin')

@section('titulo', texto('admin_cupons', 'titulo', 'Cupons — Gostosuras'))
@section('titulo_pagina', texto('admin_cupons', 'titulo.pagina', 'Cupons promocionais'))

@section('conteudo')
@if(session('sucesso_cupons'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_cupons') }}</div>
@endif

<div class="duas-colunas">
    <section class="painel-admin">
        <h2>{{ texto('admin_cupons', 'lista.titulo', 'Cupons cadastrados') }}</h2>
        <p class="nota-segura nota-segura--admin">{{ texto('admin_cupons', 'nota.regras', 'Um cupom só é aceito se estiver ativo, dentro da validade, com usos disponíveis e atingindo o pedido mínimo. O cupom destacado vira a promoção mostrada na vitrine e no cardápio.') }}</p>

        @forelse($cupons as $cupom)
            <article class="cartao-cupom {{ ! $cupom->ativo ? 'cartao-cupom--desligado' : '' }}">
                <div class="cartao-cupom__info">
                    <strong>{{ $cupom->codigo }}</strong>
                    <small>
                        @if($cupom->tipo === 'percentual')
                            {{ $cupom->valor }}%
                        @else
                            {{ preco_br($cupom->valor) }}
                        @endif
                        @if($cupom->valor_minimo)
                            · {{ str_replace(':valor', preco_br($cupom->valor_minimo), texto('admin_cupons', 'lista.minimo', 'mín. :valor')) }}
                        @endif
                        · {{ texto('admin_cupons', 'lista.usos', 'usos') }}: {{ $cupom->usos }}{{ $cupom->limite_usos ? '/'.$cupom->limite_usos : '' }}
                        @if($cupom->fim_em)
                            · {{ texto('admin_cupons', 'lista.ate_data', 'até') }} {{ $cupom->fim_em->format('d/m/Y') }}
                        @else
                            · {{ texto('admin_cupons', 'lista.sem_validade', 'sem validade') }}
                        @endif
                    </small>
                    <span class="status-pilula {{ $cupom->vigente() && $cupom->ativo ? 'status-pilula--entregue' : '' }}">
                        @if(! $cupom->ativo)
                            {{ texto('admin_cupons', 'status.inativo', 'Desativado') }}
                        @elseif(! $cupom->vigente())
                            {{ texto('admin_cupons', 'status.expirado', 'Fora da validade') }}
                        @elseif(! $cupom->temUsosDisponiveis())
                            {{ texto('admin_cupons', 'status.esgotado', 'Usos esgotados') }}
                        @else
                            {{ texto('admin_cupons', 'status.ativo', 'Disponível') }}
                        @endif
                    </span>
                </div>
                <div class="cartao-cupom__acoes">
                    @if($destaque === $cupom->codigo)
                        <form method="POST" action="{{ route('admin.cupons.parar_divulgacao') }}" class="form-inline">
                            @csrf
                            <button type="submit" class="mini-botao mini-botao--perigo">{{ texto('admin_cupons', 'botao.parar_divulgacao', 'Retirar da vitrine') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.cupons.divulgar', $cupom) }}" class="form-inline">
                            @csrf
                            <button type="submit" class="mini-botao mini-botao--salvar">{{ texto('admin_cupons', 'botao.divulgar', 'Destacar na vitrine') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.cupons.edit', $cupom) }}" class="mini-botao">{{ texto('admin_cupons', 'botao.editar', 'Editar') }}</a>
                    <button type="button" class="interruptor {{ $cupom->ativo ? 'ligado' : '' }}" data-funcao="alternar-cupom" data-url="{{ route('admin.cupons.ativo', $cupom) }}"
                            aria-label="{{ texto('admin_cupons', 'botao.ligar_desligar', 'Ligar ou desligar') }}"></button>
                    <form method="POST" action="{{ route('admin.cupons.destroy', $cupom) }}" class="form-inline" data-confirmar="{{ texto('admin_cupons', 'botao.confirmar_remover', 'Remover este cupom?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="mini-botao mini-botao--perigo">{{ texto('admin_cupons', 'botao.remover', 'Remover') }}</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="texto-suave">{{ texto('admin_cupons', 'lista.vazia', 'Nenhum cupom cadastrado ainda.') }}</p>
        @endforelse
    </section>

    <aside>
        <section class="painel-admin">
            <h2>{{ texto('admin_cupons', 'novo.titulo', 'Novo cupom') }}</h2>
            <p class="texto-suave">{{ texto('admin_cupons', 'novo.dica', 'Crie cupons de percentual ou valor fixo e escolha qual promovido na vitrine.') }}</p>
            <a href="{{ route('admin.cupons.create') }}" class="botao botao--chefe bloco">{{ texto('admin_cupons', 'botao.criar', 'Criar cupom') }}</a>
        </section>
    </aside>
</div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-funcao="alternar-cupom"]').forEach(function (botao) {
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

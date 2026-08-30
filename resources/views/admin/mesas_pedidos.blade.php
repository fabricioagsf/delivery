@extends('layouts.admin')

@section('titulo', texto('admin_mesas_controle', 'titulo', 'Pedidos das mesas — Gostosuras'))
@section('titulo_pagina', texto('admin_mesas_controle', 'titulo.pagina', 'Pedidos das mesas (tempo real)'))

@section('conteudo')
<div class="controle-mesas">
    <div class="controle-mesas__cabecalho">
        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_mesas_controle', 'nota.explicacao', 'A grade mostra cada mesa ativa com o(s) pedido(s) em aberto. Quando chega um pedido novo, o quadro pisca em vermelho e toca um alerta — abra o pedido para começar o preparo.') }}
        </p>

        <div class="controle-mesas__controles">
            <label class="controle-mesas__toggle">
                <input type="checkbox" id="controle-som" checked>
                <span>{{ texto('admin_mesas_controle', 'controle.som', 'Tocar som de alerta') }}</span>
            </label>
            <button type="button" class="mini-botao" id="controle-testar-som">
                {{ texto('admin_mesas_controle', 'controle.testar_som', 'Testar som') }}
            </button>
            <span class="controle-mesas__status" id="controle-status-conexao" data-estado="ok">
                <span class="controle-mesas__bolinha"></span>
                <span class="controle-mesas__status-texto">{{ texto('admin_mesas_controle', 'controle.conectado', 'Conectado') }}</span>
            </span>
        </div>
    </div>

    <div class="controle-mesas__grade" id="grade-mesas">
        @forelse($mesas as $mesa)
            <article class="cartao-mesa cartao-mesa--livre"
                     data-mesa-id="{{ $mesa->id }}"
                     data-estado="livre" tabindex="0" role="button"
                     aria-label="{{ texto('admin_mesas_controle', 'cartao.abrir', 'Abrir mesa') }}"
                     title="{{ texto('admin_mesas_controle', 'cartao.abrir', 'Abrir mesa') }}">
                <header class="cartao-mesa__cabecalho">
                    <h3 class="cartao-mesa__titulo">{{ $mesa->nome ?: ($mesa->codigo ?: __('Mesa #').$mesa->id) }}</h3>
                    <span class="cartao-mesa__capacidade">{{ $mesa->capacidade }} {{ $mesa->capacidade === 1 ? texto('admin_mesas_controle','pessoa.singular','pessoa') : texto('admin_mesas_controle','pessoa.plural','pessoas') }}</span>
                </header>
                <div class="cartao-mesa__corpo">
                    <span class="cartao-mesa__estado-texto">{{ texto('admin_mesas_controle', 'estado.livre', 'Livre') }}</span>
                </div>
                <footer class="cartao-mesa__acoes">
                    <a class="mini-botao mini-botao--primario"
                       href="{{ route('admin.mesa.pedido', ['mesa' => $mesa]) }}"
                       data-fazer-pedido>{{ texto('admin_mesas_controle', 'cartao.fazer_pedido', 'Fazer pedido') }}</a>
                </footer>
            </article>
        @empty
            <p class="vazio">{{ texto('admin_mesas_controle', 'vazio', 'Nenhuma mesa ativa cadastrada. Vá em Mesas e ative ao menos uma para começar.') }}</p>
        @endforelse
    </div>
</div>

<div class="popup-pedido" id="popup-pedido" aria-live="polite" aria-atomic="true" hidden>
    <div class="popup-pedido__caixa">
        <strong class="popup-pedido__titulo">{{ texto('admin_mesas_controle', 'popup.titulo', 'Novo pedido!') }}</strong>
        <p class="popup-pedido__linha"><span class="popup-pedido__rotulo">{{ texto('admin_mesas_controle', 'popup.mesa', 'Mesa') }}</span><span class="popup-pedido__valor" id="popup-mesa">—</span></p>
        <p class="popup-pedido__linha"><span class="popup-pedido__rotulo">{{ texto('admin_mesas_controle', 'popup.codigo', 'Código') }}</span><span class="popup-pedido__valor" id="popup-codigo">—</span></p>
        <p class="popup-pedido__linha"><span class="popup-pedido__rotulo">{{ texto('admin_mesas_controle', 'popup.cliente', 'Cliente') }}</span><span class="popup-pedido__valor" id="popup-cliente">—</span></p>
        <p class="popup-pedido__linha"><span class="popup-pedido__rotulo">{{ texto('admin_mesas_controle', 'popup.total', 'Total') }}</span><span class="popup-pedido__valor" id="popup-total">—</span></p>
        <div class="popup-pedido__acoes">
            <a href="#" class="botao botao--chefe" id="popup-abrir">{{ texto('admin_mesas_controle', 'popup.abrir', 'Abrir pedido') }}</a>
            <button type="button" class="botao" id="popup-fechar">{{ texto('admin_mesas_controle', 'popup.fechar', 'Fechar') }}</button>
        </div>
    </div>
</div>

<div class="modal-mesa" id="modal-mesa" hidden>
    <div class="modal-mesa__velo" data-fechar-modal-mesa></div>
    <div class="modal-mesa__janela" role="dialog" aria-modal="true" aria-labelledby="modal-mesa-titulo">
        <header class="modal-mesa__cabecalho">
            <div class="modal-mesa__titulos">
                <h3 id="modal-mesa-titulo"></h3>
                <span class="modal-mesa__subtitulo" id="modal-mesa-subtitulo"></span>
            </div>
            <button type="button" class="modal-mesa__fechar" data-fechar-modal-mesa aria-label="{{ texto('admin_mesas_controle', 'modal.fechar', 'Fechar') }}">×</button>
        </header>
        <div class="modal-mesa__corpo" id="modal-mesa-corpo"></div>
        <footer class="modal-mesa__rodape">
            <button type="button" class="botao" data-fechar-modal-mesa>{{ texto('admin_mesas_controle', 'modal.fechar', 'Fechar') }}</button>
        </footer>
    </div>
</div>

@endsection

@push('scripts')
    <script>
        window.Rotas = window.Rotas || {};
        window.Rotas.mesasControleEstado = '{{ route('admin.mesas-controle.estado') }}';
        window.Rotas.mesasControleDetalhe = '{{ route('admin.mesas-controle.detalhe', ['mesa' => '__ID__']) }}';
        window.Textos = window.Textos || {};
        window.Textos.mesaControle = {
            livre: @json(texto('admin_mesas_controle', 'estado.livre', 'Livre')),
            novo: @json(texto('admin_mesas_controle', 'estado.novo', 'Aguardando preparo')),
            em_preparo: @json(texto('admin_mesas_controle', 'estado.em_preparo', 'Em preparo')),
            em_entrega: @json(texto('admin_mesas_controle', 'estado.em_entrega', 'Sendo entregue')),
            modal_carregando: @json(texto('admin_mesas_controle', 'modal.carregando', 'Carregando pedidos da mesa...')),
            modal_vazio: @json(texto('admin_mesas_controle', 'modal.vazio', 'Esta mesa está livre — nenhum pedido em aberto.')),
            modal_pedidos_abertos: @json(texto('admin_mesas_controle', 'modal.pedidos_abertos', 'pedidos em aberto')),
            modal_observacoes: @json(texto('admin_mesas_controle', 'modal.observacoes', 'Observações')),
            modal_total_mesa: @json(texto('admin_mesas_controle', 'modal.total_mesa', 'Total da mesa')),
            modal_abrir_pedido: @json(texto('admin_mesas_controle', 'modal.abrir_pedido', 'Abrir pedido')),
            modal_pagamento: @json(texto('admin_mesas_controle', 'modal.pagamento', 'Pagamento')),
            modal_horario: @json(texto('admin_mesas_controle', 'modal.horario', 'Hora')),
            pessoa.singular: @json(texto('admin_mesas_controle', 'pessoa.singular', 'pessoa')),
            pessoa_plural: @json(texto('admin_mesas_controle', 'pessoa.plural', 'pessoas')),
        };
    </script>
@endpush

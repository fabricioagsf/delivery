@extends('layouts.admin')

@section('titulo', texto('admin_caixa', 'titulo', 'Caixa — Gostosuras'))
@section('titulo_pagina', texto('admin_caixa', 'titulo.pagina', 'Caixa (contas das mesas)'))

@section('conteudo')
<div class="controle-mesas">
    <div class="controle-mesas__cabecalho">
        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_caixa', 'nota.explicacao', 'O Caixa é exclusivo para os pedidos das mesas: abra a mesa para ver os pedidos em aberto e o total da conta. Ao fechar a conta, registre a forma de pagamento e a mesa fica livre.') }}
        </p>
    </div>

    <div class="controle-mesas__grade" id="grade-caixa">
        @forelse($mesas as $mesa)
            <article class="cartao-mesa cartao-mesa--livre"
                     data-mesa-id="{{ $mesa->id }}"
                     data-estado="livre" tabindex="0" role="button"
                     aria-label="{{ texto('admin_caixa', 'cartao.abrir', 'Abrir conta da mesa') }}"
                     title="{{ texto('admin_caixa', 'cartao.abrir', 'Abrir conta da mesa') }}">
                <header class="cartao-mesa__cabecalho">
                    <h3 class="cartao-mesa__titulo">{{ $mesa->nome ?: ($mesa->codigo ?: __('Mesa #').$mesa->id) }}</h3>
                    <span class="cartao-mesa__capacidade">{{ $mesa->capacidade }} {{ $mesa->capacidade === 1 ? texto('admin_caixa','pessoa.singular','pessoa') : texto('admin_caixa','pessoa.plural','pessoas') }}</span>
                </header>
                <div class="cartao-mesa__corpo">
                    <span class="cartao-mesa__estado-texto">{{ texto('admin_caixa', 'estado.livre', 'Livre') }}</span>
                </div>
                <footer class="cartao-mesa__acoes">
                    <a class="mini-botao mini-botao--primario"
                       href="{{ route('admin.mesa.pedido', ['mesa' => $mesa]) }}"
                       data-fazer-pedido>{{ texto('admin_caixa', 'cartao.fazer_pedido', 'Fazer pedido') }}</a>
                </footer>
            </article>
        @empty
            <p class="vazio">{{ texto('admin_caixa', 'vazio', 'Nenhuma mesa ativa cadastrada. Vá em Mesas e ative ao menos uma para começar.') }}</p>
        @endforelse
    </div>
</div>

<div class="modal-mesa modal-mesa--caixa" id="modal-caixa" hidden>
    <div class="modal-mesa__velo" data-fechar-modal-caixa></div>
    <div class="modal-mesa__janela" role="dialog" aria-modal="true" aria-labelledby="modal-caixa-titulo">
        <header class="modal-mesa__cabecalho">
            <div class="modal-mesa__titulos">
                <h3 id="modal-caixa-titulo"></h3>
                <span class="modal-mesa__subtitulo" id="modal-caixa-subtitulo"></span>
            </div>
            <button type="button" class="modal-mesa__fechar" data-fechar-modal-caixa aria-label="{{ texto('admin_caixa', 'modal.fechar', 'Fechar') }}">×</button>
        </header>
        <div class="modal-mesa__corpo" id="modal-caixa-corpo"></div>
        <footer class="modal-mesa__rodape">
            <button type="button" class="botao" data-fechar-modal-caixa>{{ texto('admin_caixa', 'modal.fechar', 'Fechar') }}</button>
        </footer>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        window.Rotas = window.Rotas || {};
        window.Rotas.caixaEstado = '{{ route('admin.caixa.estado') }}';
        window.Rotas.caixaConta = '{{ route('admin.caixa.conta', ['mesa' => '__ID__']) }}';
        window.Rotas.caixaFechar = '{{ route('admin.caixa.fechar', ['mesa' => '__ID__']) }}';
        window.Rotas.caixaPixEfi = '{{ route('admin.caixa.pixEfi', ['mesa' => '__ID__']) }}';
        window.Textos = window.Textos || {};
        window.Textos.caixa = {
            livre: @json(texto('admin_caixa', 'estado.livre', 'Livre')),
            com_conta: @json(texto('admin_caixa', 'estado.com_conta', 'Conta aberta')),
            modal_carregando: @json(texto('admin_caixa', 'modal.carregando', 'Carregando a conta da mesa...')),
            modal_vazio: @json(texto('admin_caixa', 'modal.vazio', 'Esta mesa está livre — nenhum pedido em aberto.')),
            modal_pedidos_abertos: @json(texto('admin_caixa', 'modal.pedidos_abertos', 'pedido(s) em aberto')),
            modal_observacoes: @json(texto('admin_caixa', 'modal.observacoes', 'Observações')),
            modal_total_mesa: @json(texto('admin_caixa', 'modal.total_mesa', 'Total da conta')),
            modal_status: @json(texto('admin_caixa', 'modal.status', 'Status')),
            status_novo: @json(texto('admin_mesas_controle', 'estado.novo', 'Aguardando preparo')),
            status_em_preparo: @json(texto('admin_mesas_controle', 'estado.em_preparo', 'Em preparo')),
            status_em_entrega: @json(texto('admin_mesas_controle', 'estado.em_entrega', 'Sendo entregue')),
            status_entregue: @json(texto('admin_mesas_controle', 'estado.entregue', 'Entregue')),
            modal_horario: @json(texto('admin_caixa', 'modal.horario', 'Hora')),
            campo_forma_pagamento: @json(texto('admin_caixa', 'campo.forma_pagamento', 'Forma de pagamento')),
            campo_troco_para: @json(texto('admin_caixa', 'campo.troco_para', 'Valor recebido (R$)')),
            campo_troco: @json(texto('admin_caixa', 'campo.troco', 'Troco')),
            botao_fechar_conta: @json(texto('admin_caixa', 'botao.fechar_conta', 'Fechar conta e registrar pagamento')),
            confirmar_fechar: @json(texto('admin_caixa', 'confirmar.fechar_conta', 'Confirmar o fechamento da conta desta mesa?')),
            erro_troco_obrigatorio: @json(texto('admin_caixa', 'erro.troco_obrigatorio', 'Informe o valor recebido em dinheiro.')),
            erro_troco_menor: @json(texto('admin_caixa', 'erro.troco_menor', 'O valor recebido é menor que o total da conta.')),
            pix_opcao_chave: @json(texto('admin_caixa', 'pix.opcao_chave', 'QR code por chave registrada')),
            pix_opcao_efi: @json(texto('admin_caixa', 'pix.opcao_efi', 'QR code Pix automático (Efí)')),
            pix_sem_taxa: @json(texto('admin_caixa', 'pix.sem_taxa', 'Sem taxa de operadora')),
            pix_taxa: @json(texto('admin_caixa', 'pix.taxa', 'Taxa da operadora (Efí): :taxa')),
            pix_qr_alt: @json(texto('admin_caixa', 'pix.qr_alt', 'QR code Pix da conta da mesa')),
            pix_copia_e_cola: @json(texto('admin_caixa', 'pix.copia_e_cola', 'Pix copia e cola')),
            pix_copiar: @json(texto('admin_caixa', 'pix.copiar', 'Copiar código Pix')),
            pix_valor: @json(texto('admin_caixa', 'pix.valor', 'Valor: :valor')),
            pix_sem_chave: @json(texto('admin_caixa', 'pix.sem_chave', 'QR indisponível — cadastre a Chave Pix da loja em Configurações (Loja) e a cidade da empresa (Nota fiscal · Cidade).')),
            pix_sem_efi: @json(texto('admin_caixa', 'pix.sem_efi', 'Pix automático (Efí) não ativado — ative a Efí nas Configurações e informe a chave de recebimento.')),
            pix_carregando: @json(texto('admin_caixa', 'pix.carregando', 'Gerando QR da operadora...')),
            pix_erro_gerar: @json(texto('admin_caixa', 'pix.erro_gerar', 'Não foi possível gerar o QR da operadora — tente de novo.')),
            pessoa_plural: @json(texto('admin_caixa', 'pessoa.plural', 'pessoas')),
            formas: {
                dinheiro: @json(texto('pagamentos', 'forma.dinheiro', 'Dinheiro')),
                pix: @json(texto('pagamentos', 'forma.pix', 'Pix')),
                cartao: @json(texto('pagamentos', 'forma.cartao', 'Cartão')),
            },
        };
    </script>
@endpush
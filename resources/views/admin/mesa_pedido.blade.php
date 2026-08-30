@extends('layouts.admin')

@section('titulo', texto('admin_mesa_pedido', 'titulo', 'Criar pedido da mesa — Gostosuras'))
@section('titulo_pagina', texto('admin_mesa_pedido', 'titulo.pagina', 'Criar pedido da mesa'))

@section('conteudo')
<div class="mesa-pedido">
    <header class="mesa-pedido__cabecalho">
        <div class="mesa-pedido__titulos">
            <h2>{{ $mesa->nome ?: ($mesa->codigo ?: __('Mesa #').$mesa->id) }}</h2>
            <span class="mesa-pedido__capacidade">
                {{ $mesa->capacidade }} {{ $mesa->capacidade === 1
                    ? texto('admin_mesa_pedido', 'pessoa.singular', 'pessoa')
                    : texto('admin_mesa_pedido', 'pessoa.plural', 'pessoas') }}
                · {{ $abertos->isNotEmpty() ? texto('admin_mesa_pedido', 'estado.com_conta', 'Conta aberta') : texto('admin_mesa_pedido', 'estado.livre', 'Livre') }}
            </span>
            @if($abertos->isNotEmpty())
                <span class="mesa-pedido__total-aberto">
                    {{ str_replace(':total', preco_br($totalAberto), texto('admin_mesa_pedido', 'conta.aberta', 'Conta em aberto: :total')) }}
                    ({{ $abertos->count() }} {{ $abertos->count() === 1
                        ? texto('admin_mesa_pedido', 'pedido.singular', 'pedido')
                        : texto('admin_mesa_pedido', 'pedido.plural', 'pedidos') }})
                </span>
            @endif
        </div>
        <div class="mesa-pedido__acoes">
            <a class="botao" href="{{ route('admin.mesas-controle.index') }}">{{ texto('admin_mesa_pedido', 'voltar', 'Voltar') }}</a>
            <a class="botao botao--chefe" href="{{ route('admin.caixa.index') }}">{{ texto('admin_mesa_pedido', 'abrir_caixa', 'Abrir caixa desta mesa') }}</a>
        </div>
    </header>

    @if($abertos->isNotEmpty())
        <section class="mesa-pedido__abertos" id="mesa-pedido-abertos" aria-label="{{ texto('admin_mesa_pedido', 'abertos.rotulo', 'Pedidos em aberto desta mesa') }}">
            <header class="mesa-pedido__abertos-cabecalho">
                <h3>{{ texto('admin_mesa_pedido', 'abertos.titulo', 'Pedidos em aberto desta mesa') }}</h3>
                <span class="mesa-pedido__abertos-conta">{{ $abertos->count() }}</span>
            </header>
            <ul class="mesa-pedido__abertos-lista">
                @foreach($abertos as $pedido)
                    <li class="mesa-pedido__aberto" data-pedido-id="{{ $pedido->id }}">
                        <span class="mesa-pedido__aberto-codigo">#{{ $pedido->codigo }}</span>
                        <span class="mesa-pedido__aberto-status mesa-pedido__aberto-status--{{ str_replace('_', '-', $pedido->status) }}">
                            {{ texto('admin_mesas_controle', 'estado.'.$pedido->status, $pedido->status) }}
                        </span>
                        @if($pedido->entregue_mesa_em)
                            <span class="mesa-pedido__aberto-entregue">
                                {{ str_replace(':hora', $pedido->entregue_mesa_em->format('H:i'), texto('admin_mesa_pedido', 'abertos.entregue_as', 'Entregue às :hora')) }}
                            </span>
                        @elseif(in_array($pedido->status, ['novo', 'em_preparo', 'em_entrega'], true))
                            <button type="button" class="botao botao--pequeno mesa-pedido__aberto-entregar" data-entregar-pedido>
                                {{ texto('admin_mesa_pedido', 'abertos.entregar', 'Entregue na mesa') }}
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="mesa-pedido__layout">
        <section class="mesa-pedido__cardapio">
            <p class="nota-segura nota-segura--admin">
                {{ texto('admin_mesa_pedido', 'nota.explicacao', 'Monte o pedido do cliente usando o mesmo cardápio da mesa. Ao enviar, o pedido vai para a tela de Pedidos das mesas e o valor entra na conta da mesa.') }}
            </p>

            @if($categorias->isEmpty())
                <p class="vazio">{{ texto('admin_mesa_pedido', 'vazio', 'O cardápio está vazio — cadastre produtos ativos antes de atender a mesa.') }}</p>
            @else
                @foreach($categorias as $categoria)
                    <section class="mesa-pedido__categoria">
                        <header class="mesa-pedido__categoria-cabecalho">
                            <h3>{{ $categoria->nome }}</h3>
                            <span class="mesa-pedido__categoria-contagem">
                                {{ $categoria->produtos->count() }} {{ $categoria->produtos->count() === 1
                                    ? texto('admin_mesa_pedido', 'item.singular', 'item')
                                    : texto('admin_mesa_pedido', 'item.plural', 'itens') }}
                            </span>
                        </header>
                        <div class="mesa-pedido__grade">
                            @foreach($categoria->produtos as $produto)
                                @include('admin.partials.mesa_produto_card', ['produto' => $produto])
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @endif
        </section>

        <aside class="mesa-pedido__pedido" id="mesa-pedido-cart" aria-label="{{ texto('admin_mesa_pedido', 'cart.rotulo', 'Pedido da mesa') }}">
            <header class="mesa-pedido__pedido-cabecalho">
                <h3>{{ texto('admin_mesa_pedido', 'cart.titulo', 'Pedido da mesa') }}</h3>
                <span class="mesa-pedido__pedido-contador" id="mesa-pedido-contador">0</span>
            </header>

            <p class="mesa-pedido__pedido-vazio" id="mesa-pedido-vazio">
                {{ texto('admin_mesa_pedido', 'cart.vazio', 'Nenhum item ainda — escolha pelo cardápio, igualzinho ao cliente.') }}
            </p>
            <div class="mesa-pedido__pedido-corpo" id="mesa-pedido-linhas"></div>

            <div class="mesa-pedido__pedido-form" id="mesa-pedido-form">
                <label class="mesa-pedido__campo">
                    <span>{{ texto('admin_mesa_pedido', 'campo.cliente', 'Nome do cliente (opcional)') }}</span>
                    <input type="text" name="nome_cliente" maxlength="120" placeholder="{{ texto('admin_mesa_pedido', 'campo.cliente_ph', 'Ex.: Maria do 60') }}">
                </label>

                <label class="mesa-pedido__campo">
                    <span>{{ texto('admin_mesa_pedido', 'campo.observacoes', 'Observações (opcional)') }}</span>
                    <textarea name="observacoes" maxlength="500" rows="2" placeholder="{{ texto('admin_mesa_pedido', 'campo.observacoes_ph', 'Ex.: sem castanhas') }}"></textarea>
                </label>

                @if($employees->isNotEmpty())
                    <fieldset class="mesa-pedido__funcionarios">
                        <legend>{{ texto('admin_mesa_pedido', 'campo.funcionarios', 'Funcionário que está fazendo este pedido') }}</legend>
                        <p class="nota-segura nota-segura--admin">
                            Marque o seu nome. (Outros funcionários podem abrir pedidos separados na mesma mesa — cada pedido gera a sua comissão.)
                        </p>
                        @foreach($employees as $emp)
                            <label class="mesa-pedido__funcionario">
                                <input type="radio" name="employees[]" value="{{ $emp->id }}" required>
                                <span>{{ $emp->name }}</span>
                                <small>{{ $emp->roles->pluck('nome')->join(', ') ?: '—' }}</small>
                            </label>
                        @endforeach
                    </fieldset>
                @endif

                <p class="mesa-pedido__pedido-total">
                    <span>{{ texto('admin_mesa_pedido', 'cart.total', 'Total do pedido') }}</span>
                    <strong id="mesa-pedido-total">R$ 0,00</strong>
                </p>

                <button type="button" class="botao botao--chefe bloco" id="mesa-pedido-enviar" disabled>
                    {{ texto('admin_mesa_pedido', 'cart.enviar', 'Enviar pedido para a cozinha') }}
                </button>
            </div>
        </aside>
    </div>
</div>

@include('partials.modal-personalizar', ['confirmarTexto' => texto('admin_mesa_pedido', 'botao.adicionar_pedido', 'Adicionar ao pedido')])
@endsection

@push('scripts')
    @php
        $textosJs = [
            'cart_rotulo' => texto('admin_mesa_pedido', 'cart.rotulo', 'Pedido da mesa'),
            'cart_vazio' => texto('admin_mesa_pedido', 'cart.vazio', 'Nenhum item ainda — escolha pelo cardápio, igualzinho ao cliente.'),
            'cart_remover' => texto('admin_mesa_pedido', 'cart.remover', 'Remover'),
            'sucesso' => texto('admin_mesa_pedido', 'sucesso.enviado', 'Pedido enviado para a cozinha!'),
            'erro_geral' => texto('admin_mesa_pedido', 'erro.geral', 'Não foi possível enviar o pedido — tente de novo.'),
            'sem_itens' => texto('admin_mesa_pedido', 'val.sem_itens', 'Escolha ao menos um item.'),
            'modal_cada' => texto('vitrine', 'modal.cada', 'cada'),
            'modal_adicionais' => texto('vitrine', 'modal.adicionais', 'Adicionais'),
            'modal_remocoes' => texto('vitrine', 'modal.remocoes', 'Remoções'),
            'modal_vazio' => texto('vitrine', 'modal.vazio', 'Sem opções'),
            'modal_adicionar' => texto('admin_mesa_pedido', 'botao.adicionar_pedido', 'Adicionar ao pedido'),
            'abertos_rotulo' => texto('admin_mesa_pedido', 'abertos.rotulo', 'Pedidos em aberto desta mesa'),
            'abertos_entregue_as' => texto('admin_mesa_pedido', 'abertos.entregue_as', 'Entregue às :hora'),
            'abertos_entregando' => texto('admin_mesa_pedido', 'abertos.entregando', 'Marcando...'),
            'abertos_confirmar_entrega' => texto('admin_mesa_pedido', 'abertos.confirmar_entrega', 'Confirmar marcação de entregue na mesa?'),
            'sucesso_entregue' => texto('admin_mesa_pedido', 'sucesso.entregue', 'Pedido marcado como entregue na mesa!'),
        ];
    @endphp
    <script>
        window.Rotas = window.Rotas || {};
        window.Rotas.mesaPedidoConfirmar = '{{ route('admin.mesa.pedidoConfirmar', ['mesa' => $mesa]) }}';
        window.Textos = window.Textos || {};
        window.Textos.mesaPedido = @json($textosJs);
    </script>
@endpush
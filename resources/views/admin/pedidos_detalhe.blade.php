@extends('layouts.admin')

@section('titulo', str_replace(':codigo', $pedido->codigo, texto('admin_pedidos', 'titulo.detalhe', 'Pedido :codigo — Gostosuras')))
@section('titulo_pagina', str_replace(':codigo', $pedido->codigo, texto('admin_pedidos', 'titulo.detalhe', 'Pedido :codigo')))

@section('conteudo')
<div class="duas-colunas">
    <section class="painel-admin">
        <h2>{{ texto('admin_pedidos', 'detalhe.resumo_titulo', 'Resumo') }}</h2>
        <p>
            <span class="status-pilula status-pilula--{{ $pedido->status }}">{{ status_pedido($pedido->status) }}</span>
            · {{ $pedido->created_at?->format('d/m/Y H:i') }}
        </p>

        <table class="tabela">
            <thead>
            <tr>
                <th>{{ texto('admin_pedidos', 'detalhe.item', 'Item') }}</th>
                <th>{{ texto('admin_pedidos', 'detalhe.qtd', 'Qtd') }}</th>
                <th>{{ texto('admin_pedidos', 'detalhe.unitario', 'Unitário') }}</th>
                <th>{{ texto('admin_pedidos', 'detalhe.subtotal', 'Subtotal') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($pedido->itens as $item)
                <tr>
                    <td>{{ $item->nome_produto }}</td>
                    <td>{{ $item->quantidade }}</td>
                    <td>{{ preco_br($item->preco_unitario) }}</td>
                    <td>{{ preco_br($item->preco_unitario * $item->quantidade) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <p class="resumo-linha"><span>{{ texto('carrinho', 'resumo.subtotal', 'Subtotal') }}</span><span>{{ preco_br($pedido->subtotal) }}</span></p>
        <p class="resumo-linha"><span>{{ texto('checkout', 'resumo.taxa_entrega', 'Taxa de entrega') }}</span><span>{{ preco_br($pedido->taxa_entrega) }}</span></p>
        <p class="resumo-linha resumo-linha--total"><span>{{ texto('checkout', 'resumo.total', 'Total') }}</span><strong>{{ preco_br($pedido->total) }}</strong></p>

        @if($pedido->forma_pagamento === 'dinheiro' && $pedido->troco_para)
            <p class="destaque-info">{{ str_replace(':valor', preco_br($pedido->troco_para), texto('admin_pedidos', 'detalhe.troco', 'Levar troco para :valor')) }}</p>
        @endif
        @if(in_array($pedido->forma_pagamento, ['cartao_mp', 'pix_efi']))
            <p class="resumo-linha">
                <span>{{ texto('admin_pedidos', 'nota.pagamento', 'Pagamento online') }}</span>
                <span>
                    @if($pedido->pagamento_status === 'pago')
                        <span class="status-pilula status-pilula--entregue">{{ texto('admin_pedidos', 'nota.pagamento_pago', 'Pago') }}</span>
                    @else
                        <span class="status-pilula status-pilula--novo">{{ texto('admin_pedidos', 'nota.pagamento_pendente', 'Pendente') }}</span>
                    @endif
                </span>
            </p>
        @endif
        @if($pedido->observacoes)
            <p class="destaque-info">{{ texto('checkout', 'campo.observacoes', 'Observações') }}: {{ $pedido->observacoes }}</p>
        @endif

        <h2 class="margem-topo">{{ texto('admin_pedidos', 'nota.titulo', 'Nota fiscal') }}</h2>
        @if(session('sucesso_nota'))
            <div class="alerta alerta--sucesso">{{ session('sucesso_nota') }}</div>
        @endif
        @if(session('erro_nota'))
            <div class="alerta alerta--erro">{{ session('erro_nota') }}</div>
        @endif

        @forelse($pedido->notas as $nota)
            <p class="resumo-linha">
                <span>
                    {{ strtoupper($nota->modelo) }} · {{ texto('admin_pedidos', 'nota.status_' . $nota->status, ucfirst($nota->status)) }}
                    @if($nota->numero) · Nº {{ $nota->numero }}/{{ $nota->serie }} @endif
                </span>
                <span>{{ $nota->created_at?->format('d/m/Y H:i') }}</span>
            </p>
            @if($nota->mensagem)
                <p class="texto-suave">{{ $nota->mensagem }}</p>
            @endif
        @empty
            <p class="texto-suave">{{ texto('admin_pedidos', 'nota.sem_nota', 'Nenhuma nota gerada para este pedido.') }}</p>
        @endforelse

        <form method="POST" action="{{ route('admin.pedidos.nota', $pedido) }}" class="margem-topo">
            @csrf
            <button type="submit" class="botao">{{ texto('admin_pedidos', 'nota.botao_gerar', 'Gerar NF') }}</button>
        </form>
    </section>

    <aside>
        <section class="painel-admin">
            <h2>{{ texto('admin_pedidos', 'detalhe.status_titulo', 'Atualizar status') }}</h2>
            <select id="detalhe-status" class="seletor-status seletor-status--grande" data-id="{{ $pedido->id }}">
                @foreach(['novo', 'em_preparo', 'em_entrega', 'entregue', 'cancelado'] as $status)
                    <option value="{{ $status }}" {{ $pedido->status === $status ? 'selected' : '' }}>{{ status_pedido($status) }}</option>
                @endforeach
            </select>
            @if($pedido->status !== 'cancelado')
                <p class="nota-segura nota-segura--admin">{{ texto('admin_pedidos', 'detalhe.cancelar_aviso', 'Cancelar devolve os itens ao estoque automaticamente.') }}</p>
            @endif
        </section>

        <section class="painel-admin margem-topo">
            <h2>{{ texto('admin_pedidos', 'detalhe.cliente_titulo', 'Cliente') }}</h2>
            <p><strong>{{ $pedido->nome_cliente }}</strong><br>{{ $pedido->telefone }}@if($pedido->email)<br>{{ $pedido->email }}@endif</p>
            @if($pedido->cliente_id)
                <p class="texto-suave">{{ texto('admin_pedidos', 'detalhe.conta_vinculada', 'Pedido vinculado a uma conta de cliente.') }}</p>
            @endif

            <h2 class="margem-topo">{{ $pedido->tipo_entrega === 'entrega'
                    ? texto('confirmacao', 'entrega.titulo', 'Entrega')
                    : texto('confirmacao', 'retirada.titulo', 'Retirada') }}</h2>
            @if($pedido->tipo_entrega === 'entrega')
                <address class="texto-normal">
                    {{ $pedido->rua }}, {{ $pedido->numero }}@if($pedido->complemento) — {{ $pedido->complemento }}@endif<br>
                    {{ $pedido->bairro }} — {{ $pedido->cidade }}@if($pedido->cep)<br>{{ texto('checkout', 'campo.cep', 'CEP') }}: {{ $pedido->cep }}@endif
                </address>
            @else
                <p>{{ texto('confirmacao', 'retirada.texto', 'Retire na loja — combinaremos o horário pelo WhatsApp.') }}</p>
            @endif

            <h2 class="margem-topo">{{ texto('admin_pedidos', 'detalhe.chave_titulo', 'Chave de segurança') }}</h2>
            <p class="nota-segura nota-segura--admin">
                {{ texto('admin_pedidos', 'detalhe.chave_texto', 'Na entrega/retirada, peça ao cliente a chave de segurança cadastrada na conta dele e confirme antes de marcar como entregue.') }}
            </p>
        </section>
    </aside>
</div>
@endsection

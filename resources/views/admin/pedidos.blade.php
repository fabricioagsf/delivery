@extends('layouts.admin')

@section('titulo', texto('admin_pedidos', 'titulo', 'Pedidos — Gostosuras'))
@section('titulo_pagina', texto('admin_pedidos', 'titulo.pagina', 'Pedidos'))

@section('conteudo')
<form method="GET" class="filtros">
    <input type="text" name="q" value="{{ $busca }}" placeholder="{{ texto('admin_pedidos', 'busca.placeholder', 'Código, cliente ou telefone...') }}">
    <button type="submit" class="botao">{{ texto('admin_produtos', 'botao.buscar', 'Buscar') }}</button>
</form>

<div class="chips chips--status">
    <a href="{{ route('admin.pedidos.index') }}" class="chip {{ !$statusAtual ? 'chip--ativa' : '' }}">
        {{ texto('admin_pedidos', 'filtro.todos', 'Todos') }}
    </a>
    @foreach($statusLista as $status)
        <a href="{{ route('admin.pedidos.index', array_filter(['status' => $status, 'q' => $busca])) }}"
           class="chip {{ $statusAtual === $status ? 'chip--ativa' : '' }}">
            {{ status_pedido($status) }}
            @if($contagemStatus->has($status)) <b>{{ $contagemStatus->get($status) }}</b> @endif
        </a>
    @endforeach
</div>

<section class="painel-admin">
    <div class="tabela-rolagem">
        <table class="tabela">
        <thead>
        <tr>
            <th>{{ texto('admin_pedidos', 'tabela.codigo', 'Código') }}</th>
            <th>{{ texto('admin_pedidos', 'tabela.quando', 'Quando') }}</th>
            <th>{{ texto('admin_pedidos', 'tabela.cliente', 'Cliente') }}</th>
            <th>{{ texto('admin_pedidos', 'tabela.tipo', 'Tipo') }}</th>
            <th>{{ texto('admin_pedidos', 'tabela.pagamento', 'Pagamento') }}</th>
            <th>{{ texto('admin_pedidos', 'tabela.itens', 'Itens') }}</th>
            <th>{{ texto('admin_pedidos', 'tabela.total', 'Total') }}</th>
            <th>{{ texto('admin_pedidos', 'tabela.status', 'Status') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($pedidos as $pedido)
            <tr data-pedido="{{ $pedido->id }}">
                <td><a href="{{ route('admin.pedidos.show', $pedido) }}"><strong>{{ $pedido->codigo }}</strong></a></td>
                <td>{{ $pedido->created_at?->format('d/m H:i') }}</td>
                <td>{{ $pedido->nome_cliente }}<br><small>{{ $pedido->telefone }}</small></td>
                <td>{{ texto('conta', 'tipo.' . $pedido->tipo_entrega, ucfirst($pedido->tipo_entrega)) }}</td>
                <td>{{ forma_pagamento_label($pedido->forma_pagamento) }}</td>
                <td>{{ $pedido->itens_count }}</td>
                <td><strong>{{ preco_br($pedido->total) }}</strong></td>
                <td>
                    <select class="seletor-status" data-funcao="mudar-status">
                        @foreach($statusLista as $status)
                            <option value="{{ $status }}" {{ $pedido->status === $status ? 'selected' : '' }}>{{ status_pedido($status) }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="texto-suave">{{ texto('admin_pedidos', 'lista.vazia', 'Nenhum pedido encontrado.') }}</td></tr>
        @endforelse
        </tbody>
        </table>
    </div>

    {{ $pedidos->links('vendor.pagination.padrao') }}
</section>
@endsection

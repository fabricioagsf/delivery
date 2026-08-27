@extends('layouts.admin')

@section('titulo', texto('admin_item_venda', 'titulo', 'Produtos e serviços — Gostosuras'))
@section('titulo_pagina', texto('admin_item_venda', 'titulo.pagina', 'Módulo de produtos e serviços'))

@section('conteudo')
@if(session('sucesso_item_venda'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_item_venda') }}</div>
@endif

<form method="POST" action="{{ route('admin.item-venda.atualizar') }}" class="form-admin">
    @csrf

    <fieldset class="secao-form">
        <legend>{{ texto('admin_item_venda', 'secao.geral', 'Módulo') }}</legend>

        <label class="caixa-marcar">
            <input type="checkbox" name="item_venda_ativo" value="1" {{ $ativo ? 'checked' : '' }}>
            {{ texto('admin_item_venda', 'campo.ativo', 'Ativar cadastro de produtos e serviços') }}
        </label>

        <label>{{ texto('admin_item_venda', 'campo.tipo', 'O que o sistema vende') }}
            <select name="item_venda_tipo">
                <option value="produtos" {{ $tipo === 'produtos' ? 'selected' : '' }}>{{ texto('admin_item_venda', 'tipo.produtos', 'Apenas produtos') }}</option>
                <option value="servicos" {{ $tipo === 'servicos' ? 'selected' : '' }}>{{ texto('admin_item_venda', 'tipo.servicos', 'Apenas serviços') }}</option>
                <option value="ambos" {{ $tipo === 'ambos' ? 'selected' : '' }}>{{ texto('admin_item_venda', 'tipo.ambos', 'Produtos e serviços') }}</option>
            </select>
        </label>

        @if($permiteServicos && ! $permiteProdutos)
            <p class="nota-segura nota-segura--admin">{{ texto('admin_item_venda', 'nota.somente_servicos', 'Este delivery ainda não tem cadastro de serviços na vitrine/carrinho. Ao configurar "apenas serviços", o painel de produtos fica oculto até a venda de serviços ser integrada.') }}</p>
        @elseif($permiteProdutos && ! $permiteServicos)
            <p class="nota-segura nota-segura--admin">{{ texto('admin_item_venda', 'nota.somente_produtos', 'Venda configurada apenas para produtos — o padrão deste delivery.') }}</p>
        @else
            <p class="nota-segura nota-segura--admin">{{ texto('admin_item_venda', 'nota.ambos', 'Venda configurada para produtos e serviços. O cadastro de serviços ainda não está integrado à vitrine/carrinho deste delivery.') }}</p>
        @endif
    </fieldset>

    <fieldset class="secao-form">
        <legend>{{ texto('admin_item_venda', 'secao.resumo', 'Resumo atual') }}</legend>

        <div class="cartoes-resumo">
            <div class="cartao-metrica">
                <span class="numero-grande">{{ $totalProdutos }}</span>
                <span class="texto-suave">{{ texto('admin_item_venda', 'metrica.produtos', 'produtos cadastrados') }}</span>
            </div>
            <div class="cartao-metrica">
                <span class="numero-grande">{{ $totalAtivos }}</span>
                <span class="texto-suave">{{ texto('admin_item_venda', 'metrica.ativos', 'produtos ativos na vitrine') }}</span>
            </div>
            <div class="cartao-metrica">
                <span class="numero-grande">{{ $totalCategorias }}</span>
                <span class="texto-suave">{{ texto('admin_item_venda', 'metrica.categorias', 'categorias') }}</span>
            </div>
            <div class="cartao-metrica">
                <span class="numero-grande">{{ $totalPedidos }}</span>
                <span class="texto-suave">{{ texto('admin_item_venda', 'metrica.pedidos', 'pedidos com itens') }}</span>
            </div>
        </div>
    </fieldset>

    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">{{ texto('admin_item_venda', 'botao.salvar', 'Salvar módulo') }}</button>
    </div>
</form>
@endsection

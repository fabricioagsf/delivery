@extends('layouts.admin')

@section('titulo', texto('admin_produtos', 'titulo', 'Produtos e estoque — Gostosuras'))
@section('titulo_pagina', texto('admin_produtos', 'titulo.pagina', 'Produtos e estoque'))

@section('conteudo')
@if(session('sucesso_produtos'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_produtos') }}</div>
@endif

<form method="GET" class="filtros">
    <a href="{{ route('admin.produtos.create') }}" class="botao botao--chefe">{{ texto('admin_produtos', 'botao.novo', '+ Novo produto') }}</a>
    <input type="text" name="q" value="{{ $busca }}" placeholder="{{ texto('admin_produtos', 'busca.placeholder', 'Buscar por nome ou categoria...') }}">
    <button type="submit" class="botao">{{ texto('admin_produtos', 'botao.buscar', 'Buscar') }}</button>

    <a href="{{ route('admin.produtos.index') }}" class="chip {{ !$filtro ? 'chip--ativa' : '' }}">{{ texto('vitrine', 'filtro.todos', 'Tudo') }}</a>
    <a href="{{ route('admin.produtos.index', ['estoque' => 'critico']) }}" class="chip {{ $filtro === 'critico' ? 'chip--ativa' : '' }}">
        {{ str_replace(':qtd', $totalCriticos, texto('admin_produtos', 'filtro.critico', 'Estoque baixo (:qtd)')) }}
    </a>
    <a href="{{ route('admin.produtos.index', ['estoque' => 'esgotado']) }}" class="chip {{ $filtro === 'esgotado' ? 'chip--ativa' : '' }}">
        {{ texto('admin_produtos', 'filtro.esgotado', 'Esgotados') }}
    </a>
</form>

<section class="painel-admin">
    <p class="nota-segura nota-segura--admin">
        {{ texto('admin_produtos', 'nota.estoque_vazio', 'Regra da loja: o produto só é vendido com quantidade maior que zero. Sem quantidade definida, ele fica indisponível na vitrine.') }}
    </p>

    <table class="tabela tabela--estoque">
        <thead>
        <tr>
            <th>{{ texto('admin_produtos', 'tabela.produto', 'Produto') }}</th>
            <th>{{ texto('admin_produtos', 'tabela.categoria', 'Categoria') }}</th>
            <th>{{ texto('admin_produtos', 'tabela.preco', 'Preço') }}</th>
            <th>{{ texto('admin_produtos', 'tabela.estoque', 'Estoque') }}</th>
            <th>{{ texto('admin_produtos', 'tabela.minimo', 'Mínimo') }}</th>
            <th></th>
            <th>{{ texto('admin_produtos', 'tabela.vitrine', 'Vitrine') }}</th>
            <th>{{ texto('admin_produtos', 'tabela.destaque', 'Destaque') }}</th>
            <th>{{ texto('admin_produtos', 'tabela.acoes', 'Ações') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($produtos as $produto)
            <tr data-produto="{{ $produto->id }}" @if($produto->temEstoque(1) === false) class="linha-esgotada" @endif>
                <td><strong>{{ $produto->nome }}</strong></td>
                <td>{{ $produto->categoria?->nome }}</td>
                <td class="celula-preco">{{ preco_br($produto->preco) }}</td>
                <td>
                    <input type="number" min="0" max="100000"
                           value="{{ $produto->estoque }}"
                           placeholder="{{ texto('admin_produtos', 'tabela.sem_controle', '— sem controle —') }}"
                           class="entrada-estoque" data-campo="estoque">
                </td>
                <td>
                    <input type="number" min="0" max="100000"
                           value="{{ $produto->estoque_minimo }}"
                           class="entrada-estoque entrada-estoque--pequena" data-campo="estoque_minimo">
                </td>
                <td>
                    <button type="button" class="mini-botao mini-botao--salvar" data-funcao="salvar-estoque">
                        {{ texto('admin_produtos', 'botao.salvar_estoque', 'Salvar') }}
                    </button>
                    <span class="retorno-linha"></span>
                </td>
                <td>
                    <button type="button" class="interruptor {{ $produto->ativo ? 'ligado' : '' }}" data-funcao="alternar-ativo"
                            aria-label="{{ texto('admin_produtos', 'tabela.vitrine', 'Vitrine') }}"></button>
                </td>
                <td>
                    <button type="button" class="interruptor interruptor--destaque {{ $produto->destaque ? 'ligado' : '' }}" data-funcao="alternar-destaque"
                            aria-label="{{ texto('admin_produtos', 'tabela.destaque', 'Destaque') }}"></button>
                </td>
                <td class="celula-acoes">
                    <a href="{{ route('admin.produtos.edit', $produto) }}" class="mini-botao mini-botao--salvar">{{ texto('admin_produtos', 'botao.editar', 'Editar') }}</a>
                    <form method="POST" action="{{ route('admin.produtos.destroy', $produto) }}" class="form-inline" data-confirmar="{{ texto('admin_produtos', 'botao.confirmar_remover', 'Remover este produto? Ele sai da vitrine, mas pode ser restaurado pela Auditoria.') }}">
                        @csrf
                        <button type="submit" class="mini-botao mini-botao--perigo">{{ texto('admin_banners', 'botao.remover', 'Remover') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="texto-suave">{{ texto('admin_produtos', 'lista.vazia', 'Nenhum produto cadastrado ainda.') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $produtos->links('vendor.pagination.padrao') }}
</section>
@endsection

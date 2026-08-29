@extends('layouts.admin')

@section('titulo', texto('admin_lojas', 'titulo.form', 'Loja — Gostosuras'))
@section('titulo_pagina', $loja->exists ? texto('admin_lojas', 'form.titulo_editar', 'Editar loja') : texto('admin_lojas', 'form.titulo_novo', 'Nova loja'))

@section('conteudo')
<section class="painel-admin painel-admin--estreito">
    @if($errors->any())
        <div class="alerta alerta--erro">
            <ul>
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" class="form-admin"
          action="{{ $loja->exists ? route('admin.lojas.update', $loja) : route('admin.lojas.store') }}">
        @csrf

        <fieldset class="secao-form">
            <legend>{{ texto('admin_lojas', 'form.secao.dados', 'Dados da loja') }}</legend>

            <label>{{ texto('admin_lojas', 'campo.nome', 'Nome da loja') }}
                <input type="text" name="nome" value="{{ old('nome', $loja->nome) }}" maxlength="150" autocomplete="off">
            </label>

            <label>{{ texto('admin_lojas', 'campo.slug', 'Identificador (usado no QR e nos relatórios)') }}
                <input type="text" name="slug" value="{{ old('slug', $loja->slug) }}" maxlength="100" placeholder="ex.: matriz, loja-2" autocomplete="off">
            </label>

            <label>{{ texto('admin_lojas', 'campo.dominio', 'Domínio próprio (opcional — vazio = loja no domínio principal)') }}
                <input type="text" name="dominio" value="{{ old('dominio', $loja->dominio) }}" maxlength="190" placeholder="ex.: loja2.dominio.com.br" autocomplete="off">
            </label>

            <label>{{ texto('admin_lojas', 'campo.status', 'Situação') }}
                <select name="status">
                    <option value="ativo" @selected(old('status', $loja->status ?: 'ativo') === 'ativo')>{{ texto('admin_lojas', 'opcao.ativo', 'Ativa (aparece no seletor)') }}</option>
                    <option value="suspenso" @selected(old('status', $loja->status) === 'suspenso')>{{ texto('admin_lojas', 'opcao.suspenso', 'Suspensa (oculta)') }}</option>
                </select>
            </label>
        </fieldset>

        @if($loja->exists)
            <p class="nota-segura nota-segura--admin">{{ texto('admin_lojas', 'nota.acoes', 'Os produtos, pedidos, cupons e banners desta loja são independentes: cadastre o catálogo com a loja ativa no seletor do topo do painel.') }}</p>
        @endif

        <div class="rodape-form">
            <button type="submit" class="botao botao--chefe">{{ texto('admin_lojas', 'botao.salvar', 'Salvar loja') }}</button>
            <a href="{{ route('admin.lojas.index') }}" class="botao">{{ texto('admin_lojas', 'botao.cancelar', 'Cancelar') }}</a>
        </div>
    </form>
</section>
@endsection

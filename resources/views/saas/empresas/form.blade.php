@extends('layouts.saas')

@section('titulo', $empresa->exists ? 'Editar empresa' : 'Nova empresa')
@section('titulo_pagina', $empresa->exists ? 'Editar empresa' : 'Nova empresa')

@section('conteudo')
<form method="POST" action="{{ $empresa->exists ? route('saas.empresas.update', $empresa) : route('saas.empresas.store') }}" class="form-admin">
    @csrf
    @if($empresa->exists) @method('PUT') @endif
    <label>Nome
        <input type="text" name="nome" value="{{ old('nome', $empresa->nome) }}" required>
    </label>
    <label>Identificador (slug)
        <input type="text" name="slug" value="{{ old('slug', $empresa->slug) }}" required>
    </label>
    <label>CNPJ
        <input type="text" name="cnpj" value="{{ old('cnpj', $empresa->cnpj) }}">
    </label>
    <label class="caixa-marcar">
        <input type="checkbox" name="ativo" value="1" {{ old('ativo', $empresa->ativo ?? true) ? 'checked' : '' }}>
        Ativa
    </label>
    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">Salvar</button>
        <a href="{{ route('saas.empresas.index') }}" class="botao botao--fantasma">Cancelar</a>
    </div>
</form>
@endsection

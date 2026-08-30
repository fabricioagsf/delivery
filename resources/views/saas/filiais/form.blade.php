@extends('layouts.saas')

@section('titulo', $filial->exists ? 'Editar filial' : 'Nova filial')
@section('titulo_pagina', $filial->exists ? 'Editar filial' : 'Nova filial')

@section('conteudo')
<form method="POST" action="{{ $filial->exists ? route('saas.filiais.update', $filial) : route('saas.filiais.store') }}" class="form-admin">
    @csrf
    @if($filial->exists) @method('PUT') @endif
    <label>Empresa
        <select name="empresa_id" required>
            @foreach($empresas as $empresa)
                <option value="{{ $empresa->id }}" {{ old('empresa_id', $filial->empresa_id) == $empresa->id ? 'selected' : '' }}>{{ $empresa->nome }}</option>
            @endforeach
        </select>
    </label>
    <label>Nome
        <input type="text" name="nome" value="{{ old('nome', $filial->nome) }}" required>
    </label>
    <label>Identificador (slug)
        <input type="text" name="slug" value="{{ old('slug', $filial->slug) }}" required>
    </label>
    <label>Domínio (opcional)
        <input type="text" name="dominio" value="{{ old('dominio', $filial->dominio) }}">
    </label>
    <label class="caixa-marcar">
        <input type="checkbox" name="ativo" value="1" {{ old('ativo', $filial->ativo ?? true) ? 'checked' : '' }}>
        Ativa
    </label>
    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">Salvar</button>
        <a href="{{ route('saas.filiais.index') }}" class="botao botao--fantasma">Cancelar</a>
    </div>
</form>
@endsection

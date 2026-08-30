@extends('layouts.saas')

@section('titulo', 'Configurações — ' . $empresa->nome)
@section('titulo_pagina', 'Configurações da empresa')

@section('conteudo')
<form method="POST" action="{{ route('saas.configuracoes.salvar') }}" class="form-admin">
    @csrf

    <fieldset class="secao-form">
        <legend>Comissões por papel (%)</legend>
        <p class="nota-segura nota-segura--admin">Defina o percentual de comissão sobre o valor total do pedido para cada papel de funcionário.</p>
        @forelse($roles as $role)
            <label>{{ $role->nome }} — {{ $role->descricao }}
                <div class="input-com-moeda">
                    <input type="number" name="comissao[{{ $role->id }}]" value="{{ $comissoes[$role->id] ?? 0 }}" min="0" max="100" step="0.1" style="width:120px">
                    <span>%</span>
                </div>
            </label>
        @empty
            <p class="texto-suave">Cadastre papéis na tela de Employees antes.</p>
        @endforelse
    </fieldset>

    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">Salvar configurações</button>
    </div>
</form>

<style>
.input-com-moeda { display:flex; align-items:center; gap:4px; }
.input-com-moeda input { text-align:right; }
</style>
@endsection

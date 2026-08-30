@extends('layouts.saas')

@section('titulo', 'Configurações — ' . $empresa->nome)
@section('titulo_pagina', 'Configurações da empresa')

@section('conteudo')
<form method="POST" action="{{ route('saas.empresas.config.salvar', $empresa) }}" class="form-admin">
    @csrf

    <fieldset class="secao-form">
        <legend>Comissões por papel (%)</legend>
        <p class="nota-segura nota-segura--admin">Defina o percentual de comissão sobre o valor total do pedido para cada papel de funcionário. O cálculo é feito automaticamente quando o pedido é finalizado.</p>
        @forelse($roles as $role)
            <label>{{ $role->nome }} — <small>{{ $role->descricao }}</small>
                <div class="input-com-percentual">
                    <input type="number" name="comissao[{{ $role->id }}]" value="{{ $comissoes[$role->id] ?? 0 }}" min="0" max="100" step="0.1" style="width:120px">
                    <span>%</span>
                </div>
            </label>
        @empty
            <p class="texto-suave">Cadastre papéis no sistema antes.</p>
        @endforelse
    </fieldset>

    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">Salvar configurações</button>
        <a href="{{ route('saas.empresas.index') }}" class="botao botao--fantasma">Voltar</a>
    </div>
</form>

<style>
.input-com-percentual { display:flex; align-items:center; gap:4px; }
.input-com-percentual input { text-align:right; }
</style>
@endsection

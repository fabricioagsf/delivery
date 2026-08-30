@extends('layouts.saas')

@section('titulo', 'Configurações — ' . $empresa->nome)
@section('titulo_pagina', 'Configurações da empresa')

@section('conteudo')
<form method="POST" action="{{ route('saas.empresas.config.salvar', $empresa) }}" class="form-admin">
    @csrf

    <fieldset class="secao-form">
        <legend>Comissão dos funcionários (%)</legend>
        <p class="nota-segura nota-segura--admin">
            Percentual único aplicado sobre o valor de cada pedido feito pelo funcionário.
            <br>
            <strong>Ex.:</strong> 8% — se o funcionário vendeu R$1.000 no mês, ganha R$80 de comissão.
            Quando mais de um funcionário atende a mesma mesa, ambos recebem a mesma comissão sobre o total do pedido.
        </p>
        <label>Comissão padrão
            <div class="input-com-percentual">
                <input type="number" name="comissao_padrao" value="{{ $comissaoPadrao }}" min="0" max="100" step="0.1" style="width:120px" required>
                <span>%</span>
            </div>
        </label>
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

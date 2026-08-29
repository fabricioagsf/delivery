@extends('layouts.admin')

@section('titulo', texto('admin_fidelidade', 'titulo', 'Fidelidade — Gostosuras'))
@section('titulo_pagina', texto('admin_fidelidade', 'titulo.pagina', 'Programa de fidelidade (pontos)'))

@section('conteudo')
@if(session('sucesso_fidelidade'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_fidelidade') }}</div>
@endif

<form method="POST" action="{{ route('admin.fidelidade.atualizar') }}" class="form-admin">
    @csrf

    <fieldset class="secao-form">
        <legend>{{ texto('admin_fidelidade', 'secao.geral', 'Funcionamento') }}</legend>

        <label class="caixa-marcar">
            <input type="checkbox" name="fidelidade_ativo" value="1" {{ $ativo ? 'checked' : '' }}>
            {{ texto('admin_fidelidade', 'campo.ativo', 'Ativar programa de fidelidade (pontos)') }}
        </label>

        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_fidelidade', 'nota.explicacao', 'Com o módulo ativo, o cliente logado acumula pontos a cada pedido (R$ 1,00 de compra rende 1 ponto, configurável abaixo) e pode trocá-los por desconto no checkout.') }}
        </p>
    </fieldset>

    <fieldset class="secao-form">
        <legend>{{ texto('admin_fidelidade', 'secao.regras', 'Regras de ganho e resgate') }}</legend>

        <label>{{ texto('admin_fidelidade', 'campo.ganho', 'Pontos por R$ 1,00 de compra') }}
            <input type="number" name="ganho" class="entrada-texto" step="0.01" min="0.01" max="100" value="{{ $ganho }}">
        </label>

        <label>{{ texto('admin_fidelidade', 'campo.ponto_valor', 'Valor de cada ponto no resgate (R$)') }}
            <input type="number" name="ponto_valor" class="entrada-texto" step="0.01" min="0.01" max="100" value="{{ $pontoValor }}">
        </label>

        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_fidelidade', 'nota.regras', 'Ex.: com "valor do ponto" 0,10, cada 10 pontos valem R$ 1,00 de desconto no checkout.') }}
        </p>
    </fieldset>

    <fieldset class="secao-form">
        <legend>{{ texto('admin_fidelidade', 'secao.status', 'Status atual') }}</legend>

        <div class="cartoes-resumo">
            <div class="cartao-metrica">
                <span class="numero-grande">{{ $totalPontos }}</span>
                <span class="texto-suave">{{ texto('admin_fidelidade', 'metrica.pontos', 'pontos em circulação') }}</span>
            </div>
            <div class="cartao-metrica">
                <span class="numero-grande">{{ $totalClientes }}</span>
                <span class="texto-suave">{{ texto('admin_fidelidade', 'metrica.clientes', 'clientes com pontos') }}</span>
            </div>
        </div>
    </fieldset>

    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">{{ texto('admin_fidelidade', 'botao.salvar', 'Salvar fidelidade') }}</button>
    </div>
</form>
@endsection

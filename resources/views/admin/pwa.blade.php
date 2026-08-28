@extends('layouts.admin')

@section('titulo', texto('admin_pwa', 'titulo', 'PWA / App — Gostosuras'))
@section('titulo_pagina', texto('admin_pwa', 'titulo.pagina', 'Módulo PWA (app / cardápio offline)'))

@section('conteudo')
@if(session('sucesso_pwa'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_pwa') }}</div>
@endif

<form method="POST" action="{{ route('admin.pwa.atualizar') }}" class="form-admin">
    @csrf

    <fieldset class="secao-form">
        <legend>{{ texto('admin_pwa', 'secao.geral', 'App instalável') }}</legend>

        <label class="caixa-marcar">
            <input type="checkbox" name="pwa_ativo" value="1" {{ $ativo ? 'checked' : '' }}>
            {{ texto('admin_pwa', 'campo.ativo', 'Ativar PWA (cardápio consultável offline e instalável no celular)') }}
        </label>

        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_pwa', 'nota.explicacao', 'Com o PWA ativo, o cliente visita o cardápio uma vez e depois consegue consultá-lo mesmo sem internet, além de poder instalar o atalho na tela inicial do celular.') }}
        </p>
    </fieldset>

    <fieldset class="secao-form">
        <legend>{{ texto('admin_pwa', 'secao.status', 'Status atual') }}</legend>

        <div class="cartoes-resumo">
            <div class="cartao-metrica">
                <span class="numero-grande">{{ $totalImagens }}</span>
                <span class="texto-suave">{{ texto('admin_pwa', 'metrica.imagens', 'imagens guardadas para consulta offline') }}</span>
            </div>
            <div class="cartao-metrica">
                <span class="numero-grande">v{{ $versao }}</span>
                <span class="texto-suave">{{ texto('admin_pwa', 'metrica.cache', 'versão do cache do app') }}</span>
            </div>
        </div>

        <label class="caixa-marcar">
            <input type="checkbox" name="pwa_renovar_cache" value="1">
            {{ texto('admin_pwa', 'campo.renovar', 'Renovar agora o cache dos clientes (recarregar cardápio/preços na próxima abertura)') }}
        </label>
    </fieldset>

    <fieldset class="secao-form">
        <legend>{{ texto('admin_pwa', 'secao.links', 'Links do app') }}</legend>
        <ul class="lista-links-pwa">
            <li>
                <a href="{{ $cardapioUrl }}" target="_blank">
                    {{ texto('admin_pwa', 'link.cardapio', 'Ver o cardápio que fica disponível offline') }}
                </a>
            </li>
            <li>
                <code>{{ $serviceWorkerUrl }}</code>
                <span class="texto-suave">{{ texto('admin_pwa', 'link.sw_nome', 'service worker') }}</span>
            </li>
            <li>
                <code>{{ $manifestUrl }}</code>
                <span class="texto-suave">{{ texto('admin_pwa', 'link.manifest_nome', 'manifesto do app') }}</span>
            </li>
        </ul>
    </fieldset>

    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">{{ texto('admin_pwa', 'botao.salvar', 'Salvar app (PWA)') }}</button>
    </div>
</form>
@endsection

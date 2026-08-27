@extends('layouts.admin')

@section('titulo', texto('admin_config', 'titulo', 'Configurações — Gostosuras'))
@section('titulo_pagina', texto('admin_config', 'titulo.pagina', 'Configurações da loja'))

@section('conteudo')
@if(session('sucesso_config'))
    <div class="alerta alerta--sucesso">{{ session('sucesso_config') }}</div>
@endif

<form method="POST" action="{{ route('admin.configuracoes.salvar') }}" class="form-admin">
    @csrf

    {{-- ======================== LOJA ======================== --}}
    <x-config-section :legend="texto('admin_config', 'secao.loja', 'Loja')">
        <x-config-pair>
            <x-config-input name="taxa_entrega" :label="texto('admin_config', 'campo.taxa_entrega', 'Taxa de entrega (R$)')" type="number" :value="old('taxa_entrega', $valores['taxa_entrega'] ?? '0.00')" />
            <x-config-input name="margem_producao" :label="texto('admin_config', 'campo.margem', 'Margem de produção (%)')" type="number" :value="old('margem_producao', $valores['margem_producao'] ?? '20')" />
        </x-config-pair>
        <x-config-input name="chave_pix" :label="texto('admin_config', 'campo.chave_pix', 'Chave Pix exibida ao cliente')" :value="old('chave_pix', $valores['chave_pix'] ?? '')" />
    </x-config-section>

    {{-- ======================== NF-e ======================== --}}
    <x-config-section :legend="texto('admin_config', 'secao.nfe', 'Nota fiscal (NF-e / NFC-e)')">
        <x-config-toggle name="emitir_nfe" :label="texto('admin_config', 'campo.emitir_nfe', 'Habilitar emissão de nota fiscal nos pedidos')" :checked="(old('emitir_nfe', $valores['emitir_nfe'] ?? '0') === '1')" />

        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_config', 'nota.nfe', 'Com a emissão habilitada, cada pedido ganha o botão "Gerar NF". A transmissão à SEFAZ exige: certificado digital A1, CNPJ e dados fiscais dos produtos.') }}
        </p>

        <x-config-pair>
            <x-config-input name="empresa_cnpj" :label="texto('admin_config', 'campo.cnpj', 'CNPJ da empresa')" :value="old('empresa_cnpj', $valores['empresa_cnpj'] ?? '')" />
            <x-config-input name="empresa_inscricao_estadual" :label="texto('admin_config', 'campo.ie', 'Inscrição Estadual')" :value="old('empresa_inscricao_estadual', $valores['empresa_inscricao_estadual'] ?? '')" />
        </x-config-pair>

        <x-config-input name="empresa_razao_social" :label="texto('admin_config', 'campo.razao', 'Razão social')" :value="old('empresa_razao_social', $valores['empresa_razao_social'] ?? '')" />

        <label>{{ texto('admin_config', 'campo.ambiente', 'Ambiente SEFAZ') }}
            <select name="nfe_ambiente">
                <option value="2" {{ old('nfe_ambiente', $valores['nfe_ambiente'] ?? '2') === '2' ? 'selected' : '' }}>{{ texto('admin_config', 'ambiente.homologacao', 'Homologação (testes)') }}</option>
                <option value="1" {{ old('nfe_ambiente', $valores['nfe_ambiente'] ?? '2') === '1' ? 'selected' : '' }}>{{ texto('admin_config', 'ambiente.producao', 'Produção') }}</option>
            </select>
        </label>
    </x-config-section>

    {{-- ======================== WHATSAPP ======================== --}}
    <x-config-section :legend="texto('admin_config', 'secao.whatsapp', 'WhatsApp (envio automático pela API oficial)')">
        <x-config-toggle name="whatsapp_ativo" :label="texto('admin_config', 'campo.whatsapp_ativo', 'Enviar ofertas e senhas pela API oficial')" :checked="(old('whatsapp_ativo', $valores['whatsapp_ativo'] ?? '0') === '1')" />

        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_config', 'nota.whatsapp', 'Requer conta WhatsApp Business na Meta Cloud API. Preencha o token permanente e o Phone Number ID.') }}
        </p>

        <x-config-input name="whatsapp_token" :label="texto('admin_config', 'campo.whatsapp_token', 'Token permanente (access token)')" type="password" :value="old('whatsapp_token', $valores['whatsapp_token'] ?? '')" />
        <x-config-input name="whatsapp_phone_id" :label="texto('admin_config', 'campo.whatsapp_phone_id', 'Phone Number ID')" :value="old('whatsapp_phone_id', $valores['whatsapp_phone_id'] ?? '')" />
    </x-config-section>

    {{-- ======================== LOGIN SOCIAL (auth-multi) ======================== --}}
    <x-config-section
        :legend="texto('admin_config', 'secao.social', 'Login social')"
        :description="texto('admin_config', 'nota.social', 'Ative os provedores abaixo para que os botões de login social apareçam na tela de entrada.')"
    >
        @php
            $social = [
                [
                    'provider' => 'google',
                    'nome' => 'Google',
                    'campo_id' => 'google_client_id',
                    'campo_secret' => 'google_client_secret',
                    'campo_ativo' => 'google_login_ativo',
                    'label_id' => 'Client ID',
                    'label_secret' => 'Client secret',
                    'label_ativo' => 'Ativar login com Google',
                    'nota' => 'Crie um OAuth Client (tipo Web) no Google Cloud Console e cadastre a URI de redirecionamento: :callback',
                ],
                [
                    'provider' => 'facebook',
                    'nome' => 'Facebook',
                    'campo_id' => 'facebook_client_id',
                    'campo_secret' => 'facebook_client_secret',
                    'campo_ativo' => 'facebook_login_ativo',
                    'label_id' => 'App ID',
                    'label_secret' => 'App secret',
                    'label_ativo' => 'Ativar login com Facebook',
                    'nota' => 'Cadastre o App no Facebook for Developers e adicione a URI de redirecionamento: :callback',
                ],
                [
                    'provider' => 'microsoft',
                    'nome' => 'Microsoft',
                    'campo_id' => 'microsoft_client_id',
                    'campo_secret' => 'microsoft_client_secret',
                    'campo_ativo' => 'microsoft_login_ativo',
                    'label_id' => 'Application (client) ID',
                    'label_secret' => 'Client secret',
                    'label_ativo' => 'Ativar login com Microsoft',
                    'nota' => 'Registre uma aplicação no Azure Portal e adicione a URI de redirecionamento: :callback',
                ],
                [
                    'provider' => 'instagram',
                    'nome' => 'Instagram',
                    'campo_id' => 'instagram_client_id',
                    'campo_secret' => 'instagram_client_secret',
                    'campo_ativo' => 'instagram_login_ativo',
                    'label_id' => 'App ID',
                    'label_secret' => 'App secret',
                    'label_ativo' => 'Ativar login com Instagram',
                    'nota' => 'Crie um App no Facebook for Developers com o produto Instagram Graph API e cadastre a URI de redirecionamento: :callback',
                ],
            ];
        @endphp

        @foreach($social as $s)
            <h3>{{ $s['nome'] }}</h3>

            <x-config-toggle
                :name="$s['campo_ativo']"
                :label="$s['label_ativo']"
                :checked="(old($s['campo_ativo'], $valores[$s['campo_ativo']] ?? '0') === '1')"
            />

            <x-config-callback :provider="$s['provider']" :description="$s['nota']" />

            <x-config-pair>
                <x-config-input
                    :name="$s['campo_id']"
                    :label="$s['label_id']"
                    :value="old($s['campo_id'], $valores[$s['campo_id']] ?? '')"
                />
                <x-config-input
                    :name="$s['campo_secret']"
                    :label="$s['label_secret']"
                    type="password"
                    :value="old($s['campo_secret'], $valores[$s['campo_secret']] ?? '')"
                />
            </x-config-pair>
        @endforeach
    </x-config-section>

    {{-- ======================== PAGAMENTOS ======================== --}}
    <x-config-section :legend="texto('admin_config', 'secao.pagamentos', 'Pagamento online')">
        {{-- Mercado Pago --}}
        <x-config-toggle name="mercadopago_ativo" :label="texto('admin_config', 'campo.mercadopago_ativo', 'Aceitar cartão online via Mercado Pago')" :checked="(old('mercadopago_ativo', $valores['mercadopago_ativo'] ?? '0') === '1')" />

        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_config', 'nota.mercadopago', 'Crie uma aplicação em developers.mercadopago.com, copie o Access Token e cole abaixo.') }}
        </p>

        <ol class="passos-gateway">
            @foreach($passosMp as $passo)
                <li>{!! $passo !!}</li>
            @endforeach
        </ol>

        <x-config-input name="mercadopago_access_token" :label="texto('admin_config', 'campo.mercadopago_token', 'Access Token')" type="password" :value="old('mercadopago_access_token', $valores['mercadopago_access_token'] ?? '')" />
        <x-config-input name="mercadopago_public_key" :label="texto('admin_config', 'campo.mercadopago_public_key', 'Public Key (opcional)')" :value="old('mercadopago_public_key', $valores['mercadopago_public_key'] ?? '')" />

        {{-- Efí --}}
        <x-config-toggle name="efi_ativo" :label="texto('admin_config', 'campo.efi_ativo', 'Aceitar Pix automático via Efí')" :checked="(old('efi_ativo', $valores['efi_ativo'] ?? '0') === '1')" />

        <p class="nota-segura nota-segura--admin">
            {{ texto('admin_config', 'nota.efi', 'Crie um aplicativo em seuefi.com (produtos: Pix) e copie Client ID e Client Secret.') }}
        </p>

        <ol class="passos-gateway">
            @foreach($passosEfi as $passo)
                <li>{!! $passo !!}</li>
            @endforeach
        </ol>

        <x-config-pair>
            <x-config-input name="efi_client_id" :label="texto('admin_config', 'campo.efi_client_id', 'Client ID')" :value="old('efi_client_id', $valores['efi_client_id'] ?? '')" />
            <x-config-input name="efi_client_secret" :label="texto('admin_config', 'campo.efi_client_secret', 'Client Secret')" type="password" :value="old('efi_client_secret', $valores['efi_client_secret'] ?? '')" />
        </x-config-pair>

        <x-config-pair>
            <x-config-input name="efi_pix_chave" :label="texto('admin_config', 'campo.efi_pix_chave', 'Chave Pix do recebimento')" :value="old('efi_pix_chave', $valores['efi_pix_chave'] ?? '')" />
            <label class="caixa-marcar caixa-marcar--linha">
                <input type="checkbox" name="efi_sandbox" value="1" {{ old('efi_sandbox', $valores['efi_sandbox'] ?? '1') === '1' ? 'checked' : '' }}>
                {{ texto('admin_config', 'campo.efi_sandbox', 'Homologação (testes) — desligue para produção') }}
            </label>
        </x-config-pair>
    </x-config-section>

    {{-- ======================== ITEM-VENDA ======================== --}}
    <x-config-section
        :legend="texto('admin_config', 'secao.item_venda', 'Produtos e serviços (item-venda)')"
        :description="texto('admin_config', 'nota.item_venda', 'Controle geral do módulo de produtos e serviços. Os detalhes ficam na tela própria do módulo no menu.')"
    >
        <x-config-toggle name="item_venda_ativo" :label="texto('admin_config', 'campo.item_venda_ativo', 'Ativar cadastro de produtos e serviços')" :checked="(old('item_venda_ativo', $valores['item_venda_ativo'] ?? '0') === '1')" />

        <label>{{ texto('admin_config', 'campo.item_venda_tipo', 'O que o sistema vende') }}
            <select name="item_venda_tipo">
                <option value="produtos" {{ old('item_venda_tipo', $valores['item_venda_tipo'] ?? 'produtos') === 'produtos' ? 'selected' : '' }}>{{ texto('admin_config', 'tipo.produtos', 'Apenas produtos') }}</option>
                <option value="servicos" {{ old('item_venda_tipo', $valores['item_venda_tipo'] ?? 'produtos') === 'servicos' ? 'selected' : '' }}>{{ texto('admin_config', 'tipo.servicos', 'Apenas serviços') }}</option>
                <option value="ambos" {{ old('item_venda_tipo', $valores['item_venda_tipo'] ?? 'produtos') === 'ambos' ? 'selected' : '' }}>{{ texto('admin_config', 'tipo.ambos', 'Produtos e serviços') }}</option>
            </select>
        </label>
    </x-config-section>

    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">{{ texto('admin_config', 'botao.salvar', 'Salvar configurações') }}</button>
    </div>
</form>
@endsection

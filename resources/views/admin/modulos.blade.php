@extends('layouts.admin')

@section('titulo', texto('admin_modulos', 'titulo', 'Módulos — Gostosuras'))
@section('titulo_pagina', texto('admin_modulos', 'titulo.pagina', 'Módulos do sistema'))

@section('conteudo')
<div class="tabela-rolagem">
    <section class="painel-admin">
        <p class="nota-segura nota-segura--admin">{{ texto('admin_modulos', 'nota.banco', 'Os módulos são ligados e desligados APENAS direto no banco de dados, na tabela modulos: flag ativo = 1 (ligado) ou 0 (desligado). Esta tela apenas mostra o estado atual — não altere por aqui.') }}</p>
        <p class="nota-segura nota-segura--admin">{{ texto('admin_modulos', 'nota.escopo', 'Mostrando o estado para a loja ativa. Linha com loja própria vale para ela; senão vale a regra global (loja NULL).') }}</p>

        @forelse($modulos as $modulo)
            <article class="cartao-cupom {{ ! $modulo->ativo ? 'cartao-cupom--desligado' : '' }}">
                <div class="cartao-cupom__info">
                    <strong>{{ $modulo->nome }}</strong>
                    <small>{{ texto('admin_modulos', 'coluna.chave', 'Chave (slug)') }}: <code>{{ $modulo->slug }}</code></small>
                </div>
                <div class="cartao-cupom__acoes">
                    <span class="status-pilula {{ $modulo->ativo ? 'status-pilula--entregue' : 'status-pilula--cancelado' }}">
                        {{ $modulo->ativo ? texto('admin_modulos', 'status.ativo', 'Ativo') : texto('admin_modulos', 'status.inativo', 'Inativo') }}
                    </span>
                    <span class="texto-suave">
                        {{ $modulo->loja_id !== null ? texto('admin_modulos', 'escopo.loja', 'Apenas esta loja') : texto('admin_modulos', 'escopo.global', 'Global (todas as lojas)') }}
                    </span>
                </div>
            </article>
        @empty
            <p class="texto-suave">{{ texto('admin_modulos', 'vazio', 'Nenhum módulo cadastrado ainda.') }}</p>
        @endforelse
    </section>
</div>
@endsection
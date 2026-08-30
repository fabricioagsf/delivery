@extends('layouts.saas')

@section('titulo', 'Dashboard — SaaS')
@section('titulo_pagina', 'Dashboard')

@section('conteudo')
<div class="duas-colunas">
    <section class="painel-admin">
        <h2>Empresas</h2>
        <p class="nota-segura nota-segura--admin">Total de empresas ativas na plataforma.</p>
        <div class="estatistica">
            <span class="estatistica__numero">{{ App\Models\Saas\Empresa::where('ativo', true)->count() }}</span>
            <span class="estatistica__rotulo">Empresas ativas</span>
        </div>
        <a href="{{ route('saas.empresas.index') }}" class="botao">Gerenciar empresas</a>
    </section>
    <section class="painel-admin">
        <h2>Funcionários</h2>
        <p class="nota-segura nota-segura--admin">Total de funcionários registrados.</p>
        <div class="estatistica">
            <span class="estatistica__numero">{{ App\Models\Saas\Employee::where('ativo', true)->count() }}</span>
            <span class="estatistica__rotulo">Funcionários ativos</span>
        </div>
        <a href="{{ route('saas.employees.index') }}" class="botao">Gerenciar funcionários</a>
    </section>
</div>
@endsection

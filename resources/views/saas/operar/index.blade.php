@extends('layouts.saas')

@section('titulo', 'Operar — ' . (saas_employee_atual()?->name ?? ''))
@section('titulo_pagina', 'Selecione a filial para operar')
@section('conteudo')
@php
$employee = saas_employee_atual();
$filiais = $employee?->filiaisLiberadas() ?? collect();
@endphp

<p class="nota-segura nota-segura--admin">Olá, <strong>{{ $employee?->name }}</strong>. Selecione a filial onde você vai operar.</p>

@if($filiais->isEmpty())
    <p class="texto-suave">Nenhuma filial disponível. Solicite ao gerente.</p>
@else
    <div class="grade-acessos">
        @foreach($filiais as $filial)
            <a href="{{ route('admin.mesas-controle.index') }}?filial={{ $filial->id }}" class="cartao-acesso">
                <span class="cartao-acesso__icone">🏪</span>
                <strong>{{ $filial->nome }}</strong>
                <small>{{ $filial->dominio }}</small>
            </a>
        @endforeach
    </div>
@endif

<style>
.grade-acessos { display:grid; grid-template-columns: repeat(auto-fill,minmax(200px,1fr)); gap:16px; margin-top:24px; }
.cartao-acesso { display:flex; flex-direction:column; align-items:center; gap:8px; padding:24px; background:var(--cor-card); border:1px solid var(--cor-borda); border-radius:12px; text-align:center; text-decoration:none; color:inherit; transition:border-color .2s, transform .2s; }
.cartao-acesso:hover { border-color:var(--cor-primaria); transform:translateY(-2px); }
.cartao-acesso__icone { font-size:2.5rem; }
.cartao-acesso strong { font-size:1.1rem; }
.cartao-acesso small { color:var(--cor-texto-suave); font-size:.85rem; }
</style>

<div style="margin-top:32px;">
    <a href="{{ route('saas.dashboard') }}" class="botao botao--fantasma">Painel da empresa</a>
    <a href="{{ route('saas.logout') }}" class="botao" style="margin-left:8px;">Sair</a>
</div>
@endsection

@extends('layouts.saas')

@section('titulo', 'Login — SaaS')
@section('titulo_pagina', 'Acesso ao painel SaaS')

@section('conteudo')
<div class="saas-login">
    <form method="POST" action="{{ route('saas.login.processar') }}" class="painel-form">
        @csrf
        <label>E-mail
            <input type="email" name="email" required autofocus>
        </label>
        <label>Senha
            <input type="password" name="password" required>
        </label>
        @if($errors->any())
            <p class="form-mensagem form-mensagem--erro">{{ $errors->first() }}</p>
        @endif
        <button type="submit" class="botao botao--chefe bloco">Entrar</button>
    </form>
</div>
@endsection

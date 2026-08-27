<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', texto('erros', 'titulo.padrao', 'Ops! — Gostosuras'))</title>
    <link rel="stylesheet" href="{{ asset('css/loja.css') }}">
</head>
<body>
<main class="pagina pagina-erro">
    <section class="cartao-erro">
        <span class="cartao-erro__codigo">@yield('codigo')</span>
        <h1>@yield('titulo_pagina')</h1>
        <p>@yield('mensagem')</p>
        <a href="{{ url('/') }}" class="botao botao--chefe">{{ texto('erros', 'botao.voltar_loja', 'Voltar para a loja') }}</a>
    </section>
</main>
</body>
</html>

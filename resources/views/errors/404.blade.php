@extends('errors.base')

@section('titulo', texto('erros', 'titulo.404', 'Página não encontrada — Gostosuras'))
@section('codigo', '404')
@section('titulo_pagina', texto('erros', 'titulo_pagina.404', 'Essa página derreteu...'))
@section('mensagem', texto('erros', 'mensagem.404', 'Não encontramos o que você procura. Talvez a gostosura tenha sido movida ou o link veio errado.'))

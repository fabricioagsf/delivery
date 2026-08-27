@extends('errors.base')

@section('titulo', texto('erros', 'titulo.500', 'Erro inesperado — Gostosuras'))
@section('codigo', '500')
@section('titulo_pagina', texto('erros', 'titulo_pagina.500', 'Caiu açúcar no servidor'))
@section('mensagem', texto('erros', 'mensagem.500', 'Algo saiu do ponto na nossa cozinha e já estamos limpando a bagunça. Tente novamente em alguns instantes.'))

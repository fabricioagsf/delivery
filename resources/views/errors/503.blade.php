@extends('errors.base')

@section('titulo', texto('erros', 'titulo.503', 'Em manutenção — Gostosuras'))
@section('codigo', '503')
@section('titulo_pagina', texto('erros', 'titulo_pagina.503', 'Estamos reabastecendo a vitrine'))
@section('mensagem', texto('erros', 'mensagem.503', 'A loja está em manutenção rápida. Volte em alguns minutos — as gostosuras continuam aqui.'))

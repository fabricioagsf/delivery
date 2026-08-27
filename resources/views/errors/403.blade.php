@extends('errors.base')

@section('titulo', texto('erros', 'titulo.403', 'Acesso restrito — Gostosuras'))
@section('codigo', '403')
@section('titulo_pagina', texto('erros', 'titulo_pagina.403', 'Essa área não é sua (ainda!)'))
@section('mensagem', texto('erros', 'mensagem.403', 'Você não tem permissão para acessar este cantinho da confeitaria.'))

@extends('errors.base')

@section('titulo', texto('erros', 'titulo.419', 'Sessão expirada — Gostosuras'))
@section('codigo', '419')
@section('titulo_pagina', texto('erros', 'titulo_pagina.419', 'Seu doce esfriou'))
@section('mensagem', texto('erros', 'mensagem.419', 'A página ficou aberta tempo demais e a sessão expirou por segurança. Atualize a página e tente de novo.'))

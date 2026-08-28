@extends('layouts.admin')

@section('titulo', texto('admin_help', 'titulo', 'Ajuda — Gostosuras'))
@section('titulo_pagina', texto('admin_help', 'titulo_pagina', 'Ajuda do sistema'))

@section('conteudo')
<section class="painel-admin">
    @if(trim($html) === '')
        <p class="alerta alerta--erro">{{ texto('admin_help', 'vazio', 'Nenhum conteúdo de ajuda disponível.') }}</p>
    @else
        <div class="help-doc">
            {!! $html !!}
        </div>
    @endif
</section>
@endsection

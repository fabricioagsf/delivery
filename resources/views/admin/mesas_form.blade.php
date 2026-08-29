@extends('layouts.admin')

@section('titulo', texto('admin_mesas', 'titulo.form', 'Mesa — Gostosuras'))
@section('titulo_pagina', $mesa->exists ? texto('admin_mesas', 'form.titulo_editar', 'Editar mesa') : texto('admin_mesas', 'form.titulo_novo', 'Nova mesa'))

@section('conteudo')
<section class="painel-admin painel-admin--estreito">
    @if($errors->any())
        <div class="alerta alerta--erro">
            <ul>
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" class="form-admin"
          action="{{ $mesa->exists ? route('admin.mesas.update', $mesa) : route('admin.mesas.store') }}">
        @csrf

        <fieldset class="secao-form">
            <legend>{{ texto('admin_mesas', 'form.secao.identificacao', 'Identificação') }}</legend>

            <label>{{ texto('admin_mesas', 'campo.nome', 'Nome (ex.: Mesa 1, Varanda)') }}
                <input type="text" name="nome" value="{{ old('nome', $mesa->nome) }}" maxlength="50" required>
            </label>

            <label>{{ texto('admin_mesas', 'campo.codigo', 'Código (opcional, usado em relatórios e no QR)') }}
                <input type="text" name="codigo" value="{{ old('codigo', $mesa->codigo) }}" maxlength="10" placeholder="M1" autocomplete="off">
            </label>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_mesas', 'form.secao.detalhes', 'Detalhes') }}</legend>

            <label>{{ texto('admin_mesas', 'campo.capacidade', 'Capacidade (pessoas)') }}
                <input type="number" name="capacidade" min="1" max="20" value="{{ old('capacidade', $mesa->capacidade ?? 4) }}" required>
            </label>

            <label class="interruptor-linha">
                <input type="checkbox" class="interruptor-caixa" name="ativo" value="1" id="interruptor-ativo" @checked(old('ativo', $mesa->ativo ?? true))>
                <span class="interruptor" aria-hidden="true"></span>
                <span>{{ texto('admin_mesas', 'campo.ativo', 'Mesa ativa') }}</span>
            </label>
        </fieldset>

        <div class="rodape-form">
            <button type="submit" class="botao botao--chefe">{{ texto('admin_mesas', 'botao.salvar', 'Salvar mesa') }}</button>
            <a href="{{ route('admin.mesas.index') }}" class="botao">{{ texto('admin_mesas', 'botao.cancelar', 'Cancelar') }}</a>
        </div>
    </form>
</section>
@endsection

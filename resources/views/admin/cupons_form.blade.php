@extends('layouts.admin')

@section('titulo', texto('admin_cupons', 'titulo.form', 'Cupom — Gostosuras'))
@section('titulo_pagina', $cupom->exists ? texto('admin_cupons', 'form.titulo_editar', 'Editar cupom') : texto('admin_cupons', 'form.titulo_novo', 'Novo cupom'))

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
          action="{{ $cupom->exists ? route('admin.cupons.update', $cupom) : route('admin.cupons.store') }}">
        @csrf

        <fieldset class="secao-form">
            <legend>{{ texto('admin_cupons', 'form.secao.identificacao', 'Identificação') }}</legend>

            <label>{{ texto('admin_cupons', 'campo.codigo', 'Código do cupom') }}
                <input type="text" name="codigo" value="{{ old('codigo', $cupom->codigo) }}" maxlength="40" placeholder="EX: FESTA10" class="texto-maiusculo" autocomplete="off">
            </label>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_cupons', 'form.secao.desconto', 'Desconto') }}</legend>

            <div class="grade-2">
                <label>{{ texto('admin_cupons', 'campo.tipo', 'Tipo') }}
                    <select name="tipo">
                        <option value="percentual" @selected(old('tipo', $cupom->tipo) === 'percentual')>{{ texto('admin_cupons', 'opcao.percentual', 'Percentual (%)') }}</option>
                        <option value="fixo" @selected(old('tipo', $cupom->tipo) === 'fixo')>{{ texto('admin_cupons', 'opcao.fixo', 'Valor fixo (R$)') }}</option>
                    </select>
                </label>
                <label>{{ texto('admin_cupons', 'campo.valor', 'Valor do desconto') }}
                    <input type="number" name="valor" step="0.01" min="0.01" value="{{ old('valor', $cupom->valor) }}" placeholder="10">
                </label>
            </div>

            <label>{{ texto('admin_cupons', 'campo.valor_minimo', 'Pedido mínimo (opcional — vazio = sem mínimo)') }}
                <input type="number" name="valor_minimo" step="0.01" min="0" value="{{ old('valor_minimo', $cupom->valor_minimo ?? '') }}" placeholder="0,00">
            </label>
        </fieldset>

        <fieldset class="secao-form">
            <legend>{{ texto('admin_cupons', 'form.secao.restricoes', 'Validade e limites') }}</legend>

            <label>{{ texto('admin_cupons', 'campo.limite_usos', 'Limite de usos (opcional — vazio = ilimitado)') }}
                <input type="number" name="limite_usos" min="1" value="{{ old('limite_usos', $cupom->limite_usos ?? '') }}" placeholder="—">
            </label>

            <div class="grade-2">
                <label>{{ texto('admin_cupons', 'campo.inicio_em', 'Válido a partir de') }}
                    <input type="datetime-local" name="inicio_em" value="{{ old('inicio_em', $cupom->inicio_em?->format('Y-m-d\TH:i')) }}">
                </label>
                <label>{{ texto('admin_cupons', 'campo.fim_em', 'Válido até') }}
                    <input type="datetime-local" name="fim_em" value="{{ old('fim_em', $cupom->fim_em?->format('Y-m-d\TH:i')) }}">
                </label>
            </div>

            <label class="interruptor-linha">
                <input type="checkbox" class="interruptor-caixa" name="ativo" value="1" id="interruptor-ativo" @checked(old('ativo', $cupom->ativo ?? true))>
                <span class="interruptor" aria-hidden="true"></span>
                <span>{{ texto('admin_cupons', 'campo.ativo', 'Cupom ativo') }}</span>
            </label>
        </fieldset>

        <div class="rodape-form">
            <button type="submit" class="botao botao--chefe">{{ texto('admin_cupons', 'botao.salvar', 'Salvar cupom') }}</button>
            <a href="{{ route('admin.cupons.index') }}" class="botao">{{ texto('admin_cupons', 'botao.cancelar', 'Cancelar') }}</a>
        </div>
    </form>
</section>
@endsection

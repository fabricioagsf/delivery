@extends('layouts.saas')

@section('titulo', $employee->exists ? 'Editar funcionário' : 'Novo funcionário')
@section('titulo_pagina', $employee->exists ? 'Editar funcionário' : 'Novo funcionário')

@section('conteudo')
<form method="POST" action="{{ $employee->exists ? route('saas.employees.update', $employee) : route('saas.employees.store') }}" class="form-admin">
    @csrf
    @if($employee->exists) @method('PUT') @endif

    <fieldset class="secao-form">
        <legend>Dados</legend>
        <label>Nome
            <input type="text" name="name" value="{{ old('name', $employee->name) }}" required>
        </label>
        <label>E-mail
            <input type="email" name="email" value="{{ old('email', $employee->email) }}" required>
        </label>
        <label>Senha {{ $employee->exists ? '(deixe vazio para manter)' : '' }}
            <input type="password" name="password" {{ $employee->exists ? '' : 'required' }}>
        </label>
        <label>Cargo
            <input type="text" name="cargo" value="{{ old('cargo', $employee->cargo) }}">
        </label>
        <label class="caixa-marcar">
            <input type="checkbox" name="ativo" value="1" {{ old('ativo', $employee->ativo ?? true) ? 'checked' : '' }}>
            Ativo
        </label>
    </fieldset>

    <fieldset class="secao-form">
        <legend>Filiais (acesso)</legend>
        @forelse($filiais as $filial)
            <label class="caixa-marcar">
                <input type="checkbox" name="filiais[]" value="{{ $filial->id }}"
                    {{ in_array($filial->id, old('filiais', $employee->exists ? $employee->filiais->pluck('id')->all() : []), true) ? 'checked' : '' }}>
                {{ $filial->nome }}
            </label>
        @empty
            <p class="texto-suave">Sem filiais cadastradas. Crie uma filial antes.</p>
        @endforelse
    </fieldset>

    <fieldset class="secao-form">
        <legend>Papéis</legend>
        @forelse($roles as $role)
            <label class="caixa-marcar">
                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                    {{ in_array($role->id, old('roles', $employee->exists ? $employee->roles->pluck('id')->all() : []), true) ? 'checked' : '' }}>
                <strong>{{ $role->nome }}</strong> — <small>{{ $role->descricao }}</small>
            </label>
        @empty
            <p class="texto-suave">Sem papéis cadastrados.</p>
        @endforelse
    </fieldset>

    <div class="rodape-form">
        <button type="submit" class="botao botao--chefe">Salvar</button>
        <a href="{{ route('saas.employees.index') }}" class="botao botao--fantasma">Cancelar</a>
    </div>
</form>
@endsection

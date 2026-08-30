@extends('layouts.saas')

@section('titulo', 'Funcionários')
@section('titulo_pagina', 'Funcionários')
@section('acoes')
    <a href="{{ route('saas.employees.create') }}" class="botao botao--chefe">Novo funcionário</a>
@endsection

@section('conteudo')
<section class="painel-admin">
    @if(session('sucesso'))
        <div class="alerta alerta--sucesso">{{ session('sucesso') }}</div>
    @endif
    <div class="tabela-rolagem">
    <table class="tabela">
        <thead>
        <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>Cargo</th>
            <th>Filiais</th>
            <th>Papéis</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($employees as $employee)
            <tr>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->email }}</td>
                <td>{{ $employee->cargo }}</td>
                <td>{{ $employee->filiais->pluck('nome')->join(', ') ?: '—' }}</td>
                <td>{{ $employee->roles->pluck('nome')->join(', ') ?: '—' }}</td>
                <td>
                    <span class="status-pilula {{ $employee->ativo ? 'status-pilula--entregue' : '' }}">
                        {{ $employee->ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('saas.employees.edit', $employee) }}" class="mini-botao">Editar</a>
                    <form method="POST" action="{{ route('saas.employees.destroy', $employee) }}" style="display:inline" onsubmit="return confirm('Remover funcionário?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="mini-botao mini-botao--excluir">Remover</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="texto-suave">Nenhum funcionário.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</section>
@endsection

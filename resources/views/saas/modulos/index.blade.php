@extends('layouts.saas')

@section('titulo', 'Módulos da plataforma')
@section('titulo_pagina', 'Módulos')

@section('conteudo')
<section class="painel-admin">
    <p class="nota-segura nota-segura--admin">Ativa/desativa módulos em todas as filiais da empresa.</p>
    <div class="tabela-rolagem">
    <table class="tabela">
        <thead>
        <tr><th>Módulo</th><th>Slug</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @forelse($modulos as $modulo)
            <tr>
                <td>{{ $modulo->nome }}</td>
                <td><code>{{ $modulo->slug }}</code></td>
                <td>
                    <span class="status-pilula {{ $modulo->ativo ? 'status-pilula--entregue' : '' }}">
                        {{ $modulo->ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                </td>
                <td>
                    <button type="button" class="interruptor {{ $modulo->ativo ? 'ligado' : '' }}"
                            data-funcao="saas-alternar-modulo"
                            data-slug="{{ $modulo->slug }}"
                            data-url="{{ route('saas.modulos.toggle', $modulo->slug) }}">
                    </button>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="texto-suave">Nenhum módulo cadastrado.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-funcao="saas-alternar-modulo"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(r => r.json()).then(r => {
                btn.classList.toggle('ligado', r.ativo);
            });
        });
    });
</script>
@endpush

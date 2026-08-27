@extends('layouts.admin')

@section('titulo', texto('admin_auditoria', 'titulo.detalhe', 'Evento de auditoria — Gostosuras'))
@section('titulo_pagina', str_replace(':id', $log->id, texto('admin_auditoria', 'titulo.detalhe.pagina', 'Evento #:id')))

@section('conteudo')
<p class="frase-evento">{{ \App\Support\AuditoriaFormatador::frase($log) }}</p>

<div class="duas-colunas">
    <section class="painel-admin">
        <h2>{{ texto('admin_auditoria', 'detalhe.alteracoes', 'O que mudou') }}</h2>

        @if($log->acao === 'UPDATE' && $log->dados_antigos && $log->dados_novos)
            <table class="tabela">
                <thead>
                <tr>
                    <th>{{ texto('admin_auditoria', 'diff.campo', 'Campo') }}</th>
                    <th>{{ texto('admin_auditoria', 'diff.antes', 'Antes') }}</th>
                    <th>{{ texto('admin_auditoria', 'diff.depois', 'Depois') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($log->dados_novos as $campo => $novo)
                    @php
                        $antigo = $log->dados_antigos[$campo] ?? null;
                    @endphp
                    @continue(is_array($novo) ? json_encode($novo) === json_encode($antigo) : $novo === $antigo)
                    <tr>
                        <td><strong>{{ \App\Support\AuditoriaFormatador::campo($campo) }}</strong></td>
                        <td class="diff-antes">{{ \App\Support\AuditoriaFormatador::valor($campo, $antigo) }}</td>
                        <td class="diff-depois">{{ \App\Support\AuditoriaFormatador::valor($campo, $novo) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @elseif($log->acao === 'DELETE' && $log->dados_antigos)
            <p class="nota-segura nota-segura--admin">{{ texto('admin_auditoria', 'detalhe.delete_explicacao', 'Este registro foi excluído. O snapshot abaixo permite reinseri-lo exatamente como estava.') }}</p>
            <table class="tabela">
                @foreach($log->dados_antigos as $campo => $valor)
                    <tr><td><strong>{{ \App\Support\AuditoriaFormatador::campo($campo) }}</strong></td><td>{{ \App\Support\AuditoriaFormatador::valor($campo, $valor) }}</td></tr>
                @endforeach
            </table>
        @elseif($log->dados_novos)
            <p class="nota-segura nota-segura--admin">{{ texto('admin_auditoria', 'detalhe.insert_explicacao', 'Novo registro criado com os dados abaixo.') }}</p>
            <table class="tabela">
                @foreach($log->dados_novos as $campo => $valor)
                    <tr><td><strong>{{ \App\Support\AuditoriaFormatador::campo($campo) }}</strong></td><td>{{ \App\Support\AuditoriaFormatador::valor($campo, $valor) }}</td></tr>
                @endforeach
            </table>
        @else
            <p class="texto-suave">{{ texto('admin_auditoria', 'detalhe.sem_dados', 'Sem snapshot detalhado para este evento.') }}</p>
        @endif

        <a href="{{ route('admin.auditoria.index') }}" class="link-voltar">{{ texto('confirmacao', 'botao.voltar', 'Voltar') }}</a>
    </section>

    <aside>
        <section class="painel-admin">
            <h2>{{ texto('admin_auditoria', 'restaurar.titulo', 'Restaurar este estado') }}</h2>
            <p class="texto-suave">
                @if($log->acao === 'INSERT')
                    {{ texto('admin_auditoria', 'restaurar.explica_insert', 'O registro voltará ao estado em que foi criado (snapshot da criação).') }}
                @elseif($log->acao === 'UPDATE')
                    {{ texto('admin_auditoria', 'restaurar.explica_update', 'O registro voltará exatamente ao estado registrado logo depois desta alteração.') }}
                @else
                    {{ texto('admin_auditoria', 'restaurar.explica_delete', 'O registro excluído será trazido de volta como estava antes de ser excluído.') }}
                @endif
            </p>

            @if($errors->any())
                <div class="alerta alerta--erro">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.auditoria.restaurar', $log) }}">
                @csrf
                <label>{{ texto('admin_auditoria', 'restaurar.campo_senha', 'Senha master') }}
                    <input type="password" name="senha_master" required autocomplete="off">
                </label>
                <button type="submit" class="botao botao--chefe bloco">{{ texto('admin_auditoria', 'restaurar.botao', 'Retornar a este estado') }}</button>
            </form>
        </section>

        <section class="painel-admin margem-topo">
            <h2>{{ texto('admin_auditoria', 'detalhe.evento_titulo', 'Sobre o evento') }}</h2>
            <p>
                <span class="status-pilula status-pilula--{{ strtolower($log->acao_legivel) }}">{{ texto('admin_auditoria', 'acao.' . strtolower($log->acao), $log->acao_legivel) }}</span>
                · {{ texto('admin_auditoria', 'origem.' . $log->origem, ucfirst($log->origem)) }}
            </p>
            <p class="resumo-linha"><span>{{ texto('admin_pedidos', 'tabela.quando', 'Quando') }}</span><span>{{ $log->criado_em?->format('d/m/Y H:i:s') }}</span></p>
            <p class="resumo-linha"><span>{{ texto('admin_auditoria', 'coluna.registro', 'Registro') }}</span><span>{{ $log->tabela }}#{{ $log->registro_id }}</span></p>
            @if($log->autor)
                <p class="resumo-linha"><span>{{ texto('admin_pedidos', 'detalhe.cliente_titulo', 'Cliente') }}</span><span>{{ $log->autor }}</span></p>
            @endif
            @if($log->url)
                <p class="texto-suave">{{ texto('admin_auditoria', 'coluna.url', 'URL') }}: {{ \Illuminate\Support\Str::limit($log->url, 60) }}</p>
            @endif
        </section>
    </aside>
</div>
@endsection

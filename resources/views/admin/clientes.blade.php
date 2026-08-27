@extends('layouts.admin')

@section('titulo', texto('admin_clientes', 'titulo', 'Clientes — Gostosuras'))
@section('titulo_pagina', texto('admin_clientes', 'titulo.pagina', 'Clientes cadastrados'))

@section('conteudo')
<div class="cartoes-resumo">
    <article class="cartao-metrica">
        <small>{{ texto('admin_clientes', 'metrica.total', 'Clientes com conta') }}</small>
        <strong>{{ $totalContas }}</strong>
    </article>
    <article class="cartao-metrica">
        <small>{{ texto('admin_clientes', 'metrica.com_pedidos', 'Já compraram') }}</small>
        <strong>{{ $comPedidos }}</strong>
    </article>
    <article class="cartao-metrica">
        <small>{{ texto('admin_clientes', 'metrica.novos_mes', 'Novos neste mês') }}</small>
        <strong>{{ $novosNoMes }}</strong>
    </article>
</div>

<section class="painel-admin">
    <h2>{{ texto('admin_clientes', 'campanha.titulo', 'Enviar oferta / mensagem no WhatsApp') }}</h2>

    <div class="campanha-grade">
        <div class="campanha-mensagem">
            <p class="nota-segura nota-segura--admin">{{ texto('admin_clientes', 'campanha.nota', 'Escreva a mensagem, escolha os clientes (nada selecionado = todos) e abrirá a conversa pronta no WhatsApp — você só aperta enviar. Use :nome para chamar cada cliente pelo primeiro nome.') }}</p>

            <textarea id="mensagem-campanha" class="textarea-campanha" rows="5" maxlength="500"
                      placeholder="{{ texto('admin_clientes', 'campanha.placeholder', 'Ex.: Olá :nome! Hoje os brigadeiros gourmet estão com 20% OFF — peça pelo site! 🍫') }}"></textarea>

            <div class="contador-caracteres">
                <span id="campanha-caracteres">0</span>
            </div>
        </div>

        <aside class="caixa-envio">
            <h3>{{ texto('admin_clientes', 'campanha.lista_titulo', 'Lista de envio') }}</h3>

            <p class="caixa-envio__contador" id="campanha-contador"></p>

            <label class="caixa-marcar">
                <input type="checkbox" id="selecionar-todos">
                <span>{{ texto('admin_clientes', 'campanha.selecionar_todos', 'Selecionar todos') }}</span>
            </label>

            <p class="caixa-envio__nota">{{ texto('admin_clientes', 'campanha.nota_todos', 'Nada marcado = envia para todos.') }}</p>

            <button type="button" class="botao botao--whats bloco" id="abrir-campanha">
                {{ texto('admin_clientes', 'campanha.botao', 'Abrir WhatsApp dos selecionados') }}
            </button>

            <span class="texto-suave" id="campanha-status"></span>
        </aside>
    </div>
</section>

<section class="painel-admin">
    <h2>{{ texto('admin_clientes', 'lista.titulo', 'Contas cadastradas') }}</h2>
    <p class="nota-segura nota-segura--admin">{{ texto('admin_clientes', 'lista.nota_privacidade', 'Senhas e chaves de segurança nunca são exibidas. A opção de senha gera uma nova senha temporária e prepara a mensagem no WhatsApp.') }}</p>

    <div class="tabela-rolagem">
        <table class="tabela tabela--clientes">
            <thead>
            <tr>
                <th class="celula-check">
                    <input type="checkbox" class="selecionar-campanha-tudo" title="{{ texto('admin_clientes', 'campanha.selecionar_todos', 'Selecionar todos') }}">
                </th>
                <th>{{ texto('admin_clientes', 'coluna.nome', 'Cliente') }}</th>
                <th>{{ texto('admin_clientes', 'coluna.contato', 'Contato') }}</th>
                <th>{{ texto('admin_clientes', 'coluna.desde', 'Cliente desde') }}</th>
                <th>{{ texto('admin_clientes', 'coluna.pedidos', 'Pedidos') }}</th>
                <th>{{ texto('admin_clientes', 'coluna.total_gasto', 'Total comprado') }}</th>
                <th>{{ texto('admin_clientes', 'coluna.acoes', 'Ações') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($clientes as $cliente)
                <tr data-cliente="{{ $cliente->id }}" data-nome="{{ $cliente->nome }}">
                    <td class="celula-check">
                        <input type="checkbox" class="selecionar-campanha" value="{{ $cliente->id }}" aria-label="{{ texto('admin_clientes', 'coluna.incluir', 'Incluir na oferta') }}">
                    </td>
                    <td><strong>{{ $cliente->nome }}</strong></td>
                    <td>
                        {{ $cliente->telefone }}<br>
                        <small class="texto-suave">{{ $cliente->email }}</small>
                    </td>
                    <td>{{ $cliente->created_at?->format('d/m/Y') }}</td>
                    <td>{{ $cliente->pedidos_count }}</td>
                    <td class="celula-preco">{{ preco_br($cliente->total_gasto ?? 0) }}</td>
                    <td class="celula-acoes">
                        <button type="button" class="mini-botao mini-botao--salvar" data-funcao="senha-whats" data-url="{{ route('admin.clientes.senha', $cliente) }}">
                            {{ texto('admin_clientes', 'botao.senha_whats', 'Nova senha no WhatsApp') }}
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="texto-suave">{{ texto('admin_clientes', 'lista.vazia', 'Nenhum cliente com conta ainda.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $clientes->links('vendor.pagination.padrao') }}
</section>
@endsection

@push('scripts')
    <script>
        window.TextosClientes = {
            confirmarSenha: '{{ texto('admin_clientes', 'js.confirmar_senha', 'Gerar uma NOVA senha para este cliente? A antiga deixa de funcionar e a mensagem do WhatsApp será aberta.') }}',
            mensagemCurta: '{{ texto('admin_clientes', 'js.mensagem_curta', 'Escreva a mensagem da oferta primeiro.') }}',
            abrindo: '{{ texto('admin_clientes', 'js.abrindo', 'Abrindo :qtd conversa(s) no WhatsApp...') }}',
            contadorMarcados: '{{ texto('admin_clientes', 'campanha.contador_marcados', ':qtd selecionado(s) para receber a oferta') }}',
            contadorTodos: '{{ texto('admin_clientes', 'campanha.contador_todos', 'Todos os :qtd clientes vão receber') }}',
            aberto: '{{ texto('admin_clientes', 'js.aberto', ':qtd conversa(s) aberta(s) no WhatsApp — basta apertar enviar em cada uma.') }}',
            statusApi: '{{ texto('admin_clientes', 'campanha.status_api', 'API do WhatsApp: :enviados de :qtd mensagem(ns) enviada(s) com sucesso.') }}',
            erroPopup: '{{ texto('admin_clientes', 'js.erro_popup', 'O navegador bloqueou as janelas do WhatsApp — permita pop-ups para este site e tente de novo.') }}'
        };

        (function () {
            var mensagem = document.getElementById('mensagem-campanha');
            var contadorCaracteres = document.getElementById('campanha-caracteres');
            var contador = document.getElementById('campanha-contador');
            var selecionarTudo = document.getElementById('selecionar-todos');
            var selecionarTudoTabela = document.querySelector('.selecionar-campanha-tudo');
            var caixas = Array.prototype.slice.call(document.querySelectorAll('.selecionar-campanha'));
            var LIMITE = Number(mensagem?.maxLength || 500);

            function atualizarContadorCaracteres() {
                if (contadorCaracteres) contadorCaracteres.textContent = mensagem.value.length + '/' + LIMITE;
            }

            function atualizarContadorEnvio() {
                if (!contador) return;
                var marcados = caixas.filter(function (c) { return c.checked; }).length;
                contador.textContent = marcados > 0
                    ? TextosClientes.contadorMarcados.replace(':qtd', marcados)
                    : TextosClientes.contadorTodos.replace(':qtd', caixas.length);

                if (selecionarTudo && selecionarTudo !== this) {
                    selecionarTudo.checked = caixas.length > 0 && marcados === caixas.length;
                }
                if (selecionarTudoTabela) {
                    selecionarTudoTabela.checked = caixas.length > 0 && marcados === caixas.length;
                }
            }

            function alternarTodos(estado) {
                caixas.forEach(function (c) { c.checked = estado; });
                atualizarContadorEnvio();
            }

            mensagem?.addEventListener('input', atualizarContadorCaracteres);
            caixas.forEach(function (c) { c.addEventListener('change', atualizarContadorEnvio); });
            selecionarTudo?.addEventListener('change', function () { alternarTodos(this.checked); });
            selecionarTudoTabela?.addEventListener('change', function () { alternarTodos(this.checked); });

            atualizarContadorCaracteres();
            atualizarContadorEnvio();
        })();

        function whatsUrl(digitos, mensagem) {
            if (digitos.length <= 11) digitos = '55' + digitos;
            return 'https://wa.me/' + digitos + '?text=' + encodeURIComponent(mensagem);
        }

        // Abre janelas em branco AINDA no clique (gesto do usuário) — se esperar
        // o fetch responder, o bloqueador de popups do navegador mata as abas.
        function prepararJanelas(quantidade) {
            var janelas = [];
            for (var i = 0; i < quantidade; i++) {
                var j = window.open('', '_blank');
                if (!j) {
                    janelas.forEach(function (aberta) { aberta.close(); });
                    toast(TextosClientes.erroPopup, 'erro');
                    return null;
                }
                j.document.write('<p style="font-family:sans-serif;padding:24px;color:#6b4226">Abrindo o WhatsApp…</p>');
                janelas.push(j);
            }
            return janelas;
        }

        function enviarJanelas(janelas, links) {
            links.forEach(function (item, indice) {
                janelas[indice].location.href = item.whats;
            });
        }

        function descartarJanelas(janelas) {
            janelas.forEach(function (j) { try { j.close(); } catch (e) {} });
        }

        document.querySelectorAll('[data-funcao="senha-whats"]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                if (!confirm(TextosClientes.confirmarSenha)) return;

                var janela = prepararJanelas(1);
                if (!janela) return;

                fetch(botao.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).then(function (r) { return r.json(); }).then(function (r) {
                    if (r.modo === 'api') {
                        // Enviado pela API oficial: a janela preparada vira lixo
                        descartarJanelas(janela);
                    } else {
                        enviarJanelas(janela, [{ whats: r.whats }]);
                    }
                    toast(r.mensagem);
                    navigator.clipboard?.writeText(r.senha);
                }).catch(function (e) {
                    descartarJanelas(janela);
                    toast(e.message, 'erro');
                });
            });
        });

        document.getElementById('abrir-campanha')?.addEventListener('click', function () {
            var mensagem = document.getElementById('mensagem-campanha').value.trim();
            var status = document.getElementById('campanha-status');

            if (mensagem.length < 5) { toast(TextosClientes.mensagemCurta, 'erro'); return; }

            var selecionados = Array.prototype.map.call(
                document.querySelectorAll('.selecionar-campanha:checked'),
                function (c) { return Number(c.value); }
            );

            var alvo = selecionados.length > 0
                ? selecionados.length
                : document.querySelectorAll('.selecionar-campanha').length;

            var janelas = prepararJanelas(alvo);
            if (!janelas) return;

            status.textContent = TextosClientes.abrindo.replace(':qtd', alvo);

            fetch('{{ route('admin.clientes.campanha') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ mensagem: mensagem, ids: selecionados })
            }).then(function (r) { return r.json(); }).then(function (r) {
                if (r.modo === 'api') {
                    // Enviado pela API oficial: fecha as janelas preparadas
                    descartarJanelas(janelas);
                    status.textContent = TextosClientes.statusApi
                        .replace(':enviados', r.enviados)
                        .replace(':qtd', r.quantidade);
                    if (r.falhas && r.falhas.length) {
                        toast(r.falhas[0].nome + ': ' + r.falhas[0].erro, 'erro');
                    }
                } else {
                    enviarJanelas(janelas, r.links);
                    status.textContent = TextosClientes.aberto.replace(':qtd', r.quantidade);
                }
            }).catch(function (e) {
                descartarJanelas(janelas);
                status.textContent = '';
                toast(e.message, 'erro');
            });
        });
    </script>
@endpush

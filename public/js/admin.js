/* GOSTOSURAS — interações do painel (AJAX puro) */
(function () {
    'use strict';

    var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function enviar(url, dados) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(dados || {})
        }).then(function (resposta) {
            return resposta.json().then(function (corpo) {
                if (!resposta.ok) throw new Error(corpo.mensagem || 'Erro inesperado');
                return corpo;
            });
        });
    }

    var toastEl = document.createElement('div');
    toastEl.className = 'toast';
    document.body.appendChild(toastEl);
    var timer;

    function toast(mensagem, tipo) {
        clearTimeout(timer);
        toastEl.textContent = mensagem;
        toastEl.className = 'toast visivel' + (tipo === 'erro' ? ' erro' : '');
        timer = setTimeout(function () { toastEl.classList.remove('visivel'); }, 2600);
    }

    /* -------- produtos / estoque -------- */
    document.querySelectorAll('[data-produto] [data-funcao]').forEach(function (controle) {
        controle.addEventListener('click', function () {
            var linha = controle.closest('[data-produto]');
            var id = linha.dataset.produto;
            var funcao = controle.dataset.funcao;

            var promessa;
            if (funcao === 'salvar-estoque') {
                promessa = enviar(Rotas.produtoEstoque.replace('ID', id), {
                    estoque: linha.querySelector('[data-campo="estoque"]').value,
                    estoque_minimo: linha.querySelector('[data-campo="estoque_minimo"]').value || null
                });
            } else if (funcao === 'alternar-ativo') {
                promessa = enviar(Rotas.produtoAtivo.replace('ID', id)).then(function (r) {
                    controle.classList.toggle('ligado', r.ativo);
                    return r;
                });
            } else if (funcao === 'alternar-destaque') {
                promessa = enviar(Rotas.produtoDestaque.replace('ID', id)).then(function (r) {
                    controle.classList.toggle('ligado', r.destaque);
                    return r;
                });
            }

            if (!promessa) return;

            promessa.then(function (r) {
                toast(r.mensagem);
                if (funcao === 'salvar-estoque') {
                    var retorno = linha.querySelector('.retorno-linha');
                    retorno.textContent = '✓';
                    setTimeout(function () { retorno.textContent = ''; }, 2200);
                }
            }).catch(function (e) { toast(e.message, 'erro'); });
        });
    });

    /* -------- pedidos: mudar status -------- */
    function mudarStatus(id, status, seletor) {
        enviar(Rotas.pedidoStatus.replace('ID', id), { status: status })
            .then(function (r) { toast(r.mensagem); })
            .catch(function (e) {
                toast(e.message, 'erro');
                if (seletor && window.__statusOriginal) seletor.value = window.__statusOriginal[id];
            });
    }

    document.querySelectorAll('[data-pedido] .seletor-status').forEach(function (seletor) {
        seletor.addEventListener('focus', function () {
            window.__statusOriginal = window.__statusOriginal || {};
            window.__statusOriginal[seletor.closest('[data-pedido]').dataset.pedido] = seletor.value;
        });
        seletor.addEventListener('change', function () {
            mudarStatus(seletor.closest('[data-pedido]').dataset.pedido, seletor.value, seletor);
        });
    });

    var detalheStatus = document.getElementById('detalhe-status');
    if (detalheStatus) {
        detalheStatus.addEventListener('change', function () {
            mudarStatus(detalheStatus.dataset.id, detalheStatus.value, detalheStatus);
        });
    }

    /* -------- produto: personalizações (complementos) -------- */
    var listaComplementos = document.getElementById('lista-complementos');
    if (listaComplementos) {
        var compIndex = listaComplementos.querySelectorAll('.linha-complemento').length;

        function desativarPreco(linha, desativar) {
            var campoPreco = linha.querySelector('[name$="[preco]"]');
            if (!campoPreco) return;
            campoPreco.disabled = desativar;
            if (desativar) campoPreco.value = '0';
        }

        function aplicarRegraPreco(linha) {
            var select = linha.querySelector('[data-campo="tipo"]');
            desativarPreco(linha, select.value === 'remocao');
        }

        // Mantém coberto o campo tipo/preco mesmo com o documento já carregado
        listaComplementos.querySelectorAll('.linha-complemento').forEach(aplicarRegraPreco);

        listaComplementos.addEventListener('change', function (evento) {
            if (evento.target.dataset.campo === 'tipo') {
                aplicarRegraPreco(evento.target.closest('.linha-complemento'));
            }
        });

        listaComplementos.addEventListener('click', function (evento) {
            var botao = evento.target.closest('[data-remover]');
            if (!botao) return;
            botao.closest('.linha-complemento').remove();
        });

        var botaoAdicionar = document.getElementById('adicionar-complemento');
        if (botaoAdicionar) {
            botaoAdicionar.addEventListener('click', function () {
                var modelo = document.querySelector('#lista-complementos .linha-complemento');
                if (!modelo) return;

                var novaLinha = modelo.cloneNode(true);
                var idx = compIndex++;
                novaLinha.querySelectorAll('input, select').forEach(function (campo) {
                    var nome = campo.name;
                    if (!nome) return;
                    campo.name = nome.replace(/\[0\]/, '[' + idx + ']');
                    campo.value = campo.type === 'checkbox' ? campo.value : '';
                });
                novaLinha.querySelector('[name$="[tipo]"]').value = 'adicional';
                desativarPreco(novaLinha, false);
                listaComplementos.appendChild(novaLinha);
            });
        }
    }

    // Copiar link do cardápio digital
    var botaoCardapioCopiar = document.querySelector('[data-copiar-cardapio]');
    if (botaoCardapioCopiar) {
        botaoCardapioCopiar.addEventListener('click', function () {
            navigator.clipboard?.writeText(botaoCardapioCopiar.dataset.copiarCardapio || '')
                .then(function () {
                    var original = botaoCardapioCopiar.textContent;
                    botaoCardapioCopiar.textContent = 'Copiado!';
                    setTimeout(function () { botaoCardapioCopiar.textContent = original; }, 2000);
                })
                .catch(function () {
                    // fallback: seleciona o input ao lado
                    var url = botaoCardapioCopiar.parentElement.querySelector('input');
                    if (url) { url.select(); }
                });
        });
    }
})();

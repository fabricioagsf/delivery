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
})();

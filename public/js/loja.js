/* ==========================================================================
   GOSTOSURAS — interações (AJAX puro, sem frameworks)
   ========================================================================== */
(function () {
    'use strict';

    var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    /* ---------------- utilidades ---------------- */

    function requisitar(url, metodo, dados, repetido) {
        return fetch(url, {
            method: metodo,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: dados ? JSON.stringify(dados) : undefined
        }).then(function (resposta) {
            if (resposta.status === 419 && !repetido) {
                // Token envelheceu: busca um novo e repete a mesma chamada
                return fetch(Rotas.csrf, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (t) {
                        CSRF = t.token;
                        return requisitar(url, metodo, dados, true);
                    });
            }

            return resposta.json().then(function (corpo) {
                if (!resposta.ok) {
                    // Sessão/token expirou: recarrega a página uma vez para
                    // pegar um token novo em vez de mostrar erro cru.
                    if (resposta.status === 419 && !sessionStorage.getItem('recarregou-419')) {
                        sessionStorage.setItem('recarregou-419', '1');
                        location.reload();
                        return new Promise(function () {}); // aguarda o reload
                    }

                    var erro = new Error(corpo.mensagem || Textos.erroInesperado);
                    erro.status = resposta.status;
                    erro.corpo = corpo;
                    throw erro;
                }

                sessionStorage.removeItem('recarregou-419');
                return corpo;
            });
        });
    }

    function formParaJson(form) {
        var dados = {};
        new FormData(form).forEach(function (valor, campo) {
            if (campo.endsWith('[]')) {
                (dados[campo.slice(0, -2)] = dados[campo.slice(0, -2)] || []).push(valor);
            } else {
                dados[campo] = valor;
            }
        });
        return dados;
    }

    var toastEl = document.createElement('div');
    toastEl.className = 'toast';
    document.body.appendChild(toastEl);
    var toastTimer;

    function toast(mensagem, tipo) {
        clearTimeout(toastTimer);
        toastEl.textContent = mensagem;
        toastEl.classList.add('visivel');
        toastEl.style.background = tipo === 'erro' ? '#b3261e' : '';
        toastTimer = setTimeout(function () { toastEl.classList.remove('visivel'); }, 2600);
    }

    function mostrarMensagemForm(form, texto, tipo) {
        var alvo = form.querySelector('.form-mensagem');
        if (!alvo) { toast(texto, tipo); return; }
        alvo.textContent = texto;
        alvo.className = 'form-mensagem ' + (tipo === 'erro' ? 'erro' : 'ok');
    }

    /* ---------------- carrinho: selo no topo ---------------- */
    function atualizarSelo(contagem) {
        var selo = document.getElementById('carrinho-badge');
        if (!selo) return;
        selo.textContent = contagem;
        selo.classList.toggle('oculto', contagem === 0);
    }

    /* adicionar à sacola na vitrine */
    document.querySelectorAll('.botao-adicionar').forEach(function (botao) {
        botao.addEventListener('click', function () {
            botao.disabled = true;
            requisitar(Rotas.carrinhoAdicionar, 'POST', {
                produto_id: Number(botao.dataset.produtoId)
            }).then(function (r) {
                atualizarSelo(r.contagem);
                toast(r.mensagem);
            }).catch(function (e) {
                // Gatilho de atualização: admin mexeu em valor/estoque agora
                if (e.corpo && e.corpo.atualizar_vitrine && typeof carregarResultados === 'function') {
                    carregarResultados(window.location.href, false);
                }
                toast(e.message, 'erro');
            }).finally(function () { botao.disabled = false; });
        });
    });

    /* página do carrinho */
    var listaCarrinho = document.getElementById('lista-carrinho');
    if (listaCarrinho) {
        listaCarrinho.addEventListener('click', function (evento) {
            var alvo = evento.target.closest('[data-acao]');
            if (!alvo) return;

            var linha = alvo.closest('.linha-carrinho');
            var produtoId = Number(linha.dataset.produtoId);
            var acao = alvo.dataset.acao;
            var valorEl = linha.querySelector('.contador__valor');

            if (acao === 'remover') {
                requisitar(Rotas.carrinhoRemover, 'POST', { produto_id: produtoId })
                    .then(function (r) {
                        linha.remove();
                        aposMudanca(r);
                        toast(r.mensagem);
                    }).catch(function (e) { toast(e.message, 'erro'); });
                return;
            }

            var quantidade = Number(valorEl.textContent) + (acao === 'aumentar' ? 1 : -1);
            requisitar(Rotas.carrinhoAtualizar, 'POST', {
                produto_id: produtoId,
                quantidade: Math.max(0, quantidade)
            }).then(function (r) {
                if (quantidade <= 0) { linha.remove(); }
                else { valorEl.textContent = quantidade; }
                aposMudanca(r);
            }).catch(function (e) { toast(e.message, 'erro'); });
        });

        function aposMudanca(r) {
            atualizarSelo(r.contagem);
            var resumoSubtotal = document.getElementById('resumo-subtotal');
            if (resumoSubtotal && r.subtotal !== undefined) resumoSubtotal.textContent = r.subtotal;

            document.querySelectorAll('.linha-carrinho').forEach(function (linha) {
                var id = Number(linha.dataset.produtoId);
                var btnMais = linha.querySelector('[data-acao="aumentar"]');
                if (btnMais) btnMais.disabled = false;
            });

            if (r.vazio) window.location.reload();
        }
    }

    /* ---------------- carrossel de banners ---------------- */
    var carrossel = document.getElementById('carrossel-banners');

    if (carrossel && carrossel.querySelectorAll('.carrossel__slide').length > 1) {
        var slides = carrossel.querySelectorAll('.carrossel__slide');
        var pontos = carrossel.querySelectorAll('.carrossel__ponto');
        var atual = 0;
        var timerBanner;

        function irPara(indice) {
            slides[atual].classList.remove('ativo');
            pontos[atual]?.classList.remove('ativo');
            atual = (indice + slides.length) % slides.length;
            slides[atual].classList.add('ativo');
            pontos[atual]?.classList.add('ativo');
        }

        function agendar() {
            clearInterval(timerBanner);
            timerBanner = setInterval(function () { irPara(atual + 1); }, 6000);
        }

        pontos.forEach(function (ponto) {
            ponto.addEventListener('click', function () {
                irPara(Number(ponto.dataset.indice));
                agendar();
            });
        });

        carrossel.addEventListener('mouseenter', function () { clearInterval(timerBanner); });
        carrossel.addEventListener('mouseleave', agendar);

        agendar();
    }

    /* ---------------- filtro de categoria sem recarregar ---------------- */
    var areaProdutos = document.getElementById('area-produtos');

    function carregarResultados(url, empilharHistorico) {
        areaProdutos.classList.add('carregando');

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (resposta) {
            return resposta.text();
        }).then(function (html) {
            areaProdutos.innerHTML = html;
            if (empilharHistorico) history.pushState({}, '', url);
            var destino = document.getElementById('produtos');
            if (destino) {
                window.scrollTo({ top: destino.offsetTop - 90, behavior: 'smooth' });
            }
        }).catch(function () {
            // fallback seguro: navegação tradicional
            window.location.href = url;
        }).finally(function () {
            areaProdutos.classList.remove('carregando');
        });
    }

    if (areaProdutos) {
        document.addEventListener('click', function (evento) {
            var chip = evento.target.closest('#area-produtos .chip');
            if (!chip) return;
            evento.preventDefault();
            carregarResultados(chip.href, true);
        });

        window.addEventListener('popstate', function () {
            carregarResultados(window.location.href, false);
        });

        // Gatilho de atualização de preço/estoque: o comprador nunca vê
        // valor velho — checa ao voltar o foco e periodicamente.
        var ultimaVersao = null;

        function checarVersao() {
            var ids = Array.prototype.map.call(
                areaProdutos.querySelectorAll('.botao-adicionar'),
                function (b) { return b.dataset.produtoId; }
            ).join(',');

            fetch(Rotas.versao + '?ids=' + ids, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (c) {
                    if (ultimaVersao === null) {
                        ultimaVersao = c.hash;
                        return;
                    }

                    if (c.hash !== ultimaVersao) {
                        ultimaVersao = c.hash;
                        carregarResultados(window.location.href, false);
                        toast(Textos.avisoAtualizacao);
                    }
                }).catch(function () {});
        }

        setInterval(checarVersao, 15000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) checarVersao();
        });
        checarVersao();
    }

    /* ---------------- drawer da conta ---------------- */
    var velo = document.getElementById('drawer-velo');
    var drawer = document.getElementById('drawer-conta');
    var logadoBox = document.getElementById('conta-logada');
    var visitanteBox = document.getElementById('conta-visitante');
    var portaBox = document.getElementById('conta-porta');
    var modoPorta = null;

    function mostrarPorta(modo) {
        if (!portaBox || (modo !== 'senha' && modo !== 'completar')) return;
        modoPorta = modo;

        visitanteBox?.classList.add('oculto');
        logadoBox?.classList.add('oculto');
        portaBox.classList.remove('oculto');

        var formSenha = document.getElementById('form-porta-senha');
        var formCompletar = document.getElementById('form-porta-completar');
        formSenha.hidden = modo !== 'senha';
        formCompletar.hidden = modo !== 'completar';

        var titulo = document.getElementById('porta-titulo');
        var nota = document.getElementById('porta-nota');
        titulo.textContent = modo === 'senha' ? Textos.portaSenhaTitulo : Textos.portaCompletarTitulo;
        nota.textContent = modo === 'senha' ? Textos.portaSenhaNota : Textos.portaCompletarNota;
    }

    function abrirDrawer() {
        velo?.classList.remove('oculto');
        drawer?.classList.remove('oculto');
        document.body.style.overflow = 'hidden';

        if (modoPorta) return;
        if (!visitanteBox.classList.contains('oculto')) return;

        carregarPainel();
    }

    function fecharDrawer() {
        velo?.classList.add('oculto');
        drawer?.classList.add('oculto');
        document.body.style.overflow = '';
    }

    document.getElementById('abrir-conta')?.addEventListener('click', abrirDrawer);
    document.getElementById('fechar-conta')?.addEventListener('click', fecharDrawer);
    velo?.addEventListener('click', fecharDrawer);

    /* abas entrar / criar conta */
    document.querySelectorAll('.aba[data-aba]').forEach(function (aba) {
        aba.addEventListener('click', function () {
            document.querySelectorAll('.aba[data-aba]').forEach(function (outra) {
                outra.classList.toggle('ativa', outra === aba);
            });
            document.querySelectorAll('.painel-form[data-form]').forEach(function (form) {
                var ativo = form.dataset.form === aba.dataset.aba;
                form.hidden = !ativo;
                form.classList.toggle('ativo', ativo);
            });
        });
    });

    /* sanfona (acordeão) das seções */
    document.querySelectorAll('.sanfona__cabeca').forEach(function (cabeca) {
        cabeca.addEventListener('click', function () {
            var item = cabeca.parentElement;
            var jaAberta = item.classList.contains('aberta');
            document.querySelectorAll('.sanfona__item').forEach(function (i) { i.classList.remove('aberta'); });
            if (!jaAberta) item.classList.add('aberta');
        });
    });

    /* ---------------- painel (JSON) ---------------- */
    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function preencherPainel(dados) {
        visitanteBox.classList.add('oculto');
        logadoBox.classList.remove('oculto');

        logadoBox.querySelector('.drawer__saudacao').textContent =
            Textos.saudacao.replace(':nome', dados.cliente.nome.split(' ')[0]);

        var fDados = logadoBox.querySelector('[data-form="dados"]');
        fDados.nome.value = dados.cliente.nome;
        fDados.telefone.value = dados.cliente.telefone;
        fDados.email.value = dados.cliente.email;

        var ulEnderecos = logadoBox.querySelector('[data-lista="enderecos"]');
        ulEnderecos.innerHTML = '';
        dados.enderecos.forEach(function (end) {
            var li = document.createElement('li');
            li.className = 'registro-item' + (end.principal ? ' registro-item--principal' : '');
            li.innerHTML =
                '<div><strong>' + escapar(end.rua + ', ' + end.numero) +
                (end.principal ? ' ★' : '') + '</strong><small>' +
                esc(end.bairro) + ' — ' + esc(end.cidade) + '</small></div>' +
                '<div class="acoes-registro">' +
                (end.principal ? '' : '<button class="mini-botao" data-funcao="principal" data-id="' + end.id + '">' + Textos.tornarPrincipal + '</button>') +
                '<button class="mini-botao mini-botao--perigo" data-funcao="remover-endereco" data-id="' + end.id + '">' + Textos.remover + '</button></div>';
            ulEnderecos.appendChild(li);
        });
        if (!dados.enderecos.length) {
            ulEnderecos.innerHTML = '<li><small>' + Textos.enderecoVazio + '</small></li>';
        }

        var ulCartoes = logadoBox.querySelector('[data-lista="cartoes"]');
        ulCartoes.innerHTML = '';
        dados.cartoes.forEach(function (cartao) {
            var li = document.createElement('li');
            li.className = 'registro-item';
            li.innerHTML =
                '<div><strong>' + esc(cartao.apelido) + '</strong><small>' +
                esc(cartao.bandeira) + ' •••• ' + esc(cartao.numero_final) +
                (cartao.validade ? ' · ' + esc(cartao.validade) : '') + '</small></div>' +
                '<div class="acoes-registro"><button class="mini-botao mini-botao--perigo" data-funcao="remover-cartao" data-id="' +
                cartao.id + '">' + Textos.remover + '</button></div>';
            ulCartoes.appendChild(li);
        });
        if (!dados.cartoes.length) {
            ulCartoes.innerHTML = '<li><small>' + Textos.cartaoVazio + '</small></li>';
        }

        var ulPedidos = logadoBox.querySelector('[data-lista="pedidos"]');
        ulPedidos.innerHTML = '';
        dados.pedidos.forEach(function (pedido) {
            var li = document.createElement('li');
            li.className = 'pedido-mini';
            li.innerHTML =
                '<div class="pedido-mini__topo"><span>' + esc(pedido.codigo) + '</span>' +
                '<span class="status-pilula status-pilula--' + esc(pedido.status) + '">' + esc(pedido.status_label) + '</span></div>' +
                '<small>' + esc(pedido.data) + ' · ' + esc(pedido.tipo_label) + ' · ' + esc(pedido.forma_label) + '</small>' +
                '<div><strong>' + esc(pedido.total) + '</strong></div>';
            ulPedidos.appendChild(li);
        });
        if (!dados.pedidos.length) {
            ulPedidos.innerHTML = '<li><small>' + Textos.pedidoVazio + '</small></li>';
        }

        function esc(t) { return escapar(t); }
    }

    function carregarPainel() {
        requisitar(Rotas.painel, 'GET')
            .then(preencherPainel)
            .catch(function () { fecharDrawer(); });
    }

    /* ações dentro das listas (remover / principal) */
    logadoBox.addEventListener('click', function (evento) {
        var botao = evento.target.closest('[data-funcao]');
        if (!botao) return;

        var funcao = botao.dataset.funcao;
        var id = botao.dataset.id;

        var promessa;
        if (funcao === 'remover-endereco') promessa = requisitar(Rotas.enderecoBase.replace('ID', id), 'DELETE');
        if (funcao === 'principal') promessa = requisitar(Rotas.enderecoPrincipal.replace('ID', id), 'PATCH');
        if (funcao === 'remover-cartao') promessa = requisitar(Rotas.cartaoBase.replace('ID', id), 'DELETE');
        if (!promessa) return;

        promessa.then(function (r) {
            toast(r.mensagem);
            carregarPainel();
        }).catch(function (e) { toast(e.message, 'erro'); });
    });

    /* ---------------- formulários AJAX ---------------- */
    var rotasFormulario = {
        login: Rotas.login,
        registro: Rotas.registro,
        dados: Rotas.dados,
        endereco: Rotas.enderecos,
        cartao: Rotas.cartoes,
        sair: Rotas.logout,
        'troca-senha': Rotas.senha,
        completar: Rotas.completar
    };

    function formularioPorNome(nome) {
        switch (nome) {
            case 'login': return document.getElementById('form-login');
            case 'registro': return document.getElementById('form-registro');
            case 'troca-senha': return document.getElementById('form-porta-senha');
            case 'completar': return document.getElementById('form-porta-completar');
            default: return logadoBox.querySelector('[data-form="' + nome + '"]');
        }
    }

    /* botão solto "Sair da conta" (não é form, submit nunca dispara) */
    document.querySelector('[data-form="sair"]')?.addEventListener('click', function () {
        requisitar(Rotas.logout, 'POST').then(function () {
            window.location.reload();
        }).catch(function (e) { toast(e.message, 'erro'); });
    });

    document.addEventListener('submit', function (evento) {
        var form = evento.target.closest('[data-form]');
        if (!form) return;
        evento.preventDefault();

        var nome = form.dataset.form;
        if (nome === 'sair') {
            requisitar(rotasFormulario.sair, 'POST').then(function () {
                window.location.reload();
            });
            return;
        }

        requisitar(rotasFormulario[nome], nome === 'dados' ? 'PUT' : 'POST', formParaJson(form))
            .then(function (r) {
                mostrarMensagemForm(form, r.mensagem, 'ok');
                if (nome === 'login' || nome === 'registro') {
                    setTimeout(function () { window.location.reload(); }, 600);
                }
                if (nome === 'troca-senha' || nome === 'completar') {
                    // Porta concluída: recarrega para sair do estado obrigatório
                    setTimeout(function () { window.location.reload(); }, 700);
                    return;
                }
                if (nome === 'dados' || nome === 'endereco' || nome === 'cartao') {
                    carregarPainel();
                }
            })
            .catch(function (e) {
                var mensagem = e.message;
                if (e.corpo && e.corpo.erros) {
                    mensagem = Object.values(e.corpo.erros).flat().join(' ');
                }
                mostrarMensagemForm(form, mensagem, 'erro');
            });
    });

    /* ---------------- estado vindo do servidor (porta / login social) ---------------- */
    if (typeof ContaEstado !== 'undefined') {
        if (ContaEstado.avisoSocial) {
            toast(ContaEstado.avisoSocial, 'erro');
        }
        if (ContaEstado.porta) {
            mostrarPorta(ContaEstado.porta);
            abrirDrawer();
        }
    }

    /* ---------------- checkout ---------------- */
    var formCheckout = document.getElementById('form-checkout');
    if (formCheckout) {
        var subtotal = parseFloat(formCheckout.dataset.subtotal) || 0;
        var taxa = parseFloat(formCheckout.dataset.taxa) || 0;

        function refletirEntrega() {
            var tipo = formCheckout.querySelector('[name="tipo_entrega"]:checked')?.value;
            var entrega = tipo === 'entrega';

            var campos = document.getElementById('campos-entrega');
            var notaRetirada = document.getElementById('nota-retirada');
            var salvos = document.getElementById('bloco-enderecos-salvos');
            var temSalvos = salvos && !salvos.hidden;

            campos.hidden = !entrega || usandoSalvo();
            if (notaRetirada) notaRetirada.hidden = entrega;
            if (salvos) salvos.hidden = !entrega || !salvos.dataset.temSalvos;

            var taxaAtual = entrega ? taxa : 0;
            document.getElementById('resumo-taxa').textContent = formatarMoeda(taxaAtual);
            document.getElementById('resumo-total').textContent = formatarMoeda(subtotal + taxaAtual);

            function usandoSalvo() {
                if (!temSalvos) return false;
                var marcado = formCheckout.querySelector('[name="endereco_salvo_id"]:checked');
                return marcado && marcado.value !== '';
            }
        }

        function formatarMoeda(valor) {
            return 'R$ ' + valor.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        formCheckout.querySelectorAll('[name="tipo_entrega"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (radio.checked) refletirEntrega();
            });
        });
        formCheckout.querySelectorAll('[name="endereco_salvo_id"]').forEach(function (radio) {
            radio.addEventListener('change', refletirEntrega);
        });

        formCheckout.querySelectorAll('[name="forma_pagamento"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.getElementById('campo-troco').hidden = radio.value !== 'dinheiro';
            });
        });

        // estado inicial
        var trocoInicial = formCheckout.querySelector('[name="forma_pagamento"]:checked');
        if (trocoInicial) document.getElementById('campo-troco').hidden = trocoInicial.value !== 'dinheiro';
        refletirEntrega();

        var salvosBloco = document.getElementById('bloco-enderecos-salvos');
        if (salvosBloco) salvosBloco.dataset.temSalvos = salvosBloco.querySelector('[data-endereco-salvo]') ? '1' : '';
        refletirEntrega();
    }
})();

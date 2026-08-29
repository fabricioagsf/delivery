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
            function montarLinhaVazia(idx) {
                var container = listaComplementos;
                var txtTipo = container.dataset.textoTipo || 'Tipo';
                var txtAdicional = container.dataset.textoAdicional || 'Adicional (pago)';
                var txtRemocao = container.dataset.textoRemocao || 'Remoção (grátis)';
                var txtNome = container.dataset.textoNome || 'Nome';
                var txtPreco = container.dataset.textoPreco || 'Preço (R$)';
                var txtExemplo = container.dataset.textoExemplo || '';
                var txtRemover = container.dataset.textoRemover || 'Remover';

                var div = document.createElement('div');
                div.className = 'linha-complemento';
                div.setAttribute('data-linha', '');
                div.innerHTML =
                    '<input type="hidden" name="complementos[' + idx + '][id]" value="">' +
                    '<input type="hidden" name="complementos[' + idx + '][ordem]" value="' + (idx * 10) + '">' +
                    '<label class="linha-complemento__campo">' +
                        '<span class="rotulo-mini">' + txtTipo + '</span>' +
                        '<select name="complementos[' + idx + '][tipo]" data-campo="tipo">' +
                            '<option value="adicional">' + txtAdicional + '</option>' +
                            '<option value="remocao">' + txtRemocao + '</option>' +
                        '</select>' +
                    '</label>' +
                    '<label class="linha-complemento__campo linha-complemento__campo--nome">' +
                        '<span class="rotulo-mini">' + txtNome + '</span>' +
                        '<input type="text" name="complementos[' + idx + '][nome]" maxlength="120" placeholder="' + txtExemplo + '">' +
                    '</label>' +
                    '<label class="linha-complemento__campo linha-complemento__campo--preco" data-campo-preco>' +
                        '<span class="rotulo-mini">' + txtPreco + '</span>' +
                        '<input type="number" name="complementos[' + idx + '][preco]" step="0.01" min="0">' +
                    '</label>' +
                    '<button type="button" class="botao-remover-linha" data-remover aria-label="' + txtRemover + '">&times;</button>';

                return div;
            }

            botaoAdicionar.addEventListener('click', function () {
                var modelo = listaComplementos.querySelector('.linha-complemento');
                var idx = compIndex++;
                var novaLinha;

                if (!modelo) {
                    novaLinha = montarLinhaVazia(idx);
                    aplicarRegraPreco(novaLinha);
                    listaComplementos.appendChild(novaLinha);
                    return;
                }

                novaLinha = modelo.cloneNode(true);
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

    /* ==========================================================================
       CONTROLE DE PEDIDOS DAS MESAS (tempo real via polling)
       ========================================================================== */
    var gradeMesas = document.getElementById('grade-mesas');
    if (gradeMesas) {
        var botaoTestarSom = document.getElementById('controle-testar-som');
        var toggleSom = document.getElementById('controle-som');
        var statusConexao = document.getElementById('controle-status-conexao');
        var popup = document.getElementById('popup-pedido');
        var popupFechar = document.getElementById('popup-fechar');
        var popupAbrir = document.getElementById('popup-abrir');
        var popupMesa = document.getElementById('popup-mesa');
        var popupCodigo = document.getElementById('popup-codigo');
        var popupCliente = document.getElementById('popup-cliente');
        var popupTotal = document.getElementById('popup-total');

        var versaoAtual = null;
        var estadoPorMesa = {}; // mesaId -> { estado, pedidos[], silenciado }
        var pedidosConhecidos = new Set();
        var audioContexto = null;
        var audioHabilitado = true;
        var timerPopup = null;

        // Modal da mesa
        var modalMesa = document.getElementById('modal-mesa');
        var modalMesaTitulo = document.getElementById('modal-mesa-titulo');
        var modalMesaSubtitulo = document.getElementById('modal-mesa-subtitulo');
        var modalMesaCorpo = document.getElementById('modal-mesa-corpo');
        var mesaModalAberta = null; // id da mesa aberta no modal
        var mesaModalEstado = null; // estado (novo/em_preparo/...) exibido no modal

        if (toggleSom) {
            audioHabilitado = toggleSom.checked;
            toggleSom.addEventListener('change', function () {
                audioHabilitado = toggleSom.checked;
            });
        }

        // Web Audio API gera o "blip" sem precisar de arquivo externo
        function garantirContexto() {
            if (!audioContexto) {
                try { audioContexto = new (window.AudioContext || window.webkitAudioContext)(); }
                catch (e) { audioContexto = null; }
            }
            return audioContexto;
        }

        function tocarAlerta() {
            if (!audioHabilitado) return;
            var ctx = garantirContexto();
            if (!ctx) return;
            if (ctx.state === 'suspended') ctx.resume();

            // Dois tons curtos — fácil de reconhecer
            var agora = ctx.currentTime;
            [{ f: 660, t: agora, d: 0.15 }, { f: 880, t: agora + 0.18, d: 0.22 }].forEach(function (nota) {
                var osc = ctx.createOscillator();
                var ganho = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = nota.f;
                ganho.gain.setValueAtTime(0, nota.t);
                ganho.gain.linearRampToValueAtTime(0.35, nota.t + 0.01);
                ganho.gain.exponentialRampToValueAtTime(0.001, nota.t + nota.d);
                osc.connect(ganho).connect(ctx.destination);
                osc.start(nota.t);
                osc.stop(nota.t + nota.d);
            });
        }

        if (botaoTestarSom) {
            botaoTestarSom.addEventListener('click', function () {
                audioHabilitado = true;
                if (toggleSom && !toggleSom.checked) {
                    toggleSom.checked = true;
                }
                tocarAlerta();
            });
        }

        function formatarMoeda(valor) {
            return 'R$ ' + Number(valor || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function textoEstado(estado) {
            var t = (window.Textos && window.Textos.mesaControle) || {};
            return t[estado] || estado;
        }

        function mostrarPopup(mesaNome, pedido) {
            if (!popup) return;
            if (popupMesa) popupMesa.textContent = mesaNome;
            if (popupCodigo) popupCodigo.textContent = pedido.codigo || ('#' + pedido.id);
            if (popupCliente) popupCliente.textContent = pedido.cliente || '—';
            if (popupTotal) popupTotal.textContent = formatarMoeda(pedido.total);
            if (popupAbrir) popupAbrir.href = '/admin/pedidos/' + pedido.id;

            popup.hidden = false;
            clearTimeout(timerPopup);
            timerPopup = setTimeout(function () { popup.hidden = true; }, 12000);
        }

        if (popupFechar) {
            popupFechar.addEventListener('click', function () {
                popup.hidden = true;
                clearTimeout(timerPopup);
            });
        }

        function fecharModalMesa() {
            if (!modalMesa) return;
            modalMesa.hidden = true;
            mesaModalAberta = null;
            mesaModalEstado = null;
        }

        function abrirModalMesa(mesaId) {
            buscaDetalheMesa(mesaId);
        }

        function carregarDetalheMesa(mesaId, payload) {
            if (!payload || !payload.mesa) return;
            mesaModalAberta = mesaId;

            var dados = payload.mesa;
            var textos = (window.Textos && window.Textos.mesaControle) || {};
            mesaModalEstado = dados.estado;

            if (modalMesaTitulo) modalMesaTitulo.textContent = dados.nome;
            if (modalMesaSubtitulo) {
                var qtd = (dados.pedidos || []).length;
                modalMesaSubtitulo.textContent = qtd > 0
                    ? (qtd + ' ' + (textos.modal_pedidos_abertos || 'pedidos em aberto') + ' · ' + dados.capacidade + ' ' + (textos.pessoa_plural || 'pessoas'))
                    : (dados.capacidade + ' ' + (textos.pessoa_plural || 'pessoas'));
            }

            if (!modalMesaCorpo) return;
            modalMesaCorpo.innerHTML = '';

            if (!dados.pedidos || dados.pedidos.length === 0) {
                var vazio = document.createElement('p');
                vazio.className = 'modal-mesa__vazio';
                vazio.textContent = textos.modal_vazio || 'Esta mesa está livre — nenhum pedido em aberto.';
                modalMesaCorpo.appendChild(vazio);
                if (modalMesa) modalMesa.hidden = false;
                return;
            }

            dados.pedidos.forEach(function (p) {
                modalMesaCorpo.appendChild(criarBlocoPedido(dados.nome, p, textos));
            });

            var totalMesa = document.createElement('p');
            totalMesa.className = 'cartao-mesa__total modal-mesa__total-mesa';
            totalMesa.textContent = (textos.modal_total_mesa || 'Total da mesa') + ': ';
            var valorTotal = document.createElement('strong');
            valorTotal.textContent = formatarMoeda(dados.total);
            totalMesa.appendChild(valorTotal);
            modalMesaCorpo.appendChild(totalMesa);

            if (modalMesa) modalMesa.hidden = false;
        }

        function criarBlocoPedido(mesaNome, p, textos) {
            var bloco = document.createElement('section');
            bloco.className = 'modal-mesa__pedido';
            bloco.dataset.status = String(p.status).replace(/_/g, '-');

            var cabecalho = document.createElement('div');
            cabecalho.className = 'modal-mesa__cabecalho-pedido';
            var codigo = document.createElement('span');
            codigo.className = 'modal-mesa__codigo';
            codigo.textContent = '#' + (p.codigo || p.id);
            cabecalho.appendChild(codigo);

            var detalhes = document.createElement('span');
            detalhes.className = 'modal-mesa__detalhe-pedido';
            if (p.cliente) detalhes.textContent += p.cliente + ' · ';
            if (p.pagamento) detalhes.textContent += (textos.modal_pagamento || 'Pagamento') + ': ' + p.pagamento + ' · ';
            if (p.quando) detalhes.textContent += (textos.modal_horario || 'Hora') + ': ' + p.quando;
            cabecalho.appendChild(detalhes);

            var itens = document.createElement('ul');
            itens.className = 'modal-mesa__itens';
            (p.itens || []).forEach(function (i) {
                var li = document.createElement('li');
                li.className = 'modal-mesa__item';

                var nome = document.createElement('span');
                nome.className = 'modal-mesa__item-nome';
                nome.textContent = (i.quantidade > 1 ? (i.quantidade + '× ') : '') + i.nome;
                if (i.complementos && i.complementos.length) {
                    var comps = document.createElement('small');
                    comps.className = 'modal-mesa__item-complementos';
                    comps.textContent = '+ ' + i.complementos.join(', ');
                    nome.appendChild(comps);
                }
                li.appendChild(nome);

                var valor = document.createElement('span');
                valor.className = 'modal-mesa__item-valor';
                valor.textContent = formatarMoeda(i.subtotal);
                li.appendChild(valor);

                itens.appendChild(li);
            });
            bloco.appendChild(cabecalho);
            bloco.appendChild(itens);

            if (p.observacoes) {
                var obs = document.createElement('p');
                obs.className = 'modal-mesa__obs';
                obs.textContent = (textos.modal_observacoes || 'Observações') + ': ' + p.observacoes;
                bloco.appendChild(obs);
            }

            var rodape = document.createElement('div');
            rodape.className = 'modal-mesa__rodape-pedido';

            var total = document.createElement('span');
            total.className = 'modal-mesa__total-pedido';
            total.textContent = 'Total: ' + formatarMoeda(p.total);
            rodape.appendChild(total);

            var link = document.createElement('a');
            link.className = 'modal-mesa__acoes-botao modal-mesa__acoes-botao--chefe';
            link.href = '/admin/pedidos/' + p.id;
            link.textContent = textos.modal_abrir_pedido || 'Abrir pedido';
            rodape.appendChild(link);

            bloco.appendChild(rodape);
            return bloco;
        }

        function buscaDetalheMesa(mesaId) {
            if (!modalMesaCorpo) return;
            modalMesaCorpo.innerHTML = '';
            var textos = (window.Textos && window.Textos.mesaControle) || {};
            var carregando = document.createElement('p');
            carregando.className = 'modal-mesa__carregando';
            carregando.textContent = textos.modal_carregando || 'Carregando pedidos da mesa...';
            modalMesaCorpo.appendChild(carregando);
            if (modalMesa) modalMesa.hidden = false;

            if (!window.Rotas || !window.Rotas.mesasControleDetalhe) {
                carregando.textContent = 'Rota de detalhe não configurada.';
                return;
            }

            fetch(window.Rotas.mesasControleDetalhe.replace('__ID__', mesaId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) {
                    if (!r.ok) throw new Error('http ' + r.status);
                    return r.json();
                })
                .then(function (payload) { carregarDetalheMesa(mesaId, payload); })
                .catch(function () {
                    modalMesaCorpo.innerHTML = '';
                    var erro = document.createElement('p');
                    erro.className = 'modal-mesa__vazio';
                    erro.textContent = 'Erro ao carregar os pedidos da mesa.';
                    modalMesaCorpo.appendChild(erro);
                });
        }

        gradeMesas.addEventListener('click', function (evento) {
            var cartao = evento.target.closest('[data-mesa-id]');
            if (!cartao) return;
            // Clique em link interno (ex.: pedido) age normalmente
            if (evento.target.closest('a')) return;
            abrirModalMesa(cartao.dataset.mesaId);
        });

        gradeMesas.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter' || evento.key === ' ') {
                var cartao = evento.target.closest('[data-mesa-id]');
                if (!cartao) return;
                evento.preventDefault();
                abrirModalMesa(cartao.dataset.mesaId);
            }
        });

        modalMesa.querySelectorAll('[data-fechar-modal-mesa]').forEach(function (el) {
            el.addEventListener('click', fecharModalMesa);
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && modalMesa && !modalMesa.hidden) {
                fecharModalMesa();
            }
        });

        function montarCartaoMesa(mesaId, dados) {
            var cartao = gradeMesas.querySelector('[data-mesa-id="' + mesaId + '"]');
            if (!cartao) return;

            cartao.dataset.estado = dados.estado;
            cartao.classList.remove(
                'cartao-mesa--livre', 'cartao-mesa--novo', 'cartao-mesa--em-preparo', 'cartao-mesa--em-entrega'
            );
            cartao.classList.add('cartao-mesa--' + String(dados.estado).replace(/_/g, '-'));

            if (dados.estado === 'novo') {
                cartao.classList.remove('cartao-mesa--silenciado');
            } else {
                cartao.classList.add('cartao-mesa--silenciado');
            }

            var corpo = cartao.querySelector('.cartao-mesa__corpo');
            if (!corpo) return;
            corpo.innerHTML = '';

            if (!dados.pedidos || dados.pedidos.length === 0) {
                var span = document.createElement('span');
                span.className = 'cartao-mesa__estado-texto';
                span.textContent = textoEstado('livre');
                corpo.appendChild(span);
                return;
            }

            var lista = document.createElement('ul');
            lista.className = 'cartao-mesa__lista-pedidos';
            dados.pedidos.forEach(function (p) {
                var li = document.createElement('li');
                li.className = 'cartao-mesa__pedido';
                var link = document.createElement('a');
                link.href = '/admin/pedidos/' + p.id;
                link.textContent = '#' + (p.codigo || p.id);
                var status = document.createElement('span');
                status.textContent = textoEstado(p.status);
                li.appendChild(link);
                li.appendChild(status);
                lista.appendChild(li);
            });
            corpo.appendChild(lista);

            if (dados.total > 0) {
                var total = document.createElement('p');
                total.className = 'cartao-mesa__total';
                var lbl = document.createElement('span');
                lbl.textContent = 'Total';
                var val = document.createElement('strong');
                val.textContent = formatarMoeda(dados.total);
                total.appendChild(lbl);
                total.appendChild(val);
                corpo.appendChild(total);
            }
        }

        function aplicarEstado(payload) {
            if (payload.sem_mudancas) {
                versaoAtual = payload.versao;
                return;
            }
            versaoAtual = payload.versao;

            (payload.mesas || []).forEach(function (mesa) {
                var anterior = estadoPorMesa[mesa.id] || { pedidos: [], estado: 'livre' };

                // Detectar pedidos NOVOS (status 'novo' que não conhecíamos)
                if (mesa.estado === 'novo' && anterior.estado !== 'novo' && audioHabilitado) {
                    var primeiro = (mesa.pedidos || []).find(function (p) { return p.status === 'novo'; });
                    if (primeiro) {
                        tocarAlerta();
                        mostrarPopup(mesa.nome, primeiro);
                    }
                } else if (mesa.estado === 'novo' && anterior.estado === 'novo') {
                    // Continua novo: alerta já tocou, mas verifica se há outro pedido novo
                    var novosAgora = (mesa.pedidos || []).filter(function (p) {
                        return p.status === 'novo' && !pedidosConhecidos.has(p.id);
                    });
                    if (novosAgora.length && audioHabilitado) {
                        tocarAlerta();
                        mostrarPopup(mesa.nome, novosAgora[0]);
                    }
                }

                estadoPorMesa[mesa.id] = mesa;
                (mesa.pedidos || []).forEach(function (p) { pedidosConhecidos.add(p.id); });

                montarCartaoMesa(mesa.id, mesa);

                // Se a mesa aberta no modal mudar de estado, recarrega o detalhe
                if (mesaModalAberta !== null && parseInt(mesaModalAberta, 10) === parseInt(mesa.id, 10) && mesa.estado !== mesaModalEstado) {
                    buscaDetalheMesa(mesa.id);
                }
            });
        }

        function buscarEstado() {
            if (!window.Rotas || !window.Rotas.mesasControleEstado) return;
            if (statusConexao) statusConexao.dataset.estado = 'carregando';

            var url = window.Rotas.mesasControleEstado + (versaoAtual ? '?ultima_versao=' + versaoAtual : '');
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) {
                    if (!r.ok) throw new Error('http ' + r.status);
                    return r.json();
                })
                .then(aplicarEstado)
                .then(function () {
                    if (statusConexao) statusConexao.dataset.estado = 'ok';
                })
                .catch(function () {
                    if (statusConexao) statusConexao.dataset.estado = 'erro';
                });
        }

        // Pré-popular pedidosConhecidos com o que já veio no primeiro estado
        // (sem alerta — apenas para o caso de já existirem pedidos antigos)
        setTimeout(buscarEstado, 600);
        setInterval(buscarEstado, 7000);
    }
})();

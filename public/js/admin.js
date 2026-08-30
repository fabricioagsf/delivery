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

    function formatarMoeda(valor) {
        return 'R$ ' + Number(valor || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
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
        var pedidosVistos = carregarVistos();
        var audioContexto = null;
        var audioHabilitado = true;
        var timerPopup = null;

        function chaveVisto(mesaId, pedidoId) {
            return mesaId + ':' + pedidoId;
        }

        function carregarVistos() {
            try {
                var raw = window.localStorage.getItem('mesasControle.vistos') || '{}';
                var obj = JSON.parse(raw);
                return new Set(Object.keys(obj));
            } catch (e) { return new Set(); }
        }

        function marcarVisto(mesaId, pedidoId) {
            pedidosVistos.add(chaveVisto(mesaId, pedidoId));
            try {
                var obj = {};
                pedidosVistos.forEach(function (k) { obj[k] = 1; });
                window.localStorage.setItem('mesasControle.vistos', JSON.stringify(obj));
            } catch (e) { /* sem storage — ignora */ }
        }

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
                marcarVisto(dados.id, p.id);
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

            var statusPilulaMesa = document.createElement('span');
            statusPilulaMesa.className = 'modal-mesa__status-pilula modal-mesa__status-pilula--' + String(p.status || '').replace(/_/g, '-');
            statusPilulaMesa.textContent = textoEstado(p.status);
            cabecalho.appendChild(statusPilulaMesa);

            var itens = document.createElement('ul');
            itens.className = 'modal-mesa__itens';
            (p.itens || []).forEach(function (i) {
                var li = document.createElement('li');
                li.className = 'modal-mesa__item';
                if (i.quantidade > 1) {
                    var qtd = document.createElement('span');
                    qtd.className = 'modal-mesa__item-qtd';
                    qtd.textContent = i.quantidade + '×';
                    li.appendChild(qtd);
                }

                var nome = document.createElement('span');
                nome.className = 'modal-mesa__item-nome';
                nome.textContent = i.nome;
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

            var acoes = document.createElement('div');
            acoes.className = 'modal-mesa__acoes';

            if (['novo', 'em_preparo', 'em_entrega'].indexOf(p.status) !== -1 && !p.entregue_mesa_em) {
                var btnEntregue = document.createElement('button');
                btnEntregue.type = 'button';
                btnEntregue.className = 'modal-mesa__acoes-botao modal-mesa__acoes-botao--entregue-mesa';
                btnEntregue.textContent = textos.modal_entregar_mesa || 'Entregue na mesa';
                btnEntregue.addEventListener('click', function () {
                    if (! window.confirm(textos.modal_confirmar_entrega || 'Confirmar marcação de entregue na mesa?')) {
                        return;
                    }
                    btnEntregue.disabled = true;
                    btnEntregue.classList.add('is-loading');
                    fetch((window.Rotas.pedidoEntregueMesa || '').replace('ID', p.id), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': window.Rotas.csrfToken || ''
                        }
                    })
                        .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
                        .then(function () {
                            btnEntregue.classList.remove('is-loading');
                            btnEntregue.classList.add('is-done');
                            btnEntregue.textContent = (textos.modal_entregue_mesa_horario || 'Entregue às') + ' ' + (new Date()).toLocaleTimeString().slice(0, 5);
                        })
                        .catch(function () {
                            btnEntregue.disabled = false;
                            btnEntregue.classList.remove('is-loading');
                            alert(textos.modal_entregar_erro || 'Não foi possível marcar como entregue.');
                        });
                });
                acoes.appendChild(btnEntregue);
            } else if (p.entregue_mesa_em) {
                var selo = document.createElement('span');
                selo.className = 'modal-mesa__selo-entregue';
                selo.textContent = (textos.modal_entregue_mesa_horario || 'Entregue às') + ' ' + p.entregue_mesa_em;
                acoes.appendChild(selo);
            }

            var link = document.createElement('a');
            link.className = 'modal-mesa__acoes-botao modal-mesa__acoes-botao--chefe';
            link.href = '/admin/pedidos/' + p.id;
            link.textContent = textos.modal_abrir_pedido || 'Abrir pedido';
            acoes.appendChild(link);

            rodape.appendChild(acoes);

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

                // Detectar pedidos NOVOS (status 'novo' que ainda não visualizamos)
                if (mesa.estado === 'novo' && anterior.estado !== 'novo' && audioHabilitado) {
                    var primeiro = (mesa.pedidos || []).find(function (p) {
                        return p.status === 'novo' && !pedidosVistos.has(chaveVisto(mesa.id, p.id));
                    });
                    if (primeiro) {
                        tocarAlerta();
                        mostrarPopup(mesa.nome, primeiro);
                    }
                } else if (mesa.estado === 'novo' && anterior.estado === 'novo') {
                    // Continua novo: alerta já tocou, mas verifica se há outro pedido novo
                    var novosAgora = (mesa.pedidos || []).filter(function (p) {
                        return p.status === 'novo' && !pedidosVistos.has(chaveVisto(mesa.id, p.id));
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

    /* -------- caixa (contas das mesas) -------- */
    var gradeCaixa = document.getElementById('grade-caixa');
    if (gradeCaixa) {
        var textosCaixa = {};

        function textosCaixaAtuais() {
            return (window.Textos && window.Textos.caixa) || {};
        }

        var modalCaixa = document.getElementById('modal-caixa');
        var modalCaixaTitulo = document.getElementById('modal-caixa-titulo');
        var modalCaixaSubtitulo = document.getElementById('modal-caixa-subtitulo');
        var modalCaixaCorpo = document.getElementById('modal-caixa-corpo');
        var mesaCaixaAberta = null;

        function fecharModalCaixa() {
            if (!modalCaixa) return;
            modalCaixa.hidden = true;
            mesaCaixaAberta = null;
        }

        function montarCartaoCaixa(mesaId, dados) {
            var cartao = gradeCaixa.querySelector('[data-mesa-id="' + mesaId + '"]');
            if (!cartao) return;

            var estado = dados.estado === 'com_conta' ? 'com-conta' : 'livre';
            cartao.dataset.estado = dados.estado;
            cartao.classList.remove('cartao-mesa--livre', 'cartao-mesa--com-conta');
            cartao.classList.add('cartao-mesa--' + estado);

            var corpo = cartao.querySelector('.cartao-mesa__corpo');
            if (!corpo) return;
            corpo.innerHTML = '';

            if (!dados || !dados.qtd_pedidos) {
                var span = document.createElement('span');
                span.className = 'cartao-mesa__estado-texto';
                span.textContent = textosCaixa.livre || 'Livre';
                corpo.appendChild(span);
                return;
            }

            var txt = document.createElement('span');
            txt.className = 'cartao-mesa__estado-texto';
            txt.textContent = textosCaixa.com_conta;
            corpo.appendChild(txt);

            var total = document.createElement('p');
            total.className = 'cartao-mesa__total';
            var lbl = document.createElement('span');
            lbl.textContent = textosCaixa.modal_total_mesa || 'Total da conta';
            var val = document.createElement('strong');
            val.textContent = formatarMoeda(dados.total);
            total.appendChild(lbl);
            total.appendChild(val);
            corpo.appendChild(total);
        }

        function criarBlocoConta(p) {
            var bloco = document.createElement('section');
            bloco.className = 'modal-mesa__pedido caixa-pedido';
            bloco.dataset.status = String(p.status).replace(/_/g, '-');

            var cabecalho = document.createElement('div');
            cabecalho.className = 'modal-mesa__cabecalho-pedido';
            var codigo = document.createElement('span');
            codigo.className = 'modal-mesa__codigo';
            codigo.textContent = '#' + (p.codigo || p.id);
            cabecalho.appendChild(codigo);

            var detalhes = document.createElement('span');
            detalhes.className = 'modal-mesa__detalhe-pedido';
            if (p.cliente) detalhes.textContent += (p.cliente) + ' · ';
            if (p.quando) detalhes.textContent += (textosCaixa.modal_horario || 'Hora') + ': ' + p.quando;
            cabecalho.appendChild(detalhes);

            var statusPilula = document.createElement('span');
            statusPilula.className = 'modal-mesa__status-pilula modal-mesa__status-pilula--' + String(p.status || '').replace(/_/g, '-');
            statusPilula.textContent = textosCaixa['status_' + (p.status || '')] || p.status || '';
            cabecalho.appendChild(statusPilula);
            bloco.appendChild(cabecalho);

            var itens = document.createElement('ul');
            itens.className = 'modal-mesa__itens';
            (p.itens || []).forEach(function (i) {
                var li = document.createElement('li');
                li.className = 'modal-mesa__item';
                if (i.quantidade > 1) {
                    var qtd = document.createElement('span');
                    qtd.className = 'modal-mesa__item-qtd';
                    qtd.textContent = i.quantidade + '×';
                    li.appendChild(qtd);
                }
                var nome = document.createElement('span');
                nome.className = 'modal-mesa__item-nome';
                nome.textContent = i.nome;
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
            if (!p.itens || p.itens.length === 0) {
                var semItens = document.createElement('li');
                semItens.className = 'modal-mesa__item-nome';
                semItens.textContent = '—';
                itens.appendChild(semItens);
            }
            bloco.appendChild(itens);

            if (p.observacoes) {
                var obs = document.createElement('p');
                obs.className = 'modal-mesa__obs';
                obs.textContent = (textosCaixa.modal_observacoes || 'Observações') + ': ' + p.observacoes;
                bloco.appendChild(obs);
            }

            var rodape = document.createElement('div');
            rodape.className = 'modal-mesa__rodape-pedido';
            var total = document.createElement('span');
            total.className = 'modal-mesa__total-pedido';
            total.textContent = 'Total: ' + formatarMoeda(p.total);
            rodape.appendChild(total);
            bloco.appendChild(rodape);

            return bloco;
        }

        function montarFormaPagamento(dados, pixDados) {
            textosCaixa = textosCaixaAtuais();
            var form = document.createElement('form');
            form.className = 'caixa-form';
            form.dataset.contas = (dados.pedidos || []).length;
            pixDados = pixDados || {};

            var totalConta = Number(dados.total || 0);

            function urlQr(payload) {
                return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=' + encodeURIComponent(payload);
            }

            function blocoCopia(payload) {
                var grupo = document.createElement('div');
                grupo.className = 'caixa-form__pix-copia';

                var rotulo = document.createElement('span');
                rotulo.textContent = textosCaixa.pix_copia_e_cola || 'Pix copia e cola';
                grupo.appendChild(rotulo);

                var linha = document.createElement('div');
                linha.className = 'caixa-form__pix-linha';
                var input = document.createElement('input');
                input.type = 'text';
                input.readOnly = true;
                input.value = payload;
                input.className = 'caixa-form__pix-input';
                linha.appendChild(input);

                var copiar = document.createElement('button');
                copiar.type = 'button';
                copiar.className = 'botao botao--pequeno';
                copiar.textContent = textosCaixa.pix_copiar || 'Copiar código Pix';
                copiar.addEventListener('click', function () {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(payload).catch(function () { });
                    } else {
                        input.focus();
                        input.select();
                        document.execCommand('copy');
                    }
                });
                linha.appendChild(copiar);
                grupo.appendChild(linha);

                return grupo;
            }

            function blocoPixValor() {
                var val = document.createElement('p');
                val.className = 'caixa-form__pix-valor';
                val.textContent = (textosCaixa.pix_valor || 'Valor: :valor').replace(':valor', formatarMoeda(totalConta));
                return val;
            }

            var areaPix = document.createElement('div');
            areaPix.className = 'caixa-form__pix-area';

            function limparAreaPix() {
                areaPix.innerHTML = '';
            }

            function mensagemAreaPix(texto) {
                limparAreaPix();
                var p = document.createElement('p');
                p.className = 'caixa-form__pix-mensagem';
                p.textContent = texto;
                areaPix.appendChild(p);
            }

            function renderizarPixChave() {
                var payload = pixDados.chave_payload || null;
                if (!payload) {
                    mensagemAreaPix(textosCaixa.pix_sem_chave || 'QR indisponível — cadastre a Chave Pix da loja em Configurações e a cidade da empresa.');
                    return;
                }
                limparAreaPix();
                var img = document.createElement('img');
                img.className = 'caixa-form__pix-qr';
                img.src = urlQr(payload);
                img.alt = textosCaixa.pix_qr_alt || 'QR code Pix da conta da mesa';
                areaPix.appendChild(img);
                areaPix.appendChild(blocoPixValor());
                areaPix.appendChild(blocoCopia(payload));
            }

            function renderizarPixEfi() {
                if (!pixDados.efi_disponivel) {
                    mensagemAreaPix(textosCaixa.pix_sem_efi || 'Pix automático (Efí) não ativado.');
                    return;
                }
                limparAreaPix();
                var carregando = document.createElement('p');
                carregando.className = 'caixa-form__pix-mensagem';
                carregando.textContent = textosCaixa.pix_carregando || 'Gerando QR da operadora...';
                areaPix.appendChild(carregando);

                fetch(window.Rotas.caixaPixEfi.replace('__ID__', mesaCaixaAberta), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, json: j }; }); })
                    .then(function (resp) {
                        if (!resp.ok || !resp.json.copia_e_cola) {
                            mensagemAreaPix((resp.json && resp.json.mensagem) || textosCaixa.pix_erro_gerar || 'Não foi possível gerar o QR da operadora.');
                            return;
                        }
                        limparAreaPix();
                        var img = document.createElement('img');
                        img.className = 'caixa-form__pix-qr';
                        img.src = urlQr(resp.json.copia_e_cola);
                        img.alt = textosCaixa.pix_qr_alt || 'QR code Pix da conta da mesa';
                        areaPix.appendChild(img);
                        areaPix.appendChild(blocoPixValor());
                        areaPix.appendChild(blocoCopia(resp.json.copia_e_cola));
                    })
                    .catch(function () {
                        mensagemAreaPix(textosCaixa.pix_erro_gerar || 'Não foi possível gerar o QR da operadora.');
                    });
            }

            function taxaAtual() {
                if (form.elements.pix_opcao && form.elements.pix_opcao.value === 'efi' && pixDados.efi_taxa !== null && pixDados.efi_taxa !== undefined) {
                    return (textosCaixa.pix_taxa || 'Taxa da operadora (Efí): :taxa').replace(':taxa', String(pixDados.efi_taxa).replace('.', ',') + '%');
                }
                return textosCaixa.pix_sem_taxa || 'Sem taxa de operadora';
            }

            var campoSelect = document.createElement('label');
            campoSelect.className = 'caixa-form__campo';
            var rotulo = document.createElement('span');
            rotulo.textContent = textosCaixa.campo_forma_pagamento || 'Forma de pagamento';
            campoSelect.appendChild(rotulo);
            var select = document.createElement('select');
            select.name = 'forma_pagamento';
            select.required = true;
            Object.keys(textosCaixa.formas || {}).forEach(function (forma) {
                var op = document.createElement('option');
                op.value = forma;
                op.textContent = textosCaixa.formas[forma];
                select.appendChild(op);
            });
            campoSelect.appendChild(select);
            form.appendChild(campoSelect);

            var campoTroco = document.createElement('label');
            campoTroco.className = 'caixa-form__campo caixa-form__campo--troco';
            campoTroco.hidden = true;
            var rotuloTroco = document.createElement('span');
            rotuloTroco.textContent = textosCaixa.campo_troco_para || 'Valor recebido (R$)';
            campoTroco.appendChild(rotuloTroco);
            var inputTroco = document.createElement('input');
            inputTroco.type = 'number';
            inputTroco.name = 'troco_para';
            inputTroco.min = '0';
            inputTroco.step = '0.01';
            inputTroco.inputMode = 'decimal';
            inputTroco.placeholder = '0,00';
            campoTroco.appendChild(inputTroco);
            var trocoInfo = document.createElement('small');
            trocoInfo.className = 'caixa-form__troco-info';
            campoTroco.appendChild(trocoInfo);
            campoTroco.hidden = select.value !== 'dinheiro';
            form.appendChild(campoTroco);

            function informarTroco() {
                var recebido = Number(String(inputTroco.value || '').replace(',', '.'));
                if (select.value !== 'dinheiro') {
                    trocoInfo.textContent = '';
                    trocoInfo.classList.remove('caixa-form__troco-info--erro');
                    return;
                }
                var recebido = Number(String(inputTroco.value || '').replace(',', '.'));
                if (! recebido || isNaN(recebido) || recebido <= 0) {
                    trocoInfo.textContent = '';
                    trocoInfo.classList.remove('caixa-form__troco-info--erro');
                    return;
                }
                var troco = recebido - totalConta;
                if (troco < 0) {
                    var falta = Math.abs(troco);
                    trocoInfo.textContent = (textosCaixa.erro_troco_menor || 'O valor recebido é menor que o total da conta') + ' (' + formatarMoeda(falta) + ')';
                    trocoInfo.classList.add('caixa-form__troco-info--erro');
                    return;
                }
                trocoInfo.textContent = (textosCaixa.campo_troco || 'Troco') + ': ' + formatarMoeda(troco);
                trocoInfo.classList.remove('caixa-form__troco-info--erro');
            }

            select.addEventListener('change', function () {
                var mostrar = select.value === 'dinheiro';
                campoTroco.hidden = !mostrar;
                if (!mostrar) inputTroco.value = '';
                trocoInfo.textContent = '';
                if (mostrar) inputTroco.focus();

                pixBloco.hidden = select.value !== 'pix';
                if (select.value === 'pix') {
                    var opcao = form.elements.pix_opcao && form.elements.pix_opcao.value;
                    if (opcao === 'efi') renderizarPixEfi();
                    else renderizarPixChave();
                }
            });

            inputTroco.addEventListener('input', informarTroco);

            var pixBloco = document.createElement('div');
            pixBloco.className = 'caixa-form__pix';
            pixBloco.hidden = true;

            var opcaoChave = document.createElement('label');
            opcaoChave.className = 'caixa-form__pix-opcao';
            var radioChave = document.createElement('input');
            radioChave.type = 'radio';
            radioChave.name = 'pix_opcao';
            radioChave.value = 'chave';
            radioChave.checked = true;
            opcaoChave.appendChild(radioChave);
            opcaoChave.appendChild(document.createTextNode(textosCaixa.pix_opcao_chave || 'QR code por chave registrada'));

            var opcaoEfi = document.createElement('label');
            opcaoEfi.className = 'caixa-form__pix-opcao';
            var radioEfi = document.createElement('input');
            radioEfi.type = 'radio';
            radioEfi.name = 'pix_opcao';
            radioEfi.value = 'efi';
            opcaoEfi.appendChild(radioEfi);
            opcaoEfi.appendChild(document.createTextNode(textosCaixa.pix_opcao_efi || 'QR code Pix automático (Efí)'));

            var taxas = document.createElement('p');
            taxas.className = 'caixa-form__pix-taxa';
            taxas.textContent = taxaAtual();

            function atualizarPix() {
                var opcao = form.elements.pix_opcao && form.elements.pix_opcao.value;
                taxas.textContent = taxaAtual();
                if (opcao === 'efi') renderizarPixEfi();
                else renderizarPixChave();
            }

            radioChave.addEventListener('change', atualizarPix);
            radioEfi.addEventListener('change', atualizarPix);

            pixBloco.appendChild(opcaoChave);
            pixBloco.appendChild(opcaoEfi);
            pixBloco.appendChild(taxas);
            pixBloco.appendChild(areaPix);
            form.appendChild(pixBloco);

            var btn = document.createElement('button');
            btn.type = 'submit';
            btn.className = 'botao botao--chefe bloco';
            btn.textContent = textosCaixa.botao_fechar_conta || 'Fechar conta e registrar pagamento';
            form.appendChild(btn);

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!confirm(textosCaixa.confirmar_fechar || 'Confirmar o fechamento da conta desta mesa?')) return;

                var pagamento = form.elements.forma_pagamento.value;
                var recebido = Number(String(form.elements.troco_para.value || '').replace(',', '.'));
                if (pagamento === 'dinheiro' && !recebido) {
                    toast(textosCaixa.erro_troco_obrigatorio || 'Informe o valor recebido em dinheiro.');
                    inputTroco.focus();
                    return;
                }
                if (pagamento === 'dinheiro' && recebido < totalConta) {
                    toast((textosCaixa.erro_troco_menor || 'O valor recebido é menor que o total da conta') + ' (' + formatarMoeda(totalConta - recebido) + ')');
                    inputTroco.focus();
                    return;
                }

                btn.disabled = true;
                enviar(window.Rotas.caixaFechar.replace('__ID__', mesaCaixaAberta), {
                    forma_pagamento: pagamento,
                    troco_para: pagamento === 'dinheiro' ? form.elements.troco_para.value : null
                }).then(function (r) {
                    toast(r.mensagem);
                    fecharModalCaixa();
                    buscarEstadoCaixa();
                }).catch(function (erro) {
                    toast(erro.message, 'erro');
                    btn.disabled = false;
                });
            });

            return form;
        }

        function carregarConta(payload) {
            if (!payload || !payload.mesa) return;
            textosCaixa = textosCaixaAtuais();

            var dados = payload.mesa;
            if (modalCaixaTitulo) modalCaixaTitulo.textContent = dados.nome;
            if (modalCaixaSubtitulo) {
                var qtd = (dados.pedidos || []).length;
                modalCaixaSubtitulo.textContent = qtd > 0
                    ? (qtd + ' ' + (textosCaixa.modal_pedidos_abertos || 'pedido(s) em aberto') + ' · ' + dados.capacidade + ' ' + (textosCaixa.pessoa_plural || 'pessoas'))
                    : (dados.capacidade + ' ' + (textosCaixa.pessoa_plural || 'pessoas'));
            }

            if (!modalCaixaCorpo) return;
            modalCaixaCorpo.innerHTML = '';

            if (!dados.pedidos || dados.pedidos.length === 0) {
                var vazio = document.createElement('p');
                vazio.className = 'modal-mesa__vazio';
                vazio.textContent = textosCaixa.modal_vazio || 'Esta mesa está livre — nenhum pedido em aberto.';
                modalCaixaCorpo.appendChild(vazio);
                if (modalCaixa) modalCaixa.hidden = false;
                return;
            }

            var colunaPedidos = document.createElement('div');
            colunaPedidos.className = 'caixa-pedidos';
            dados.pedidos.forEach(function (p) {
                colunaPedidos.appendChild(criarBlocoConta(p));
            });

            var totalMesa = document.createElement('p');
            totalMesa.className = 'cartao-mesa__total modal-mesa__total-mesa caixa-total-conta';
            totalMesa.textContent = (textosCaixa.modal_total_mesa || 'Total da conta') + ': ';
            var valorTotal = document.createElement('strong');
            valorTotal.textContent = formatarMoeda(dados.total);
            totalMesa.appendChild(valorTotal);

            var colunaFechamento = document.createElement('div');
            colunaFechamento.className = 'caixa-fechamento';
            colunaFechamento.appendChild(totalMesa);
            colunaFechamento.appendChild(montarFormaPagamento(dados, payload.pix || {}));

            modalCaixaCorpo.appendChild(colunaPedidos);
            modalCaixaCorpo.appendChild(colunaFechamento);

            if (modalCaixa) modalCaixa.hidden = false;
        }

        function buscarConta(mesaId) {
            if (!modalCaixaCorpo) return;
            mesaCaixaAberta = mesaId;
            modalCaixaCorpo.innerHTML = '';
            var carregando = document.createElement('p');
            carregando.className = 'modal-mesa__carregando';
            carregando.textContent = textosCaixa.modal_carregando || 'Carregando a conta da mesa...';
            modalCaixaCorpo.appendChild(carregando);
            if (modalCaixa) modalCaixa.hidden = false;

            fetch(window.Rotas.caixaConta.replace('__ID__', mesaId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) {
                    if (!r.ok) throw new Error('http ' + r.status);
                    return r.json();
                })
                .then(carregarConta)
                .catch(function () {
                    modalCaixaCorpo.innerHTML = '';
                    var erro = document.createElement('p');
                    erro.className = 'modal-mesa__vazio';
                    erro.textContent = 'Erro ao carregar a conta da mesa.';
                    modalCaixaCorpo.appendChild(erro);
                });
        }

        function buscarEstadoCaixa() {
            if (!window.Rotas || !window.Rotas.caixaEstado) return;
            fetch(window.Rotas.caixaEstado, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (payload) {
                    (payload.mesas || []).forEach(function (mesa) {
                        montarCartaoCaixa(mesa.id, mesa);
                    });
                })
                .catch(function () { });
        }

        gradeCaixa.addEventListener('click', function (evento) {
            var cartao = evento.target.closest('[data-mesa-id]');
            if (!cartao) return;
            buscarConta(cartao.dataset.mesaId);
        });

        gradeCaixa.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter' || evento.key === ' ') {
                var cartao = evento.target.closest('[data-mesa-id]');
                if (!cartao) return;
                evento.preventDefault();
                buscarConta(cartao.dataset.mesaId);
            }
        });

        modalCaixa.querySelectorAll('[data-fechar-modal-caixa]').forEach(function (el) {
            el.addEventListener('click', fecharModalCaixa);
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && modalCaixa && !modalCaixa.hidden) {
                fecharModalCaixa();
            }
        });

        setTimeout(buscarEstadoCaixa, 200);
        setInterval(buscarEstadoCaixa, 7000);
    }

    /* -------- tablet do garçom: criar pedido da mesa -------- */
    var cartaoMesaPedido = document.getElementById('mesa-pedido-cart');
    if (cartaoMesaPedido) {
        var textosMesa = {};

        function textosMesaAtuais() {
            return (window.Textos && window.Textos.mesaPedido) || {};
        }

        var linhasMesa = {};

        function chaveLinhaMesa(produtoId, complementos) {
            var ids = (complementos || []).map(function (c) { return Number(c.id); }).sort().join(',');
            return produtoId + '|' + ids;
        }

        function produtoPorBotao(botao) {
            var card = botao.closest('.cartao-produto-mesa');
            if (!card) return null;

            var complementos;
            try { complementos = JSON.parse(card.dataset.complementos || '[]'); } catch (e) { complementos = []; }

            return {
                id: Number(card.dataset.produtoId),
                nome: card.dataset.produtoNome || 'Produto',
                preco: Number(card.dataset.produtoPreco) || 0,
                estoque: card.dataset.produtoEstoque === '' ? null : Number(card.dataset.produtoEstoque),
                complementos: complementos
            };
        }

        function montarModalPersonalizarMesa(card) {
            var dados = {
                produtoId: Number(card.dataset.produtoId),
                nome: card.dataset.produtoNome || 'Produto',
                precoBase: Number(card.dataset.produtoPreco) || 0,
                adicionais: [],
                remocoes: []
            };
            try {
                var lista = JSON.parse(card.dataset.complementos || '[]');
                dados.adicionais = lista.filter(function (c) { return c.tipo === 'adicional'; });
                dados.remocoes = lista.filter(function (c) { return c.tipo === 'remocao'; });
            } catch (e) { /* sem complementos */ }

            return dados;
        }

        function adicionarLinhaMesa(produto, complementos, quantidade) {
            var chave = chaveLinhaMesa(produto.id, complementos);
            var atual = linhasMesa[chave];
            var qtdAtual = (atual ? atual.qtd : 0) + Math.max(1, Number(quantidade) || 1);

            if (produto.estoque !== null && produto.estoque !== undefined && qtdAtual > produto.estoque) {
                toast(textosMesa.sem_estoque || 'Quantidade acima do estoque disponível.', 'erro');
                return;
            }

            var escolhidos = (complementos || []).map(function (c) {
                return { id: Number(c.id), tipo: c.tipo, nome: c.nome, preco: Number(c.preco) || 0 };
            });

            linhasMesa[chave] = {
                produto: { id: produto.id, nome: produto.nome, preco: produto.preco },
                qtd: qtdAtual,
                comods: escolhidos
            };
            renderizarPedidoMesa();
        }

        function removerLinhaMesa(chave) {
            delete linhasMesa[chave];
            renderizarPedidoMesa();
        }

        function subtotalLinhaMesa(linha) {
            var extra = linha.comods.reduce(function (soma, c) {
                return soma + (c.tipo === 'adicional' ? (Number(c.preco) || 0) : 0);
            }, 0);
            return (linha.produto.preco + extra) * linha.qtd;
        }

        var areaLinhas = document.getElementById('mesa-pedido-linhas');
        var textoVazio = document.getElementById('mesa-pedido-vazio');

        function renderizarPedidoMesa() {
            textosMesa = textosMesaAtuais();
            if (!areaLinhas) return;

            var chaves = Object.keys(linhasMesa);
            areaLinhas.innerHTML = '';
            if (textoVazio) textoVazio.hidden = chaves.length > 0;

            var total = 0;

            chaves.forEach(function (chave) {
                var linha = linhasMesa[chave];
                var item = document.createElement('article');
                item.className = 'mesa-pedido__linha';

                var cabecalho = document.createElement('div');
                cabecalho.className = 'mesa-pedido__linha-cabecalho';
                var nome = document.createElement('strong');
                nome.textContent = linha.produto.nome;
                cabecalho.appendChild(nome);
                var remover = document.createElement('button');
                remover.type = 'button';
                remover.className = 'mesa-pedido__linha-remover';
                remover.textContent = '×';
                remover.title = textosMesa.cart_remover || 'Remover';
                remover.addEventListener('click', function () { removerLinhaMesa(chave); });
                cabecalho.appendChild(remover);
                item.appendChild(cabecalho);

                if (linha.comods.length) {
                    var comps = document.createElement('small');
                    comps.className = 'mesa-pedido__linha-complementos';
                    comps.textContent = '+ ' + linha.comods.map(function (c) { return c.nome; }).join(', ');
                    item.appendChild(comps);
                }

                var pe = document.createElement('div');
                pe.className = 'mesa-pedido__linha-pe';

                var qtd = document.createElement('div');
                qtd.className = 'mesa-pedido__qtd';
                var menos = document.createElement('button');
                menos.type = 'button';
                menos.textContent = '−';
                menos.addEventListener('click', function () {
                    if (linha.qtd <= 1) { removerLinhaMesa(chave); return; }
                    linha.qtd -= 1;
                    renderizarPedidoMesa();
                });
                var qtdValor = document.createElement('span');
                qtdValor.textContent = linha.qtd;
                var mais = document.createElement('button');
                mais.type = 'button';
                mais.textContent = '+';
                mais.addEventListener('click', function () {
                    var produto = { id: linha.produto.id, nome: linha.produto.nome, preco: linha.produto.preco, estoque: null };
                    var card = document.querySelector('.cartao-produto-mesa[data-produto-id="' + linha.produto.id + '"]');
                    if (card && card.dataset.produtoEstoque !== '') {
                        produto.estoque = Number(card.dataset.produtoEstoque);
                    }
                    adicionarLinhaMesa(produto, linha.comods, 1);
                });
                qtd.appendChild(menos);
                qtd.appendChild(qtdValor);
                qtd.appendChild(mais);
                pe.appendChild(qtd);

                var subtotal = document.createElement('strong');
                subtotal.className = 'mesa-pedido__linha-subtotal';
                subtotal.textContent = formatarMoeda(subtotalLinhaMesa(linha));
                pe.appendChild(subtotal);
                item.appendChild(pe);

                areaLinhas.appendChild(item);
                total += subtotalLinhaMesa(linha);
            });

            var contador = document.getElementById('mesa-pedido-contador');
            if (contador) contador.textContent = String(chaves.length);

            var totalEl = document.getElementById('mesa-pedido-total');
            if (totalEl) totalEl.textContent = formatarMoeda(total);

            var enviar = document.getElementById('mesa-pedido-enviar');
            if (enviar) enviar.disabled = chaves.length === 0;
        }

        var modalMesa = document.getElementById('modal-personalizar');
        var modalMesaCorpo, modalMesaQtd, modalMesaTotal;

        function abrirModalPersonalizarMesa(card) {
            if (!modalMesa || !card) return;
            textosMesa = textosMesaAtuais();

            var dados = montarModalPersonalizarMesa(card);
            var titulo = document.getElementById('modal-personalizar-titulo');
            var base = document.querySelector('.modal-personalizar__base');

            if (titulo) titulo.textContent = dados.nome;
            if (base) base.textContent = formatarMoeda(dados.precoBase);

            modalMesa.dataset.precoBase = dados.precoBase;
            modalMesa.dataset.produtoId = dados.produtoId;
            modalMesa.dataset.nome = dados.nome;

            modalMesaQtd = modalMesa.querySelector('[data-qtd="valor"]');
            modalMesaCorpo = document.getElementById('modal-personalizar-corpo');
            modalMesaTotal = modalMesa.querySelector('[data-total]');
            if (modalMesaQtd) modalMesaQtd.textContent = '1';

            var html = '';
            if (dados.adicionais.length) {
                html += '<h3>' + (textosMesa.modal_adicionais || 'Adicionais') + '</h3>';
                dados.adicionais.forEach(function (c) {
                    html += '<label class="modal-personalizar__opcao">' +
                        '<input type="checkbox" data-tipo="adicional" data-preco="' + c.preco + '" value="' + c.id + '">' +
                        '<span>' + c.nome + '</span>' +
                        '<small>(+' + formatarMoeda(c.preco) + ' ' + (textosMesa.modal_cada || 'cada') + ')</small>' +
                        '</label>';
                });
            }
            if (dados.remocoes.length) {
                html += '<h3>' + (textosMesa.modal_remocoes || 'Remoções') + '</h3>';
                dados.remocoes.forEach(function (c) {
                    html += '<label class="modal-personalizar__opcao">' +
                        '<input type="checkbox" data-tipo="remocao" data-preco="0" value="' + c.id + '">' +
                        '<span>' + c.nome + '</span>' +
                        '</label>';
                });
            }
            if (!dados.adicionais.length && !dados.remocoes.length) {
                html = '<p class="modal-personalizar__vazio">' + (textosMesa.modal_vazio || 'Sem opções') + '</p>';
            }

            if (modalMesaCorpo) {
                modalMesaCorpo.innerHTML = html;
                modalMesaCorpo.addEventListener('change', calcularTotalModalMesa);
            }
            calcularTotalModalMesa();
            modalMesa.hidden = false;
            document.body.style.overflow = 'hidden';
        }

        function calcularTotalModalMesa() {
            if (!modalMesa) return;
            var qtd = Number(modalMesaQtd && modalMesaQtd.textContent) || 1;
            var base = Number(modalMesa.dataset.precoBase) || 0;
            var extra = 0;
            if (modalMesaCorpo) {
                modalMesaCorpo.querySelectorAll('input[data-tipo="adicional"]:checked').forEach(function (el) {
                    extra += Number(el.dataset.preco) || 0;
                });
            }
            if (modalMesaTotal) modalMesaTotal.textContent = formatarMoeda((base + extra) * qtd);
        }

        (function variarQtdModalMesa() {
            if (!modalMesa) return;
            modalMesa.querySelectorAll('[data-qtd]').forEach(function (botao) {
                botao.addEventListener('click', function () {
                    var qtd = Number(modalMesaQtd && modalMesaQtd.textContent) || 1;
                    qtd = botao.dataset.qtd === 'mais' ? qtd + 1 : Math.max(1, qtd - 1);
                    if (modalMesaQtd) modalMesaQtd.textContent = qtd;
                    calcularTotalModalMesa();
                });
            });

            modalMesa.querySelector('[data-confirmar]').addEventListener('click', function () {
                if (!modalMesaCorpo) return;
                var ids = [];
                modalMesaCorpo.querySelectorAll('input:checked').forEach(function (el) { ids.push(Number(el.value)); });

                var card = document.querySelector('.cartao-produto-mesa[data-produto-id="' + modalMesa.dataset.produtoId + '"]');
                var complementos = [];
                if (card) {
                    try { complementos = JSON.parse(card.dataset.complementos || '[]'); } catch (e) { complementos = []; }
                    complementos = complementos.filter(function (c) { return ids.indexOf(Number(c.id)) !== -1; });
                }

                adicionarLinhaMesa(
                    { id: Number(modalMesa.dataset.produtoId), nome: modalMesa.dataset.nome || 'Produto', preco: Number(modalMesa.dataset.precoBase) || 0, estoque: card ? (card.dataset.produtoEstoque === '' ? null : Number(card.dataset.produtoEstoque)) : null },
                    complementos,
                    Number(modalMesaQtd && modalMesaQtd.textContent) || 1
                );

                modalMesa.hidden = true;
                document.body.style.overflow = '';
            });
        })();

        document.addEventListener('click', function (evento) {
            var adicionarDireto = evento.target.closest('[data-adicionar-direto]');
            if (adicionarDireto) {
                var produto = produtoPorBotao(adicionarDireto);
                if (produto) adicionarLinhaMesa(produto, [], 1);
                return;
            }

            var personalizar = evento.target.closest('[data-modal-personalizar]');
            if (personalizar) {
                var card = personalizar.closest('.cartao-produto-mesa');
                if (card) abrirModalPersonalizarMesa(card);
            }

            if (evento.target.closest('[data-fechar]')) {
                if (modalMesa) modalMesa.hidden = true;
                document.body.style.overflow = '';
            }
        });

        var enviarPedidoMesa = document.getElementById('mesa-pedido-enviar');

        function confirmarPedidoMesa() {
            textosMesa = textosMesaAtuais();
            var itens = Object.keys(linhasMesa).map(function (chave) {
                var linha = linhasMesa[chave];
                return {
                    produto_id: linha.produto.id,
                    quantidade: linha.qtd,
                    complementos: linha.comods.map(function (c) { return c.id; })
                };
            });

            if (!itens.length) {
                toast(textosMesa.sem_itens || 'Escolha ao menos um item.', 'erro');
                return;
            }

            if (enviarPedidoMesa) enviarPedidoMesa.disabled = true;

            enviar(window.Rotas.mesaPedidoConfirmar, {
                itens: itens,
                nome_cliente: document.querySelector('[name="nome_cliente"]')?.value || '',
                observacoes: document.querySelector('[name="observacoes"]')?.value || ''
            }).then(function (r) {
                toast(r.mensagem);
                linhasMesa = {};
                renderizarPedidoMesa();
                document.querySelector('#mesa-pedido-form [name="nome_cliente"]').value = '';
                document.querySelector('#mesa-pedido-form [name="observacoes"]').value = '';
            }).catch(function (erro) {
                toast(erro.message, 'erro');
                if (enviarPedidoMesa) enviarPedidoMesa.disabled = false;
            });
        }

        if (enviarPedidoMesa) enviarPedidoMesa.addEventListener('click', confirmarPedidoMesa);

        document.addEventListener('click', function (evento) {
            var botaoEntregar = evento.target.closest('[data-entregar-pedido]');
            if (!botaoEntregar) return;

            var linha = botaoEntregar.closest('[data-pedido-id]');
            var pedidoId = linha && linha.getAttribute('data-pedido-id');
            if (!pedidoId) return;

            textosMesa = textosMesaAtuais();
            if (! window.confirm(textosMesa.abertos_confirmar_entrega || 'Confirmar marcação de entregue na mesa?')) {
                return;
            }
            var rotulo = botaoEntregar.textContent;
            botaoEntregar.disabled = true;
            botaoEntregar.textContent = textosMesa.abertos_entregando || 'Marcando...';

            enviar((window.Rotas.pedidoEntregueMesa || '').replace('ID', pedidoId), {})
                .then(function () {
                    var selo = document.createElement('span');
                    selo.className = 'mesa-pedido__aberto-entregue';
                    var agora = new Date().toLocaleTimeString().slice(0, 5);
                    selo.textContent = (textosMesa.abertos_entregue_as || 'Entregue às :hora').replace(':hora', agora);
                    botaoEntregar.replaceWith(selo);
                    toast(textosMesa.sucesso_entregue || 'Pedido marcado como entregue na mesa!');
                })
                .catch(function (erro) {
                    botaoEntregar.disabled = false;
                    botaoEntregar.textContent = rotulo;
                    toast(erro.message, 'erro');
                });
        });
    }
})();

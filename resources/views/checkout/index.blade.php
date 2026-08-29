@extends('layouts.loja')

@section('titulo', tema_texto('nome', 'Guloseimas').' — '.texto('checkout', 'titulo.pagina', 'Finalizar pedido'))

@section('conteudo')
<h1 class="titulo-pagina">{{ texto('checkout', 'titulo.pagina', 'Finalizar pedido') }}</h1>

@if($errors->any())
    <div class="alerta alerta--erro">
        <strong>{{ texto('checkout', 'erro.titulo', 'Confira os campos:') }}</strong>
        <ul>
            @foreach($errors->all() as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($itensMudaram->isNotEmpty())
    <div class="alerta alerta--sucesso">
        {{ texto('checkout', 'aviso.preco_atualizado', 'Atenção: o valor de algum item mudou desde que você adicionou. O pedido abaixo usa sempre o valor atual do sistema — nada é cobrado com preço antigo.') }}
    </div>
@endif

<form method="POST" action="{{ route('checkout.finalizar') }}" class="checkout-layout" id="form-checkout"
      data-subtotal="{{ $subtotal }}" data-taxa="{{ $taxaEntrega }}">
    @csrf

    <div class="checkout-colunas">
        {{-- ================= DADOS + ENTREGA + PAGAMENTO ================= --}}
        <section class="painel">
            <h2>{{ texto('checkout', 'secao.dados', 'Seus dados') }}</h2>
            <label>{{ texto('conta', 'campo.nome', 'Nome completo') }}
                <input type="text" name="nome_cliente" value="{{ old('nome_cliente', $cliente->nome ?? '') }}" required minlength="3">
            </label>
            <div class="linha-dupla">
                <label>{{ texto('conta', 'campo.telefone', 'Telefone / WhatsApp') }}
                    <input type="tel" name="telefone" value="{{ old('telefone', $cliente->telefone ?? '') }}" required>
                </label>
                <label>{{ texto('conta', 'campo.email', 'E-mail (opcional)') }}
                    <input type="email" name="email" value="{{ old('email', $cliente->email ?? '') }}">
                </label>
            </div>

            <h2>{{ texto('checkout', 'secao.entrega', 'Entrega ou retirada?') }}</h2>
            <div class="opcoes-cartao">
                <label class="opcao-cartao">
                    <input type="radio" name="tipo_entrega" value="entrega" {{ old('tipo_entrega', 'entrega') === 'entrega' ? 'checked' : '' }} data-alterna="campos-entrega">
                    <span class="opcao-cartao__corpo">
                        <strong>{{ texto('checkout', 'entrega.opcao_entrega', 'Receber em casa') }}</strong>
                        <small>{{ str_replace(':taxa', preco_br($taxaEntrega), texto('checkout', 'entrega.opcao_entrega_nota', 'Taxa de entrega: :taxa')) }}</small>
                    </span>
                </label>
                <label class="opcao-cartao">
                    <input type="radio" name="tipo_entrega" value="retirada" {{ old('tipo_entrega') === 'retirada' ? 'checked' : '' }} data-alterna="campos-entrega">
                    <span class="opcao-cartao__corpo">
                        <strong>{{ texto('checkout', 'entrega.opcao_retirada', 'Retirar na loja') }}</strong>
                        <small>{{ texto('checkout', 'entrega.opcao_retirada_nota', 'Sem taxa de entrega') }}</small>
                    </span>
                </label>
            </div>

            <div id="bloco-enderecos-salvos" @unless($enderecos->isNotEmpty()) hidden @endif>
                <h3>{{ texto('checkout', 'endereco.salvo_titulo', 'Enviar para um endereço salvo') }}</h3>
                <div class="opcoes-cartao opcoes-cartao--coluna">
                    @foreach($enderecos as $indice => $endereco)
                        <label class="opcao-cartao opcao-cartao--pequena">
                            <input type="radio"
                                   name="endereco_salvo_id"
                                   value="{{ $endereco->id }}"
                                   data-endereco-salvo
                                   {{ old('endereco_salvo_id', ($indice === 0 && !old('novo_endereco')) ? $endereco->id : '') === $endereco->id ? 'checked' : '' }}>
                            <span class="opcao-cartao__corpo">
                                <strong>{{ $endereco->rua }}, {{ $endereco->numero }}</strong>
                                <small>{{ $endereco->bairro }} — {{ $endereco->cidade }}{{ $endereco->principal ? ' ★' : '' }}</small>
                            </span>
                        </label>
                    @endforeach

                    <label class="opcao-cartao opcao-cartao--pequena">
                        <input type="radio" name="endereco_salvo_id" value="" data-novo-endereco {{ old('novo_endereco') ? 'checked' : '' }}>
                        <span class="opcao-cartao__corpo">
                            <strong>{{ texto('checkout', 'endereco.outro', 'Usar outro endereço') }}</strong>
                        </span>
                    </label>
                </div>
            </div>

            <div id="campos-entrega" @unless(old('novo_endereco') || $enderecos->isEmpty()) hidden @endunless>
                <label>{{ texto('checkout', 'campo.rua', 'Rua') }}
                    <input type="text" name="rua" value="{{ old('rua') }}">
                </label>
                <div class="linha-dupla">
                    <label>{{ texto('checkout', 'campo.numero', 'Número') }}
                        <input type="text" name="numero" value="{{ old('numero') }}">
                    </label>
                    <label>{{ texto('checkout', 'campo.complemento', 'Complemento') }}
                        <input type="text" name="complemento" value="{{ old('complemento') }}">
                    </label>
                </div>
                <div class="linha-dupla">
                    <label>{{ texto('checkout', 'campo.bairro', 'Bairro') }}
                        <input type="text" name="bairro" value="{{ old('bairro') }}">
                    </label>
                    <label>{{ texto('checkout', 'campo.cidade', 'Cidade') }}
                        <input type="text" name="cidade" value="{{ old('cidade') }}">
                    </label>
                </div>
                <label>{{ texto('checkout', 'campo.cep', 'CEP') }}
                    <input type="text" name="cep" value="{{ old('cep') }}">
                </label>
            </div>

            <p id="nota-retirada" class="nota-retirada" @unless(old('tipo_entrega') === 'retirada') hidden @endunless>
                {{ texto('checkout', 'entrega.nota_retirada', 'Combinaremos o horário da retirada pelo WhatsApp após a confirmação do pedido.') }}
            </p>

            <h2>{{ texto('checkout', 'secao.pagamento', 'Pagamento') }}</h2>
            <div class="opcoes-cartao opcoes-cartao--coluna">
                <label class="opcao-cartao">
                    <input type="radio" name="forma_pagamento" value="pix" {{ old('forma_pagamento', $cartaoMpAtivo ? '' : 'pix') === 'pix' ? 'checked' : '' }}>
                    <span class="opcao-cartao__corpo">
                        <strong>{{ forma_pagamento_label('pix') }}</strong>
                        <small>{{ texto('checkout', 'pagamento.pix_nota', 'Chave Pix exibida na confirmação do pedido.') }}</small>
                    </span>
                </label>
                @if($pixEfiAtivo)
                    <label class="opcao-cartao">
                        <input type="radio" name="forma_pagamento" value="pix_efi" {{ old('forma_pagamento') === 'pix_efi' ? 'checked' : '' }}>
                        <span class="opcao-cartao__corpo">
                            <strong>{{ forma_pagamento_label('pix_efi') }}</strong>
                            <small>{{ texto('checkout', 'pagamento.pix_efi_nota', 'Pix na hora: geramos o copia e cola na confirmação e confirmamos sozinhos quando você pagar.') }}</small>
                        </span>
                    </label>
                @endif
                @if($cartaoMpAtivo)
                    <label class="opcao-cartao">
                        <input type="radio" name="forma_pagamento" value="cartao_mp" {{ old('forma_pagamento') === 'cartao_mp' ? 'checked' : '' }}>
                        <span class="opcao-cartao__corpo">
                            <strong>{{ forma_pagamento_label('cartao_mp') }}</strong>
                            <small>{{ texto('checkout', 'pagamento.cartao_mp_nota', 'Você será levado à tela segura do Mercado Pago — cartão, Pix ou saldo.') }}</small>
                        </span>
                    </label>
                @endif
                <label class="opcao-cartao">
                    <input type="radio" name="forma_pagamento" value="cartao" {{ old('forma_pagamento') === 'cartao' ? 'checked' : '' }}>
                    <span class="opcao-cartao__corpo">
                        <strong>{{ forma_pagamento_label('cartao') }}</strong>
                        <small>{{ texto('checkout', 'pagamento.cartao_nota', 'Online com cartão salvo ou combinado com você.') }}</small>
                    </span>
                </label>
                <label class="opcao-cartao">
                    <input type="radio" name="forma_pagamento" value="dinheiro" {{ old('forma_pagamento') === 'dinheiro' ? 'checked' : '' }}>
                    <span class="opcao-cartao__corpo">
                        <strong>{{ forma_pagamento_label('dinheiro') }}</strong>
                        <small>{{ texto('checkout', 'pagamento.dinheiro_nota', 'Pague na entrega/retirada.') }}</small>
                    </span>
                </label>
            </div>

            <div id="bloco-cartoes-salvos" @unless($cartoes->isNotEmpty()) hidden @endif>
                <h3>{{ texto('checkout', 'pagamento.cartao_salvo_titulo', 'Pagar com um cartão salvo') }}</h3>
                <select name="cartao_salvo_id" class="entrada-texto">
                    <option value="">{{ texto('checkout', 'pagamento.cartao_selecione', 'Selecione o cartão...') }}</option>
                    @foreach($cartoes as $cartao)
                        <option value="{{ $cartao->id }}" {{ old('cartao_salvo_id') == $cartao->id ? 'selected' : '' }}>
                            {{ $cartao->apelido }} — {{ $cartao->bandeira }} ****{{ $cartao->numero_final }}
                        </option>
                    @endforeach
                </select>
            </div>

            <label id="campo-troco" hidden>{{ texto('checkout', 'pagamento.troco_para', 'Precisa de troco para quanto?') }}
                <input type="number" name="troco_para" step="0.01" min="0" value="{{ old('troco_para') }}">
            </label>

            <label>{{ texto('checkout', 'campo.observacoes', 'Observações (opcional)') }}
                <textarea name="observacoes" rows="2" maxlength="1000">{{ old('observacoes') }}</textarea>
            </label>
        </section>

        {{-- ================= RESUMO ================= --}}
        <aside class="resumo resumo--fixo">
            <h2>{{ texto('checkout', 'resumo.titulo', 'Resumo do pedido') }}</h2>
            <ul class="resumo__itens">
                @foreach($itens as $item)
                    <li>
                        <span>{{ $item['quantidade'] }}× {{ $item['produto']->nome }}</span>
                        <strong>{{ preco_br($item['subtotal']) }}</strong>
                        @include('partials.complementos_linha', ['complementos' => $item['complementos'] ?? []])
                    </li>
                @endforeach
            </ul>
            <p class="resumo__linha"><span>{{ texto('carrinho', 'resumo.subtotal', 'Subtotal') }}</span><strong>{{ preco_br($subtotal) }}</strong></p>
            <p class="resumo__linha"><span>{{ texto('checkout', 'resumo.taxa_entrega', 'Taxa de entrega') }}</span><strong id="resumo-taxa">{{ preco_br($taxaEntrega) }}</strong></p>
            <p class="resumo__linha resumo__linha--desconto" id="resumo-linha-desconto" hidden>
                <span>{{ texto('checkout', 'resumo.desconto', 'Desconto (cupom)') }}</span>
                <strong id="resumo-desconto">—</strong>
            </p>
            <p class="resumo__linha resumo__linha--desconto" id="resumo-linha-desconto-pontos" hidden>
                <span>{{ texto('fidelidade', 'resumo.desconto', 'Desconto (pontos)') }}</span>
                <strong id="resumo-desconto-pontos">—</strong>
            </p>
            <p class="resumo__linha resumo__linha--total"><span>{{ texto('checkout', 'resumo.total', 'Total') }}</span><strong id="resumo-total">{{ preco_br($subtotal + $taxaEntrega) }}</strong></p>

            <div class="campo-cupom">
                <label for="cupom-codigo">{{ texto('checkout', 'cupom.titulo', 'Cupom de desconto') }}</label>
                <div class="campo-cupom__linha">
                    <input type="text" id="cupom-codigo" name="cupom_codigo" value="{{ old('cupom_codigo') }}"
                           placeholder="{{ texto('checkout', 'cupom.placeholder', 'Ex.: FESTA10') }}" autocomplete="off" maxlength="40">
                    <button type="button" class="botao" id="botao-aplicar-cupom">{{ texto('checkout', 'cupom.aplicar', 'Aplicar') }}</button>
                </div>
                <input type="hidden" id="cupom-ok" name="cupom_ok" value="">
                @if(session('erro_cupom'))
                    <p class="campo-cupom__erro" id="mensagem-cupom">{{ session('erro_cupom') }}</p>
                @else
                    <p class="campo-cupom__resposta" id="mensagem-cupom"></p>
                @endif
            </div>

            @if($fidelidadeAtivo && $cliente)
                <input type="hidden" id="pontos-utilizados" name="pontos_utilizados" value="{{ old('pontos_utilizados') }}">
                <div class="campo-pontos">
                    <label>{{ texto('fidelidade', 'saldo', 'Seus pontos') }}: <strong>{{ str_replace(':pontos', round($saldoPontos), texto('fidelidade', 'saldo.quantidade', ':pontos pontos')) }}</strong></label>
                    <div class="campo-pontos__linha">
                        <input type="number" id="pontos-input" min="0" max="{{ max(floor($saldoPontos), 0) }}" value="" placeholder="0">
                        <button type="button" class="botao" id="botao-aplicar-pontos">{{ texto('fidelidade', 'botao.aplicar', 'Usar pontos') }}</button>
                    </div>
                    <p class="campo-pontos__resposta" id="mensagem-pontos"></p>
                </div>
            @endif

            <button type="submit" class="botao botao--chefe botao--grande bloco">{{ texto('checkout', 'botao.confirmar', 'Confirmar pedido') }}</button>
            <a href="{{ route('carrinho.index') }}" class="link-voltar">{{ texto('carrinho', 'botao.continuar', 'Continuar comprando') }}</a>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('form-checkout');
        var campoCupom = document.getElementById('cupom-codigo');
        var botaoAplicar = document.getElementById('botao-aplicar-cupom');
        var campoOk = document.getElementById('cupom-ok');
        var mensagemEl = document.getElementById('mensagem-cupom');
        var usouCupom = false;

        if (!form || !campoCupom || !botaoAplicar) return;

        var subtotal = parseFloat(form.dataset.subtotal) || 0;
        var taxaFixa = parseFloat(form.dataset.taxa) || 0;
        var desconto = 0;
        var descontoPontos = 0;
        var usouPontos = false;
        var pontosInput = document.getElementById('pontos-input');
        var botaoAplicarPontos = document.getElementById('botao-aplicar-pontos');
        var pontosHidden = document.getElementById('pontos-utilizados');
        var mensagemPontos = document.getElementById('mensagem-pontos');

        function taxaAtual() {
            var tipo = form.querySelector('[name="tipo_entrega"]:checked')?.value;
            return tipo === 'entrega' ? taxaFixa : 0;
        }

        function formatarMoeda(valor) {
            return 'R$ ' + valor.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function atualizarTotais() {
            var taxa = taxaAtual();
            var total = Math.max(subtotal + taxa - desconto - descontoPontos, 0);
            var linhaDesconto = document.getElementById('resumo-linha-desconto');
            document.getElementById('resumo-taxa').textContent = formatarMoeda(taxa);
            document.getElementById('resumo-total').textContent = formatarMoeda(total);
            if (linhaDesconto && desconto > 0) {
                linhaDesconto.hidden = false;
                document.getElementById('resumo-desconto').textContent = '- ' + formatarMoeda(desconto);
            } else if (linhaDesconto) {
                linhaDesconto.hidden = true;
            }
            var linhaPontos = document.getElementById('resumo-linha-desconto-pontos');
            if (linhaPontos && descontoPontos > 0) {
                linhaPontos.hidden = false;
                document.getElementById('resumo-desconto-pontos').textContent = '- ' + formatarMoeda(descontoPontos);
            } else if (linhaPontos) {
                linhaPontos.hidden = true;
            }
        }

        function definirMensagem(txt, tipo) {
            if (!mensagemEl) return;
            mensagemEl.textContent = txt || '';
            mensagemEl.className = 'campo-cupom__' + (tipo === 'erro' ? 'erro' : 'resposta');
            if (tipo === 'erro') mensagemEl.classList.add('campo-cupom__erro');
        }

        botaoAplicar.addEventListener('click', function () {
            var codigo = campoCupom.value.trim();
            if (codigo === '') { definirMensagem('{{ texto('checkout','cupom.vazio','Digite o código do cupom.') }}', 'erro'); return; }

            var dados = new URLSearchParams();
            dados.append('cupom_codigo', codigo);
            dados.append('subtotal', subtotal);
            dados.append('tipo_entrega', form.querySelector('[name="tipo_entrega"]:checked')?.value || 'entrega');

            fetch('{{ route('checkout.validar_cupom') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: dados.toString()
            }).then(function (r) { return r.json(); }).then(function (r) {
                if (r.valido) {
                    desconto = r.desconto;
                    usouCupom = true;
                    campoOk.value = r.codigo;
                    campoCupom.value = r.codigo;
                    definirMensagem(r.mensagem, 'ok');
                    atualizarTotais();
                } else {
                    desconto = 0;
                    usouCupom = false;
                    campoOk.value = '';
                    definirMensagem(r.mensagem, 'erro');
                    atualizarTotais();
                }
            }).catch(function () {
                definirMensagem('{{ texto('checkout','cupom.erro_rede','Não foi possível validar o cupom agora.') }}', 'erro');
            });
        });

        function definirMensagemPontos(txt, tipo) {
            if (!mensagemPontos) return;
            mensagemPontos.textContent = txt || '';
            mensagemPontos.className = 'campo-pontos__resposta' + (tipo === 'erro' ? ' campo-pontos__resposta--erro' : '');
        }

        if (botaoAplicarPontos && pontosInput) {
            botaoAplicarPontos.addEventListener('click', function () {
                var valor = pontosInput.value.trim().replace(',', '.');
                var pontos = parseFloat(valor) || 0;
                if (pontos <= 0) { definirMensagemPontos('{{ texto('fidelidade','erro.zero','Informe quantos pontos quer usar.') }}', 'erro'); return; }

                var dados = new URLSearchParams();
                dados.append('pontos', pontos);
                dados.append('subtotal', subtotal);
                dados.append('tipo_entrega', form.querySelector('[name="tipo_entrega"]:checked') ? form.querySelector('[name="tipo_entrega"]:checked').value : 'entrega');

                fetch('{{ route('checkout.validar_pontos') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: dados.toString()
                }).then(function (r) { return r.json(); }).then(function (r) {
                    if (r.valido) {
                        descontoPontos = r.desconto;
                        usouPontos = true;
                        pontosHidden.value = pontos;
                        definirMensagemPontos(r.mensagem, 'ok');
                        atualizarTotais();
                    } else {
                        descontoPontos = 0;
                        usouPontos = false;
                        pontosHidden.value = '';
                        definirMensagemPontos(r.mensagem, 'erro');
                        atualizarTotais();
                    }
                }).catch(function () {
                    definirMensagemPontos('{{ texto('fidelidade','erro.rede','Não foi possível validar os pontos agora.') }}', 'erro');
                });
            });

            pontosInput.addEventListener('change', function () {
                if (!usouPontos) return;
                descontoPontos = 0;
                usouPontos = false;
                pontosHidden.value = '';
                definirMensagemPontos('', 'ok');
                atualizarTotais();
            });
        }

        form.querySelectorAll('[name="tipo_entrega"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (radio.checked) atualizarTotais();
            });
        });

        if (campoCupom) {
            campoCupom.addEventListener('change', function () {
                if (!usouCupom) return;
                desconto = 0;
                usouCupom = false;
                campoOk.value = '';
                definirMensagem('', 'ok');
                atualizarTotais();
            });
        }
    })();
</script>
@endpush

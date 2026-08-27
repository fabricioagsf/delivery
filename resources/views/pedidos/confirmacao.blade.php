@extends('layouts.loja')

@section('titulo', texto('confirmacao', 'titulo.pagina', 'Pedido confirmado — Gostosuras'))

@section('conteudo')
<section class="confirmacao">
    <div class="confete" aria-hidden="true"></div>
    <h1>{{ texto('confirmacao', 'titulo', 'Pedido confirmado!') }}</h1>
    <p class="confirmacao__mensagem">{{ texto('confirmacao', 'mensagem', 'Suas gostosuras já estão na fila do tacho. Em breve entraremos em contato para confirmar tudo.') }}</p>

    @if(session('erro_pagamento'))
        <div class="alerta alerta--erro">{{ session('erro_pagamento') }}</div>
    @endif

    <p class="confirmacao__codigo">
        {{ texto('confirmacao', 'codigo.rotulo', 'Código do pedido') }}
        <strong>{{ $pedido->codigo }}</strong>
    </p>

    @auth('cliente')
        <div class="destaque-chave destaque-chave--aviso">
            <strong>{{ texto('confirmacao', 'chave_seguranca.titulo', 'Não esqueça sua chave de segurança') }}</strong>
            <p>{{ texto('confirmacao', 'chave_seguranca.texto', 'Informe a chave que você cadastrou ao receber o pedido. Assim garantimos que as gostosuras chegaram até você.') }}</p>
        </div>
    @endauth

    <div class="checkout-layout confirmacao__grade">
        <section class="painel">
            <h2>{{ texto('confirmacao', 'resumo.titulo', 'Resumo') }}</h2>
            <ul class="resumo__itens">
                @foreach($pedido->itens as $item)
                    <li>
                        <span>{{ $item->quantidade }}× {{ $item->nome_produto }}</span>
                        <strong>{{ preco_br($item->preco_unitario * $item->quantidade) }}</strong>
                    </li>
                @endforeach
            </ul>
            <p class="resumo__linha"><span>{{ texto('carrinho', 'resumo.subtotal', 'Subtotal') }}</span><strong>{{ preco_br($pedido->subtotal) }}</strong></p>
            <p class="resumo__linha"><span>{{ texto('checkout', 'resumo.taxa_entrega', 'Taxa de entrega') }}</span><strong>{{ preco_br($pedido->taxa_entrega) }}</strong></p>
            <p class="resumo__linha resumo__linha--total"><span>{{ texto('checkout', 'resumo.total', 'Total') }}</span><strong>{{ preco_br($pedido->total) }}</strong></p>
        </section>

        <aside class="painel">
            <h2>{{ forma_pagamento_label($pedido->forma_pagamento) }}</h2>

            @if($pedido->pagamento_status === 'pago')
                <div class="alerta alerta--sucesso">{{ texto('confirmacao', 'pagamento.pago', 'Pagamento confirmado! Obrigado.') }}</div>
            @endif

            @if($pedido->forma_pagamento === 'pix_efi' && $pedido->pagamento_status !== 'pago')
                @if($copiaECola)
                    <p>{{ texto('confirmacao', 'efi.instrucao', 'Pague com Pix copia e cola — a confirmação é automática quando você pagar.') }}</p>
                    <div class="caixa-pix"><code id="pix-copia-e-cola">{{ $copiaECola }}</code></div>
                    <button type="button" class="botao bloco" id="copiar-pix">{{ texto('confirmacao', 'efi.copiar', 'Copiar código Pix') }}</button>
                    <p class="texto-suave">{{ str_replace(':valor', preco_br($pedido->total), texto('confirmacao', 'pix.valor', 'Valor: :valor')) }}</p>
                @else
                    <p>{{ texto('confirmacao', 'efi.indisponivel', 'Não conseguimos gerar o Pix agora — recarregue a página em alguns instantes.') }}</p>
                @endif
            @elseif($pedido->forma_pagamento === 'cartao_mp' && $pedido->pagamento_status !== 'pago')
                <p>{{ texto('confirmacao', 'mp.pendente', 'Pagamento ainda não identificado no Mercado Pago.') }}</p>
                <form method="POST" action="{{ route('pedido.pagar', $pedido->codigo) }}">
                    @csrf
                    <button type="submit" class="botao botao--chefe bloco">{{ texto('confirmacao', 'mp.pagar_agora', 'Pagar agora no Mercado Pago') }}</button>
                </form>
            @elseif($pedido->forma_pagamento === 'pix')
                @if(!empty($chavePix))
                    <p class="chave-pix">{{ str_replace(':chave', $chavePix, texto('confirmacao', 'pix.instrucao', 'Faça o Pix de :valor usando a chave :chave e envie o comprovante pelo WhatsApp.')) }}
                    </p>
                    <div class="caixa-pix"><code>{{ $chavePix }}</code></div>
                @endif
                <p>{{ str_replace(':valor', preco_br($pedido->total), texto('confirmacao', 'pix.valor', 'Valor: :valor')) }}</p>
            @elseif($pedido->forma_pagamento === 'cartao')
                @if($pedido->cartao_id)
                    <p>{{ texto('confirmacao', 'cartao.salvo', 'Usaremos o cartão salvo da sua conta. Confirmaremos a cobrança pelo WhatsApp antes de preparar.') }}</p>
                @else
                    <p>{{ texto('confirmacao', 'cartao.manual', 'Combinaremos o pagamento com cartão pelo WhatsApp (maquininha na entrega ou link seguro).') }}</p>
                @endif
            @else
                <p>{{ str_replace(':valor', preco_br($pedido->total), texto('confirmacao', 'dinheiro.instrucao', 'Prepare :valor para pagar na entrega/retirada.')) }}</p>
                @if($pedido->troco_para)
                    <p>{{ str_replace(':valor', preco_br($pedido->troco_para), texto('confirmacao', 'dinheiro.troco', 'Separaremos troco para :valor.')) }}</p>
                @endif
            @endif

            <h2>{{ $pedido->tipo_entrega === 'entrega' ? texto('confirmacao', 'entrega.titulo', 'Entrega') : texto('confirmacao', 'retirada.titulo', 'Retirada') }}</h2>
            @if($pedido->tipo_entrega === 'entrega')
                <address class="endereco-texto">
                    {{ $pedido->rua }}, {{ $pedido->numero }}@if($pedido->complemento) — {{ $pedido->complemento }}@endif<br>
                    {{ $pedido->bairro }} — {{ $pedido->cidade }}@if($pedido->cep) · {{ texto('checkout', 'campo.cep', 'CEP') }} {{ $pedido->cep }}@endif
                </address>
            @else
                <p>{{ texto('confirmacao', 'retirada.texto', 'Retire na loja — combinaremos o horário pelo WhatsApp.') }}</p>
            @endif

            <a href="{{ route('vitrine') }}" class="botao botao--chefe bloco">{{ texto('confirmacao', 'botao.voltar', 'Voltar à loja') }}</a>
        </aside>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.getElementById('copiar-pix')?.addEventListener('click', function () {
        var codigo = document.getElementById('pix-copia-e-cola')?.textContent || '';
        var botao = this;
        navigator.clipboard?.writeText(codigo).then(function () {
            botao.textContent = '{{ texto('confirmacao', 'efi.copiado', 'Copiado!') }}';
            setTimeout(function () {
                botao.textContent = '{{ texto('confirmacao', 'efi.copiar', 'Copiar código Pix') }}';
            }, 2500);
        });
    });
</script>
@endpush

@php $promo = promo_destaque(); @endphp

@if($promo)
<section class="promo-destaque">
    <span class="promo-destaque__selo">{{ texto('vitrine', 'promo.selo', 'Promoção') }}</span>
    <div class="promo-destaque__texto">
        <strong>
            @if($promo->tipo === 'percentual')
                {{ str_replace(':v', number_format($promo->valor, 0), texto('vitrine', 'promo.percentual', ':v% OFF')) }}
            @else
                {{ str_replace(':v', preco_br($promo->valor), texto('vitrine', 'promo.fixo', ':v de desconto')) }}
            @endif
        </strong>
        <p>
            @if($promo->valor_minimo)
                {{ str_replace(':v', preco_br($promo->valor_minimo), texto('vitrine', 'promo.detalhe_minimo', 'Pedidos a partir de :v — use o código')) }}
            @else
                {{ texto('vitrine', 'promo.detalhe', 'Use o código') }}
            @endif
            <code>{{ $promo->codigo }}</code>
            {{ texto('vitrine', 'promo.no_checkout', 'no checkout.') }}
        </p>
    </div>
</section>
@endif

@extends('layouts.loja')

@section('titulo', texto('carrinho', 'titulo.pagina', 'Seu carrinho — Gostosuras'))

@section('conteudo')
<h1 class="titulo-pagina">{{ texto('carrinho', 'titulo.pagina', 'Seu carrinho') }}</h1>

@if(session('erro_estoque'))
    <div class="alerta alerta--erro">
        {{ str_replace(':produto', session('erro_estoque'), texto('carrinho', 'erro.estoque_flash', 'Ops! :produto não tem estoque suficiente e ficou de fora do pedido.')) }}
    </div>
@endif

@if(session('carrinho_vazio'))
    <div class="alerta alerta--erro">{{ session('carrinho_vazio') }}</div>
@endif

@empty($itens)
    <div class="vazio vazio--centralizado">
        <p>{{ texto('carrinho', 'vazio', 'Seu carrinho está mais doce que deveria... está vazio!') }}</p>
        <a href="{{ route('vitrine') }}" class="botao botao--chefe">{{ texto('carrinho', 'botao.voltar', 'Ver as gostosuras') }}</a>
    </div>
@else
    <div class="carrinho-layout">
        <ul class="lista-carrinho" id="lista-carrinho">
            @foreach($itens as $item)
                <li class="linha-carrinho" data-produto-id="{{ $item['produto']->id }}">
                    <div class="linha-carrinho__info">
                        <strong>{{ $item['produto']->nome }}</strong>
                        <span>{{ preco_br($item['produto']->preco) }} / un.</span>
                        @if($item['preco_mudou'])
                            <small class="aviso-preco-mudou">
                                {{ str_replace([':de', ':para'], [preco_br($item['preco_adicionado']), preco_br($item['produto']->preco)], texto('carrinho', 'aviso.preco_mudou', 'O valor mudou desde que você adicionou (era :de). O pedido usa o valor atual: :para.')) }}
                            </small>
                        @endif
                    </div>

                    <div class="contador" aria-label="{{ texto('carrinho', 'coluna.quantidade', 'Quantidade') }}">
                        <button type="button" class="contador__botao" data-acao="diminuir">&minus;</button>
                        <span class="contador__valor">{{ $item['quantidade'] }}</span>
                        <button type="button" class="contador__botao" data-acao="aumentar"
                                @if($item['produto']->estoque !== null && $item['quantidade'] >= $item['produto']->estoque) disabled title="{{ texto('carrinho', 'erro.estoque_maximo', 'Estoque máximo atingido.') }}" @endif
                        >+</button>
                    </div>

                    <strong class="linha-carrinho__subtotal">{{ preco_br($item['subtotal']) }}</strong>

                    <button type="button" class="linha-carrinho__remover" data-acao="remover" aria-label="{{ texto('carrinho', 'botao.remover', 'Remover') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-3 6h12l-1 12H7L6 9Z"/></svg>
                    </button>
                </li>
            @endforeach
        </ul>

        <aside class="resumo">
            <h2>{{ texto('carrinho', 'resumo.titulo', 'Resumo') }}</h2>
            <p class="resumo__linha">
                <span>{{ texto('carrinho', 'resumo.subtotal', 'Subtotal') }}</span>
                <strong id="resumo-subtotal">{{ preco_br($subtotal) }}</strong>
            </p>
            <p class="resumo__nota">{{ texto('carrinho', 'resumo.nota_taxa', 'A taxa de entrega é calculada no próximo passo.') }}</p>
            <a href="{{ route('checkout') }}" class="botao botao--chefe botao--grande bloco">{{ texto('carrinho', 'botao.finalizar', 'Finalizar pedido') }}</a>
            <a href="{{ route('vitrine') }}" class="link-voltar">{{ texto('carrinho', 'botao.continuar', 'Continuar comprando') }}</a>
        </aside>
    </div>
@endempty
@endsection

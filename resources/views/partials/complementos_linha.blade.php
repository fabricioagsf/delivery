@if(!empty($complementos))
    <ul class="lista-complementos-inline">
        @foreach($complementos as $c)
            <li class="{{ ($c['tipo'] ?? 'adicional') === 'remocao' ? 'remocao' : 'adicional' }}">
                @if(($c['tipo'] ?? 'adicional') === 'remocao')
                    {{ str_replace(':nome', $c['nome'] ?? '', texto('carrinho', 'comp_sem', 'sem :nome')) }}
                @else
                    {{ $c['nome'] ?? '' }} (+{{ preco_br($c['preco'] ?? 0) }})
                @endif
            </li>
        @endforeach
    </ul>
@endif

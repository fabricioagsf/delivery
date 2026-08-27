@if ($paginator->hasPages())
    <nav class="paginacao" aria-label="{{ texto('admin_layout', 'paginacao.rotulo', 'Paginação') }}">
        {{-- Página anterior --}}
        @if ($paginator->onFirstPage())
            <span class="paginacao__item paginacao__item--desabilitado">&laquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="paginacao__item" rel="prev">&laquo;</a>
        @endif

        {{-- Páginas numéricas --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="paginacao__item paginacao__item--desabilitado">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="paginacao__item paginacao__item--atual">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="paginacao__item">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Próxima página --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="paginacao__item" rel="next">&raquo;</a>
        @else
            <span class="paginacao__item paginacao__item--desabilitado">&raquo;</span>
        @endif
    </nav>
@endif

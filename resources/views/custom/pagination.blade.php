{{-- Bootstrap Rugby Pagination --}}
@if ($paginator->hasPages())
    <nav aria-label="Navegación de páginas">
        <ul class="pagination pagination-sm justify-content-center">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active">
                                <span class="page-link rugby-active">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>

        {{-- Results Info --}}
        <div class="text-center mt-2">
            <small class="text-muted">
                Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }}
                de {{ $paginator->total() }} resultados
            </small>
        </div>
    </nav>

    <style>
    /* Rugby pagination styles */
    .pagination .page-link {
        color: #1e4d2b;
        border-color: #dee2e6;
    }

    .pagination .page-item.active .page-link.rugby-active {
        background-color: #1e4d2b;
        border-color: #1e4d2b;
        color: white;
    }

    .pagination .page-link:hover {
        color: #28a745;
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }
    </style>
@endif
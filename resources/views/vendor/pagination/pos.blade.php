@if ($paginator->hasPages())
    <nav class="d-inline-flex align-items-center" aria-label="Pagination">
        <ul class="pagination pagination-sm mb-0 align-items-center gap-1">
            {{-- First Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="First">
                    <span class="page-link px-2" aria-hidden="true">
                        <i class="icon-base ti tabler-chevrons-left icon-xs"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link px-2" href="{{ $paginator->url(1) }}" rel="first" aria-label="First">
                        <i class="icon-base ti tabler-chevrons-left icon-xs"></i>
                    </a>
                </li>
            @endif

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="Previous">
                    <span class="page-link px-2" aria-hidden="true">
                        <i class="icon-base ti tabler-chevron-left icon-xs"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link px-2" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">
                        <i class="icon-base ti tabler-chevron-left icon-xs"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link px-2" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">
                        <i class="icon-base ti tabler-chevron-right icon-xs"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Next">
                    <span class="page-link px-2" aria-hidden="true">
                        <i class="icon-base ti tabler-chevron-right icon-xs"></i>
                    </span>
                </li>
            @endif

            {{-- Last Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link px-2" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label="Last">
                        <i class="icon-base ti tabler-chevrons-right icon-xs"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Last">
                    <span class="page-link px-2" aria-hidden="true">
                        <i class="icon-base ti tabler-chevrons-right icon-xs"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif


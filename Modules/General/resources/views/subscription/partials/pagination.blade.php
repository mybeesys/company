@if ($paginator->hasPages())
    <nav class="sub-pager" aria-label="Pagination">
        <div class="sub-pager__meta">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            /
            {{ $paginator->total() }}
        </div>
        <ul class="sub-pager__list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="sub-pager__btn is-disabled" aria-disabled="true">‹</span>
                @else
                    <a class="sub-pager__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>
                @endif
            </li>

            @php
                $window = 2;
                $start = max(1, $paginator->currentPage() - $window);
                $end = min($paginator->lastPage(), $paginator->currentPage() + $window);
            @endphp

            @foreach ($paginator->getUrlRange($start, $end) as $page => $url)
                <li>
                    @if ($page == $paginator->currentPage())
                        <span class="sub-pager__btn is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="sub-pager__btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                </li>
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="sub-pager__btn" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>
                @else
                    <span class="sub-pager__btn is-disabled" aria-disabled="true">›</span>
                @endif
            </li>
        </ul>
    </nav>
@endif

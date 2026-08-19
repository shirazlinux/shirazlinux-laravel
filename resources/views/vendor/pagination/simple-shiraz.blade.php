@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="صفحه‌بندی">
        <ul class="pager-list">
            @if ($paginator->onFirstPage())
                <li class="pager-item is-disabled" aria-disabled="true">
                    <span class="pager-link pager-nav"><span class="pager-txt">قبلی</span></span>
                </li>
            @else
                <li class="pager-item">
                    <a class="pager-link pager-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev"><span class="pager-txt">قبلی</span></a>
                </li>
            @endif

            @if ($paginator->hasMorePages())
                <li class="pager-item">
                    <a class="pager-link pager-nav" href="{{ $paginator->nextPageUrl() }}" rel="next"><span class="pager-txt">بعدی</span></a>
                </li>
            @else
                <li class="pager-item is-disabled" aria-disabled="true">
                    <span class="pager-link pager-nav"><span class="pager-txt">بعدی</span></span>
                </li>
            @endif
        </ul>
    </nav>
@endif

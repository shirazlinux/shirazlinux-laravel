@if ($paginator->hasPages())
    @php
        $fa = fn ($n) => \App\Support\JalaliDate::toPersianDigits((string) $n);
        $from = $paginator->firstItem();
        $to = $paginator->lastItem();
        $total = $paginator->total();
        $summary = ($from && $to)
            ? 'نمایش '.$fa($from).' تا '.$fa($to).' از '.$fa($total).' مورد'
            : '';
    @endphp
    <nav class="pager" role="navigation" aria-label="صفحه‌بندی">
        @if($summary !== '')
            <p class="pager-summary">{{ $summary }}</p>
        @endif
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

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="pager-item is-disabled" aria-disabled="true">
                        <span class="pager-link pager-ellipsis"><span class="pager-txt">…</span></span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pager-item is-active" aria-current="page">
                                <span class="pager-link"><span class="pager-txt">{{ $fa($page) }}</span></span>
                            </li>
                        @else
                            <li class="pager-item">
                                <a class="pager-link" href="{{ $url }}"><span class="pager-txt">{{ $fa($page) }}</span></a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

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

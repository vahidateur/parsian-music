{{-- Core server-side pagination. Props: paginator. Phase: 0.5. --}}
@props(['paginator'])

@if($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = $paginator->getUrlRange(max(1, $current - 2), min($last, $current + 2));
    @endphp
    <nav {{ $attributes->merge(['class' => 'ui-pagination']) }} aria-label="صفحه‌بندی">
        <ul class="ui-pagination__list">
            <li>
                @if($paginator->onFirstPage())
                    <span class="ui-pagination__link ui-pagination__link--disabled" aria-disabled="true">قبلی</span>
                @else
                    <a class="ui-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">قبلی</a>
                @endif
            </li>
            @foreach($pages as $page => $url)
                <li>
                    @if($page === $current)
                        <span class="ui-pagination__link ui-pagination__link--current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="ui-pagination__link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                </li>
            @endforeach
            <li>
                @if($paginator->hasMorePages())
                    <a class="ui-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">بعدی</a>
                @else
                    <span class="ui-pagination__link ui-pagination__link--disabled" aria-disabled="true">بعدی</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
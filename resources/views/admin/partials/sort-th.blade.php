{{--
    Sortable table header cell.
    Usage: @include('admin.partials.sort-th', ['col' => 'full_name', 'label' => __('admin.full_name'), 'currentSort' => $sortCol, 'currentDir' => $sortDir, 'route' => 'admin.students.index', 'params' => request()->except(['sort','direction','page'])])
--}}
@php
    $isActive  = ($currentSort ?? '') === $col;
    $nextDir   = ($isActive && ($currentDir ?? 'asc') === 'asc') ? 'desc' : 'asc';
    $linkParams = array_merge($params ?? request()->except(['sort','direction','page']), [
        'sort'      => $col,
        'direction' => $nextDir,
    ]);
@endphp
<th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 whitespace-nowrap">
    <a href="{{ route($route, $linkParams) }}"
       class="inline-flex items-center gap-1 hover:text-amber-300 transition {{ $isActive ? 'text-amber-300' : '' }}">
        {{ $label }}
        <span class="inline-flex flex-col gap-px leading-none">
            <svg class="h-2.5 w-2.5 {{ $isActive && ($currentDir ?? 'asc') === 'asc' ? 'text-amber-400' : 'opacity-30' }}" viewBox="0 0 10 6" fill="currentColor">
                <path d="M5 0L10 6H0z"/>
            </svg>
            <svg class="h-2.5 w-2.5 {{ $isActive && ($currentDir ?? 'asc') === 'desc' ? 'text-amber-400' : 'opacity-30' }}" viewBox="0 0 10 6" fill="currentColor">
                <path d="M5 6L0 0H10z"/>
            </svg>
        </span>
    </a>
</th>

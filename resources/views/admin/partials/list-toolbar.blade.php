{{--
    Shared Operational_List toolbar.

    Renders the search control and the server allow-listed filters with the
    currently applied normalized values, resubmits the active sort as hidden
    fields, and exposes the clear-filters control whenever a search or filter
    value is applied.

    Expects:
      $list         App\DTOs\OperationalListData
      $route        named route of the list index
    Optional:
      $searchable        bool   render the search input (default true)
      $searchLabel       string label of the search input
      $searchPlaceholder string placeholder of the search input
      $filters           array<string, array{label: string, all: string}>
--}}
@php
    $searchable = $searchable ?? true;
    $searchLabel = $searchLabel ?? __('admin.search');
    $searchPlaceholder = $searchPlaceholder ?? __('admin.search_placeholder');
    $filters = $filters ?? [];
    $entity = $list->context->entity;

    $listInput = 'block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20';
    $listLabel = 'mb-1.5 block text-xs font-medium text-gray-400';
    $listSubmit = 'inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30';
    $listClear = 'inline-flex items-center rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-200 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/20';
@endphp

<form method="GET" action="{{ route($route) }}"
      class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end"
      role="search" aria-label="{{ __('admin.filters_label') }}">
    {{-- Active sort / page size travel with the filter submission. --}}
    @foreach ($list->formParameters() as $parameter => $value)
        <input type="hidden" name="{{ $parameter }}" value="{{ $value }}">
    @endforeach

    @if ($searchable)
        <div>
            <label for="{{ $entity }}-search" class="{{ $listLabel }}">{{ $searchLabel }}</label>
            <input id="{{ $entity }}-search" type="search" name="search"
                   value="{{ $list->context->search }}" maxlength="100"
                   placeholder="{{ $searchPlaceholder }}" class="{{ $listInput }}">
        </div>
    @endif

    @foreach ($filters as $filterName => $filter)
        <div>
            <label for="{{ $entity }}-{{ $filterName }}" class="{{ $listLabel }}">{{ $filter['label'] }}</label>
            <select id="{{ $entity }}-{{ $filterName }}" name="{{ $filterName }}" class="{{ $listInput }}">
                <option value="">{{ $filter['all'] }}</option>
                @foreach ($list->renderableOptions($filterName) as $option)
                    <option value="{{ $option['value'] }}" @selected($option['selected'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>
    @endforeach

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="{{ $listSubmit }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 0z"/>
            </svg>
            {{ __('admin.filter') }}
        </button>
        @if ($list->has_active_context)
            <a href="{{ route($route) }}" class="{{ $listClear }}" data-list-clear="{{ $entity }}">
                {{ __('admin.clear_filters') }}
            </a>
        @endif
    </div>
</form>

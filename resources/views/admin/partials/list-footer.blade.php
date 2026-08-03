{{--
    Shared Operational_List footer: total matching record count plus pagination
    links. The paginator already carries the full normalized List_Context, so
    every page link preserves search, filters, sort, direction and page size.

    Expects: $list App\DTOs\OperationalListData
--}}
<div class="mt-5 flex flex-col items-center gap-3">
    <p class="text-xs text-gray-500 tabular-nums" data-list-total="{{ $list->total }}">
        {{ __('admin.results_total', ['count' => $list->total]) }}
    </p>

    @if ($list->paginator->hasPages())
        {{ $list->paginator->links() }}
    @endif
</div>

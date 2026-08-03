{{--
    Shared bulk-delete confirmation dialog.
    Props: name, entityLabel, selectionContext, executionEndpoint.
    Composes the existing x-modal confirmation contract; it is not a modal replacement.
    Phase: Admin bulk selection — task 3.1.
--}}
@props([
    'name' => 'bulk-delete-confirmation',
    'entity' => null,
    'entityLabel',
    'selectionContext' => null,
    'executionEndpoint',
])

<x-modal
    :name="$name"
    variant="confirmation"
    :entity="$entityLabel"
    :action="__('admin.delete')"
    :consequence="__('admin.bulk_selection.delete_warning')"
>
    <div
        data-bulk-confirmation
        data-bulk-entity="{{ $entity }}"
        data-bulk-modal-name="{{ $name }}"
        data-bulk-execution-endpoint="{{ $executionEndpoint }}"
        data-bulk-filter-context="{{ json_encode($selectionContext?->toArray(), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    >
        <p data-bulk-confirmation-count class="text-sm text-gray-300" aria-live="polite" aria-atomic="true">
            {{ __('admin.bulk_selection.selected_count', ['count' => 0]) }}
        </p>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" data-bulk-confirm-cancel class="admin-bulk__control rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">
                {{ __('admin.cancel') }}
            </button>
            <button type="button" data-bulk-confirm-delete class="admin-bulk__control rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300">
                {{ __('admin.confirm') }}
            </button>
        </div>
    </div>
</x-modal>

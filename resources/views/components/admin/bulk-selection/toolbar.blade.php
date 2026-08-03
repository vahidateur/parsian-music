{{--
    Shared bulk-selection toolbar.
    Props: entity, entityLabel, rows, selectionContext, previewEndpoint, executionEndpoint.
    The owner Alpine module consumes the data contract; this component only renders.
    Phase: Admin bulk selection — task 3.1.
--}}
@props([
    'entity',
    'entityLabel',
    'rows' => [],
    'selectionContext' => null,
    'previewEndpoint',
    'executionEndpoint',
])

<section
    x-data="bulkSelectionState"
    data-bulk-selection-toolbar
    data-bulk-entity="{{ $entity }}"
    data-bulk-preview-endpoint="{{ $previewEndpoint }}"
    data-bulk-execution-endpoint="{{ $executionEndpoint }}"
    data-bulk-visible-ids="{{ collect($rows)->pluck('id')->values()->toJson() }}"
    data-bulk-selectable-ids="{{ collect($rows)->filter(fn ($row) => $row->selectable)->pluck('id')->values()->toJson() }}"
    data-bulk-filter-context="{{ json_encode($selectionContext?->toArray(), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    data-bulk-messages="{{ json_encode([
        'pending' => __('admin.state_submitting'),
        'previewing' => __('admin.bulk_selection.previewing'),
        'previewReady' => __('admin.bulk_selection.preview_ready'),
        'selectedCount' => __('admin.bulk_selection.selected_count'),
        'complete' => __('admin.bulk_selection.result_complete'),
        'partial' => __('admin.bulk_selection.result_partial'),
        'error' => __('admin.bulk_errors.unavailable'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    aria-label="{{ __('admin.bulk_selection.toolbar_label', ['entity' => $entityLabel]) }}"
>
    <p data-bulk-live-result class="sr-only" role="status" aria-live="polite" aria-atomic="true" hidden></p>
    <p data-bulk-live-error class="sr-only" role="alert" aria-live="assertive" aria-atomic="true" hidden></p>
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-800/60 bg-gray-800/20 px-4 py-3">
        <p class="text-sm text-gray-300" aria-live="polite">
            <span data-bulk-selected-count>0</span>
            {{ __('admin.bulk_selection.selected_suffix') }}
        </p>

        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('admin.bulk_selection.actions_label') }}">
            @foreach (['activate', 'deactivate', 'delete'] as $action)
                <button
                    type="button"
                    data-bulk-action="{{ $action }}"
                    class="admin-bulk__control rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-2 text-xs font-medium text-gray-400 transition duration-200 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ __('admin.' . $action) }}
                </button>
            @endforeach
            <button
                type="button"
                data-bulk-all-filtered
                class="admin-bulk__control rounded-lg border border-amber-500/40 px-3 py-2 text-xs font-medium text-amber-300 transition duration-200 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ __('admin.bulk_selection.all_filtered') }}
            </button>
        </div>
    </div>
</section>

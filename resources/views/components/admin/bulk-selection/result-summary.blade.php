{{--
    Shared bulk-result summary.
    Props: entityLabel and resultEndpoint for owner-module recovery wiring.
    Phase: Admin bulk selection — task 3.1.
--}}
@props([
    'entity' => null,
    'entityLabel',
    'resultEndpoint' => null,
])

<section
    data-bulk-result-summary
    data-bulk-entity="{{ $entity ?? '' }}"
    hidden
    role="status"
    aria-live="polite"
    aria-label="{{ __('admin.bulk_selection.result_label', ['entity' => $entityLabel]) }}"
    class="rounded-xl border border-gray-800/60 bg-gray-800/20 px-4 py-3"
>
    <h2 class="text-sm font-semibold text-amber-100">{{ __('admin.bulk_selection.result_title') }}</h2>
    <p data-bulk-result-message class="mt-1 text-sm text-gray-300"></p>
    <ul data-bulk-result-items class="mt-2 space-y-1 text-sm text-gray-400"></ul>
    <button type="button" data-bulk-result-retry hidden class="mt-3 inline-flex text-sm font-medium text-amber-300">
        {{ __('admin.state_error_retry') }}
    </button>
    <a data-bulk-result-recovery href="{{ $resultEndpoint ?? '#' }}" class="mt-3 inline-flex text-sm font-medium text-amber-300">
        {{ __('admin.bulk_selection.recovery') }}
    </a>
</section>

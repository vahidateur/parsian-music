{{--
    Empty_State of an Operational_List — the single shared contract for both
    empty modes of the admin panel.

    The mode is never recomputed here: it comes from the server-side list
    contract (`OperationalListData::$empty_mode`), so Blade only renders it.

    Props:
    - list          : App\DTOs\OperationalListData
    - route         : named route of the list index (used by clear-filters)
    - createRoute   : named route of the create entry point (optional)
    - createLabel   : label of the create entry point
    - message       : message of the `no_records` mode (entity specific)
    - matchesMessage: message of the `no_matches` mode
    - compact       : render the compact empty-state variant (default true)

    Contract:
    - renders nothing while the list has at least one matching record
    - `no_records`  → "no record exists" plus the create entry point, and only
                      when the server-side policy flag allows `create`; hiding
                      the control never replaces the server-side check
    - `no_matches`  → "nothing matches this context" plus the clear-filters
                      control that returns the list to its default context
    - `data-empty-state` exposes the mode for tests and assistive tooling

    Requirements: 7.1, 7.2, 7.3
    Phase: Operational UX baseline — task 11.2.
--}}
@props([
    'list',
    'route',
    'createRoute' => null,
    'createLabel' => null,
    'message' => null,
    'matchesMessage' => null,
    'compact' => true,
])

@if ($list->isEmpty())
    @php
        $noMatches = $list->empty_mode === \App\DTOs\OperationalListData::EMPTY_MODE_NO_MATCHES;
        $stateMessage = $noMatches
            ? ($matchesMessage ?? __('admin.no_results_for_query'))
            : ($message ?? __('admin.empty_no_records'));
        $showCreate = ! $noMatches && $createRoute !== null && $list->allows('create');
    @endphp

    <x-dashboard.empty-state
        :compact="$compact"
        :message="$stateMessage"
        class="admin-empty"
        data-empty-state="{{ $list->empty_mode }}"
        data-empty-entity="{{ $list->context->entity }}">
        @isset($icon)
            <x-slot:icon>{{ $icon }}</x-slot:icon>
        @endisset

        @if ($showCreate || $noMatches)
            <x-slot:action>
                @if ($showCreate)
                    <a href="{{ route($createRoute) }}" class="admin-empty__action" data-empty-create>
                        {{ $createLabel ?? __('admin.create') }}
                    </a>
                @else
                    <a href="{{ route($route) }}" class="admin-empty__action" data-empty-clear>
                        {{ __('admin.clear_filters') }}
                    </a>
                @endif
            </x-slot:action>
        @endif
    </x-dashboard.empty-state>
@endif

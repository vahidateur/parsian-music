@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.teachers') }}@endsection

@section('content')
@php
    $btnPrimary   = "inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900";
    $btnSecondary = "inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30";
    $btnClear     = "rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-200 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/20";
    $inputClass   = "block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20";
    $statusBadge  = ['active' => 'bg-emerald-500/10 text-emerald-400', 'inactive' => 'bg-gray-700/50 text-gray-400'];
@endphp

{{-- Header --}}
<x-dashboard.section-header
    :title="__('admin.teachers')"
    :subtitle="__('admin.manage_teachers')"
    :badge="$teachers->total() . ' ' . __('admin.total')"
>
    <x-slot:actions>
        <a href="{{ route('admin.teachers.create') }}" class="{{ $btnPrimary }}" aria-label="{{ __('admin.create_teacher') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            {{ __('admin.create_teacher') }}
        </a>
    </x-slot:actions>
</x-dashboard.section-header>

{{-- Flash --}}
@if (session('success'))
    <x-dashboard.alert-card class="mb-5" :title="session('success')" priority="success" />
@endif
@if (session('error'))
    <x-dashboard.alert-card class="mb-5" :title="session('error')" priority="high" />
@endif

{{-- Filters --}}
<x-dashboard.chart-container class="mb-5" title="{{ __('admin.search') }}" aria-label="فیلتر اساتید">
    <form method="GET" action="{{ route('admin.teachers.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end" role="search">
        <div>
            <label for="filter-name" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.full_name') }}</label>
            <input id="filter-name" type="text" name="full_name" value="{{ request('full_name') }}"
                   placeholder="{{ __('admin.search_name') }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label for="filter-phone" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.phone') }}</label>
            <input id="filter-phone" type="tel" name="phone" value="{{ request('phone') }}"
                   placeholder="{{ __('admin.search_phone') }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label for="filter-status" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.status') }}</label>
            <select id="filter-status" name="status" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_statuses') }}</option>
                @foreach (['active', 'inactive'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ __('admin.statuses.'.$s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="{{ $btnSecondary }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 0z"/></svg>
                {{ __('admin.filter') }}
            </button>
            <a href="{{ route('admin.teachers.index') }}" class="{{ $btnClear }}">{{ __('admin.clear') }}</a>
        </div>
    </form>
</x-dashboard.chart-container>

{{-- Table --}}
<x-dashboard.chart-container
    :title="__('admin.teachers')"
    :badge="$teachers->count() . ' / ' . $teachers->total()"
>
    @if ($teachers->count())
        <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6">
            <table class="w-full text-start text-sm" role="table">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        @include('admin.partials.sort-th', ['col'=>'full_name', 'label'=>__('admin.full_name'), 'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.teachers.index'])
                        @include('admin.partials.sort-th', ['col'=>'phone',     'label'=>__('admin.phone'),     'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.teachers.index'])
                        @include('admin.partials.sort-th', ['col'=>'status',    'label'=>__('admin.status'),    'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.teachers.index'])
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.instruments') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($teachers as $teacher)
                        @php
                            $sv = $teacher->status instanceof \BackedEnum ? $teacher->status->value : (string) $teacher->status;
                        @endphp
                        <tr class="transition duration-150 hover:bg-gray-800/25">
                            <td class="px-5 py-3.5 font-medium text-gray-100">{{ $teacher->full_name }}</td>
                            <td class="px-5 py-3.5 text-gray-400 tabular-nums" dir="ltr">{{ $teacher->phone }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge[$sv] ?? 'bg-gray-700/50 text-gray-400' }}">
                                    {{ __('admin.statuses.'.$sv) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($teacher->instruments as $instrument)
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $instrument->pivot->is_primary
                                                ? 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-500/30'
                                                : 'bg-gray-700/40 text-gray-400' }}">
                                            {{ $instrument->name_fa ?: $instrument->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-600">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-4">
                                    <a href="{{ route('admin.teachers.instruments', $teacher) }}"
                                       class="text-xs font-medium text-sky-400 transition duration-150 hover:text-sky-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-sky-500/60">
                                        {{ __('admin.instruments') }}
                                    </a>
                                    <a href="{{ route('admin.teachers.edit', $teacher) }}"
                                       class="text-xs font-medium text-amber-400 transition duration-150 hover:text-amber-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500/60">
                                        {{ __('admin.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" class="inline"
                                          onsubmit="return confirm('{{ __('admin.delete_teacher_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="text-xs font-medium text-red-400 transition duration-150 hover:text-red-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-red-500/60">
                                            {{ __('admin.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-dashboard.empty-state :message="__('admin.no_teachers_found')" compact>
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                </svg>
            </x-slot:icon>
        </x-dashboard.empty-state>
    @endif
</x-dashboard.chart-container>

{{-- Pagination --}}
@if ($teachers->hasPages())
    <div class="mt-5 flex justify-center">
        {{ $teachers->links() }}
    </div>
@endif

@endsection

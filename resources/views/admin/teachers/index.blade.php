@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.teachers') }}@endsection
@section('content')

<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.teachers') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('admin.manage_teachers') }}</p>
    </div>
    <a href="{{ route('admin.teachers.create') }}" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
        {{ __('admin.create_teacher') }}
    </a>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('admin.teachers.index') }}" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3 lg:items-end">
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.full_name') }}</label>
        <input type="text" name="full_name" value="{{ request('full_name') }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="{{ __('admin.search_name') }}">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.phone') }}</label>
        <input type="tel" name="phone" value="{{ request('phone') }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="{{ __('admin.search_phone') }}">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.status') }}</label>
        <select name="status" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.all_statuses') }}</option>
            @foreach (['active', 'inactive'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                    {{ __('admin.statuses.'.$s) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">{{ __('admin.filter') }}</button>
        <a href="{{ route('admin.teachers.index') }}" class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition hover:text-gray-300">{{ __('admin.clear') }}</a>
    </div>
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <div class="max-h-[70vh] overflow-y-auto">
            <table class="w-full text-start text-sm">
                <thead>
                    <tr class="sticky top-0 z-10 border-b border-gray-800/60 bg-gray-800/30">
                        @include('admin.partials.sort-th', ['col'=>'full_name', 'label'=>__('admin.full_name'), 'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.teachers.index'])
                        @include('admin.partials.sort-th', ['col'=>'phone',     'label'=>__('admin.phone'),     'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.teachers.index'])
                        @include('admin.partials.sort-th', ['col'=>'status',    'label'=>__('admin.status'),    'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.teachers.index'])
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.instruments') }}</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @forelse ($teachers as $teacher)
                        @php
                            $statusValue = $teacher->status instanceof \BackedEnum ? $teacher->status->value : (string) $teacher->status;
                        @endphp
                        <tr class="transition hover:bg-gray-800/20 {{ $loop->even ? 'bg-gray-900/30' : 'bg-gray-900/50' }}">
                            <td class="px-6 py-4 font-medium text-gray-100">{{ $teacher->full_name }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ $teacher->phone }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full {{ $statusValue === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">
                                    {{ __('admin.statuses.'.$statusValue) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($teacher->instruments as $instrument)
                                        <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-medium text-amber-300
                                                     {{ $instrument->pivot->is_primary ? 'ring-1 ring-amber-500/40' : '' }}">
                                            {{ $instrument->name_fa ?: $instrument->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-600">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.teachers.instruments', $teacher) }}" class="text-amber-400 transition hover:text-amber-300">
                                        {{ __('admin.instruments') }}
                                    </a>
                                    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-amber-400 transition hover:text-amber-300">
                                        {{ __('admin.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" class="inline" onsubmit="return confirm('{{ __('admin.delete_teacher_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 transition hover:text-red-300">
                                            {{ __('admin.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_teachers_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($teachers->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $teachers->links() }}
    </div>
@endif

@endsection

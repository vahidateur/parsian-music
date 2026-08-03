@extends('layouts.dashboard')

@section('content')
@php
    $sortParams = $list->sortParameters();
@endphp

<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.instruments') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('admin.manage_instruments') }}</p>
    </div>
    @if ($list->allows('create'))
    <a href="{{ route('admin.instruments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        {{ __('admin.new_instrument') }}
    </a>
    @endif
</div>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.instruments.index')" />

{{-- Search + filters --}}
<div class="mb-6">
    @include('admin.partials.list-toolbar', [
        'list' => $list,
        'route' => 'admin.instruments.index',
        'searchLabel' => __('admin.instrument_name_fa'),
        'filters' => [
            'is_active' => ['label' => __('admin.status'), 'all' => __('admin.all_statuses')],
        ],
    ])
</div>

<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    @include('admin.partials.sort-th', ['col'=>'name_fa', 'label'=>__('admin.instrument_name_fa'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.instruments.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'name', 'label'=>__('admin.instrument_name_en'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.instruments.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'is_active', 'label'=>__('admin.status'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.instruments.index', 'params'=>$sortParams])
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($list->rows as $row)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-4 font-medium text-gray-100">{{ $row->field('name_fa') ?: '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $row->field('name') }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full {{ $row->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">
                                {{ $row->status === 'active' ? __('admin.active') : __('admin.inactive') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            @if ($row->allows('update'))
                                <a href="{{ route('admin.instruments.edit', $row->id) }}" class="text-amber-400 transition hover:text-amber-300">{{ __('admin.edit') }}</a>
                            @endif

                            @if ($row->allows('toggle'))
                            <form method="POST" action="{{ route('admin.instruments.toggle', $row->id) }}" class="inline ml-3">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="{{ $row->status === 'active' ? 'text-gray-400 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300' }} transition text-xs">
                                    {{ $row->status === 'active' ? __('admin.deactivate') : __('admin.activate') }}
                                </button>
                            </form>
                            @endif

                            @if ($row->allows('delete'))
                            <button type="button" x-on:click="$dispatch('open-modal', 'confirm-instrument-delete-{{ $row->id }}')" class="text-red-400 transition hover:text-red-300">{{ __('admin.delete') }}</button>
                            <x-modal name="confirm-instrument-delete-{{ $row->id }}" variant="confirmation"
                                     :entity="$row->label"
                                     :action="__('admin.delete')"
                                     :consequence="__('admin.confirmation_consequence_irreversible')">
                                <x-admin.form-state>
                                    <form method="POST" action="{{ route('admin.instruments.destroy', $row->id) }}">
                                        @csrf @method('DELETE')
                                        <div class="flex justify-end gap-2">
                                            <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">{{ __('admin.cancel') }}</button>
                                            <button type="submit" x-bind:disabled="pending" x-bind:aria-busy="pending" class="rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300 disabled:cursor-not-allowed disabled:opacity-60">{{ __('admin.confirm') }}</button>
                                        </div>
                                    </form>
                                </x-admin.form-state>
                            </x-modal>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            {{-- Shared Empty_State: mode comes from the server-side list contract --}}
                            <x-admin.list-empty
                                :list="$list"
                                route="admin.instruments.index"
                                createRoute="admin.instruments.create"
                                :createLabel="__('admin.new_instrument')"
                                :message="__('admin.no_instruments_found')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Total count + context-preserving pagination --}}
@include('admin.partials.list-footer', ['list' => $list])

@endsection

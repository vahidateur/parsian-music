@extends('layouts.dashboard')

@section('title', 'اتاق‌های کلاس')

@section('content')
@php
    $sortParams = $list->sortParameters();
@endphp
<div class="bg-gradient-to-b from-slate-900 to-slate-950 p-8">
    <div class="mx-auto max-w-6xl">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white">اتاق‌های کلاس</h1>
                <p class="mt-2 text-slate-400">مدیریت اتاق‌های آموزشی</p>
            </div>
            @if ($list->allows('create'))
            <a href="{{ route('admin.rooms.create') }}" 
               class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                اتاق جدید
            </a>
            @endif
        </div>

        {{-- Feedback_Channel + Error_State recovery path --}}
        <x-admin.feedback :returnUrl="route('admin.rooms.index')" />

        <!-- Search + filters -->
        <div class="mb-6">
            @include('admin.partials.list-toolbar', [
                'list' => $list,
                'route' => 'admin.rooms.index',
                'searchLabel' => __('admin.room_name'),
                'filters' => [
                    'is_active' => ['label' => __('admin.status'), 'all' => __('admin.all_statuses')],
                ],
            ])
        </div>

        <!-- Table -->
        <div class="rounded-lg border border-slate-700 bg-slate-800/50 backdrop-blur-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-700 bg-slate-700/30">
                            @include('admin.partials.sort-th', ['col'=>'name', 'label'=>__('admin.room_name'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.rooms.index', 'params'=>$sortParams])
                            @include('admin.partials.sort-th', ['col'=>'capacity', 'label'=>__('admin.room_capacity'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.rooms.index', 'params'=>$sortParams])
                            @include('admin.partials.sort-th', ['col'=>'is_active', 'label'=>__('admin.status'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.rooms.index', 'params'=>$sortParams])
                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-300">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($list->rows as $row)
                            <tr class="hover:bg-slate-700/20 transition">
                                <td class="px-6 py-4 text-sm text-white font-medium">{{ $row->label }}</td>
                                <td class="px-6 py-4 text-sm text-slate-300">{{ $row->field('capacity') ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if($row->status === 'active')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-500/20 px-2.5 py-0.5 text-xs font-medium text-green-300">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-400"></span>
                                            فعال
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-500/20 px-2.5 py-0.5 text-xs font-medium text-slate-300">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            غیرفعال
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        @if ($row->allows('update'))
                                        <a href="{{ route('admin.rooms.edit', $row->id) }}"
                                           class="text-amber-400 hover:text-amber-300 transition">
                                            {{ __('admin.edit') }}
                                        </a>
                                        @endif
                                        @if ($row->allows('delete'))
                                        <button type="button"
                                                x-on:click="$dispatch('open-modal', 'confirm-room-delete-{{ $row->id }}')"
                                                class="text-red-400 hover:text-red-300 transition">
                                            {{ __('admin.delete') }}
                                        </button>
                                        <x-modal name="confirm-room-delete-{{ $row->id }}" variant="confirmation"
                                                 :entity="$row->label"
                                                 :action="__('admin.delete')"
                                                 :consequence="__('admin.confirmation_consequence_irreversible')">
                                            <x-admin.form-state>
                                                <form action="{{ route('admin.rooms.destroy', $row->id) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">{{ __('admin.cancel') }}</button>
                                                        <button type="submit" x-bind:disabled="pending" x-bind:aria-busy="pending" class="rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300 disabled:cursor-not-allowed disabled:opacity-60">{{ __('admin.confirm') }}</button>
                                                    </div>
                                                </form>
                                            </x-admin.form-state>
                                        </x-modal>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    {{-- Shared Empty_State: mode comes from the server-side list contract --}}
                                    <x-admin.list-empty
                                        :list="$list"
                                        route="admin.rooms.index"
                                        createRoute="admin.rooms.create"
                                        :createLabel="__('admin.create_room')"
                                        :message="__('admin.no_rooms_found')" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Total count + context-preserving pagination -->
        @include('admin.partials.list-footer', ['list' => $list])
    </div>
</div>
@endsection

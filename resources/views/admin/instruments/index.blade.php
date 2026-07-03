@extends('layouts.dashboard')

@section('content')

<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.instruments') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('admin.manage_instruments') }}</p>
    </div>
    <a href="{{ route('admin.instruments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        {{ __('admin.new_instrument') }}
    </a>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
        {{ session('error') }}
    </div>
@endif

<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.instrument_name_fa') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.instrument_name_en') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.status') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($instruments as $instrument)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-4 font-medium text-gray-100">{{ $instrument->name_fa ?: '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $instrument->name }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full {{ $instrument->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">
                                {{ $instrument->is_active ? __('admin.active') : __('admin.inactive') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.instruments.edit', $instrument) }}" class="text-amber-400 transition hover:text-amber-300">{{ __('admin.edit') }}</a>

                            <form method="POST" action="{{ route('admin.instruments.toggle', $instrument) }}" class="inline ml-3">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="{{ $instrument->is_active ? 'text-gray-400 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300' }} transition text-xs">
                                    {{ $instrument->is_active ? __('admin.deactivate') : __('admin.activate') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.instruments.destroy', $instrument) }}" class="inline ml-3"
                                  onsubmit="return confirm('{{ __('admin.delete_instrument_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 transition hover:text-red-300">{{ __('admin.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_instruments_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

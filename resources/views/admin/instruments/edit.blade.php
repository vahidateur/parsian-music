@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="{{ route('admin.instruments.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm text-gray-400 transition hover:text-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        {{ __('admin.instruments') }}
    </a>
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.edit_instrument') }}</h1>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
        <ul class="list-disc pr-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.instruments.update', $instrument) }}" class="max-w-xl space-y-5">
    @csrf
    @method('PUT')

    {{-- Persian name --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.instrument_name_fa') }}</label>
        <input type="text" name="name_fa" value="{{ old('name_fa', $instrument->name_fa) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="مثلاً: پیانو">
        @error('name_fa')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- English name --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.instrument_name_en') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <input type="text" name="name" value="{{ old('name', $instrument->name) }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="e.g. Piano">
    </div>

    {{-- is_active --}}
    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $instrument->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
               class="h-4 w-4 rounded border-gray-600 bg-gray-800 text-amber-500 focus:ring-amber-500/20">
        <label for="is_active" class="text-sm text-gray-300">{{ __('admin.active') }}</label>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            {{ __('admin.save') }}
        </button>
        <a href="{{ route('admin.instruments.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>

@endsection

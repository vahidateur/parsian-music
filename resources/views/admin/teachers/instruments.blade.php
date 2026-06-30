@extends('layouts.dashboard')

@section('content')

{{-- Back + Actions --}}
<div class="mb-8 flex items-center justify-between">
    <a href="{{ route('admin.teachers.index') }}" class="text-sm text-gray-400 transition hover:text-gray-200">← Back to Teachers</a>
    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">Edit Teacher</a>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

{{-- Section 1: Teacher Info --}}
<div class="mb-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">Teacher Information</h2>
    </div>
    <div class="grid grid-cols-1 gap-6 px-6 py-6 sm:grid-cols-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Full Name</p>
            <p class="mt-1 text-sm text-gray-100">{{ $teacher->full_name }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Phone</p>
            <p class="mt-1 text-sm text-gray-100">{{ $teacher->phone }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Status</p>
            <span class="mt-1 inline-block rounded-full {{ (string) $teacher->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">
                {{ ucfirst((string) $teacher->status) }}
            </span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Section 2: Assigned Instruments --}}
    <section class="xl:col-span-2 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <h2 class="text-lg font-semibold text-amber-100">Assigned Instruments</h2>
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">{{ $teacher->instruments->count() }} total</span>
        </div>

        @if ($teacher->instruments->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-800/60 bg-gray-800/30">
                            <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Instrument</th>
                            <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Skill Level</th>
                            <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Primary</th>
                            <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        @foreach ($teacher->instruments as $instrument)
                            <tr class="transition hover:bg-gray-800/20">
                                <td class="px-6 py-4 font-medium text-gray-100">{{ $instrument->name }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ ucfirst($instrument->pivot->skill_level ?? '') ?: '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($instrument->pivot->is_primary)
                                        <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-medium text-amber-300">Primary</span>
                                    @else
                                        <span class="text-xs text-gray-600">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('admin.teachers.detachInstrument', $teacher) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="instrument_id" value="{{ $instrument->id }}">
                                        <button type="submit" class="text-red-400 transition hover:text-red-300">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center text-gray-500">
                No instruments assigned yet.
            </div>
        @endif
    </section>

    {{-- Section 3: Add Instrument Form --}}
    <section class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="border-b border-gray-800/60 px-6 py-4">
            <h2 class="text-base font-semibold text-amber-100">Add Instrument</h2>
        </div>
        <form method="POST" action="{{ route('admin.teachers.attachInstrument', $teacher) }}" class="space-y-5 px-6 py-6">
            @csrf

            {{-- instrument_id --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-300">Instrument</label>
                <select name="instrument_id" required
                        class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    <option value="">Select instrument...</option>
                    @foreach ($allInstruments as $instrument)
                        <option value="{{ $instrument->id }}" {{ old('instrument_id') == $instrument->id ? 'selected' : '' }}>{{ $instrument->name }}</option>
                    @endforeach
                </select>
                @error('instrument_id')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- skill_level --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-300">Skill Level</label>
                <select name="skill_level" required
                        class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    <option value="">Select level...</option>
                    @foreach (['beginner', 'intermediate', 'advanced', 'expert'] as $level)
                        <option value="{{ $level }}" {{ old('skill_level') === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
                @error('skill_level')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- is_primary --}}
            <div class="flex items-center gap-2.5">
                <input type="checkbox" name="is_primary" value="1" id="is_primary" {{ old('is_primary') ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-600 bg-gray-800 text-amber-500 focus:ring-amber-500/20">
                <label for="is_primary" class="text-sm text-gray-300">Set as primary instrument</label>
            </div>
            <p class="text-xs text-gray-500">Only one instrument can be primary. Setting this will unset the existing primary.</p>

            {{-- Submit --}}
            <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-3 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
                Assign Instrument
            </button>
        </form>
    </section>
</div>

@endsection

@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">Edit Student</h1>
    <p class="mt-1 text-sm text-gray-500">Update student information.</p>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.students.update', $student) }}" class="max-w-2xl space-y-5">
    @csrf
    @method('PUT')

    {{-- full_name --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Full Name</label>
        <input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="Student full name">
        @error('full_name')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- phone --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Phone</label>
        <input type="tel" name="phone" value="{{ old('phone', $student->phone) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="09123456789">
        @error('phone')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- parent_phone --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Parent Phone</label>
        <input type="tel" name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="Optional">
        @error('parent_phone')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- status --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Status</label>
        <select name="status" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="active" {{ (string) old('status', $student->status) === 'active' ? 'selected' : '' }}>Active</option>
            <option value="paused" {{ (string) old('status', $student->status) === 'paused' ? 'selected' : '' }}>Paused</option>
            <option value="inactive" {{ (string) old('status', $student->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="graduated" {{ (string) old('status', $student->status) === 'graduated' ? 'selected' : '' }}>Graduated</option>
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- join_date --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Join Date</label>
        <input type="date" name="join_date" value="{{ old('join_date', $student->join_date?->format('Y-m-d')) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @error('join_date')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- notes --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Notes</label>
        <textarea name="notes" rows="4"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="Optional notes">{{ old('notes', $student->notes) }}</textarea>
        @error('notes')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            Update Student
        </button>
        <a href="{{ route('admin.students.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            Cancel
        </a>
    </div>
</form>

@endsection

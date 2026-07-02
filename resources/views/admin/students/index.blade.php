@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-amber-100">Students</h1>
        <a href="{{ route('admin.students.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            New Student
        </a>
    </div>
</div>

{{-- Success Message --}}
@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

{{-- Search --}}
<form method="GET" action="{{ route('admin.students.index') }}" class="mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">Name</label>
        <input type="text" name="full_name" value="{{ request('full_name') }}" placeholder="Search name..."
               class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.phone') }}</label>
        <input type="text" name="phone" value="{{ request('phone') }}" placeholder="Search phone..."
               class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
    </div>
    <button type="submit" class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">Search</button>
    <a href="{{ route('admin.students.index') }}" class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition hover:text-gray-300">Clear</a>
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-gray-800/60 bg-gray-800/30">
                <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Phone</th>
                <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Join Date</th>
                <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800/60">
            @forelse ($students as $student)
                @php
                    $statusValue = $student->status instanceof \BackedEnum ? $student->status->value : (string) $student->status;
                @endphp
                <tr class="transition hover:bg-gray-800/20">
                    <td class="px-6 py-4 font-medium text-gray-100">{{ $student->full_name }}</td>
                    <td class="px-6 py-4 text-gray-400">{{ $student->phone }}</td>
                    <td class="px-6 py-4">
                        <span class="rounded-full {{ $statusValue === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">
                            {{ ucfirst($statusValue) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-400">{{ $student->join_date->format('Y/m/d') }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.students.edit', $student) }}" class="text-amber-400 transition hover:text-amber-300">Edit</a>
                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="inline ml-3" onsubmit="return confirm('Delete this student?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 transition hover:text-red-300">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">No students found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if ($students->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $students->withQueryString()->links() }}
    </div>
@endif

@endsection

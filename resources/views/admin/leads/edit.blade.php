@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.leads') }}@endsection

@section('content')
@php
    $btnPrimary   = "inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900";
    $btnSecondary = "inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30";
    $inputClass   = "block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20";
@endphp

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.edit_lead') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.update_lead_desc') }}</p>
</div>

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
        <ul class="list-disc pr-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="max-w-2xl space-y-5">
    @csrf
    @method('PUT')
    @include('admin.leads.partials.form-fields', compact('instruments', 'teachers', 'assignees') + ['lead' => $lead])

    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="{{ $btnPrimary }}">
            {{ __('admin.update_lead') }}
        </button>
        <a href="{{ route('admin.leads.show', $lead) }}" class="{{ $btnSecondary }}">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>

@endsection

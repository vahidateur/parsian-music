@extends('layouts.dashboard')

@section('title', __('admin.new_invoice'))

@section('content')

<div class="mb-8">
    <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 transition hover:text-gray-300">{{ __('admin.back_to_invoices') }}</a>
    <h1 class="mt-3 text-2xl font-semibold text-amber-100">{{ __('admin.new_invoice') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.new_invoice_desc') }}</p>
</div>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.invoices.index')" />

@include('admin.invoices.form', [
    'action'       => route('admin.invoices.store'),
    'method'       => 'POST',
    'submitLabel'  => __('admin.create_invoice'),
    'students'     => $students,
    'enrollments'  => $enrollments,
    'itemsSeed'    => old('items', []),
    'taxSeed'      => (float) old('tax', 0),
    'studentId'    => $selectedStudentId,
    'enrollmentId' => null,
    'issueDate'    => now()->format('Y-m-d'),
    'dueDate'      => now()->addMonth()->format('Y-m-d'),
    'notes'        => null,
])

@endsection

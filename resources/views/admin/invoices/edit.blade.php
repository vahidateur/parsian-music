@extends('layouts.dashboard')

@section('title', __('admin.edit_invoice'))

@section('content')

<div class="mb-8">
    <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-sm text-gray-500 transition hover:text-gray-300">{{ __('admin.back_to_invoices') }}</a>
    <h1 class="mt-3 text-2xl font-semibold text-amber-100">{{ __('admin.edit_invoice') }} — {{ $invoice->invoice_number }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.update_invoice_desc') }}</p>
</div>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.invoices.index')" />

@include('admin.invoices.form', [
    'action'       => route('admin.invoices.update', $invoice),
    'method'       => 'PUT',
    'submitLabel'  => __('admin.update_invoice'),
    'students'     => $students,
    'enrollments'  => $enrollments,
    'itemsSeed'    => old('items', $invoice->items->map(fn ($item) => [
        'title'       => $item->title,
        'description' => $item->description,
        'quantity'    => (int) $item->quantity,
        'unit_price'  => (float) $item->unit_price,
        'discount'    => (float) $item->discount,
    ])->all()),
    'taxSeed'      => (float) old('tax', $invoice->tax),
    'studentId'    => $invoice->student_id,
    'enrollmentId' => $invoice->enrollment_id,
    'issueDate'    => $invoice->issue_date?->format('Y-m-d'),
    'dueDate'      => $invoice->due_date?->format('Y-m-d'),
    'notes'        => $invoice->notes,
])

@endsection

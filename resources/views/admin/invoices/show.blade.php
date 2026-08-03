@extends('layouts.dashboard')

@section('title', $invoice->invoice_number)

@section('content')

@php
    $amountPaid = $invoice->amountPaid();
    $amountDue = $invoice->amountDue();
    // Status precondition plus the named policy ability the endpoint enforces.
    $canPay = ! in_array($invoice->status, [
        \App\Enums\InvoiceStatusEnum::Draft,
        \App\Enums\InvoiceStatusEnum::Paid,
        \App\Enums\InvoiceStatusEnum::Cancelled,
    ], true) && \Illuminate\Support\Facades\Gate::allows('registerPayment', $invoice);
@endphp

{{-- Heading + actions --}}
<div class="mb-8">
    <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 transition hover:text-gray-300">{{ __('admin.back_to_invoices') }}</a>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-semibold text-amber-100">{{ $invoice->invoice_number }}</h1>
            <x-admin.status-badge :label="$invoice->status->label()" :color="$invoice->status->color()" />
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if ($invoice->status === \App\Enums\InvoiceStatusEnum::Draft)
                @can('issue', $invoice)
                <form method="POST" action="{{ route('admin.invoices.issue', $invoice) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
                        {{ __('admin.issue_invoice') }}
                    </button>
                </form>
                @endcan
            @endif

            @unless ($invoice->status->isTerminal())
                @can('update', $invoice)
                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">
                    {{ __('admin.edit') }}
                </a>
                @endcan
                @can('cancel', $invoice)
                <button type="button" x-on:click="$dispatch('open-modal', 'confirm-invoice-cancel-{{ $invoice->id }}')" class="rounded-lg border border-rose-500/40 px-4 py-2.5 text-sm font-medium text-rose-300 transition hover:bg-rose-500/10">
                    {{ __('admin.cancel_invoice') }}
                </button>
                <x-modal name="confirm-invoice-cancel-{{ $invoice->id }}" variant="confirmation"
                         :entity="__('admin.invoice') . ' #' . $invoice->id"
                         :action="__('admin.cancel_invoice')"
                         :consequence="__('admin.confirmation_consequence_irreversible')">
                    <x-admin.form-state>
                        <form method="POST" action="{{ route('admin.invoices.cancel', $invoice) }}">
                            @csrf
                            <div class="flex justify-end gap-2">
                                <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">{{ __('admin.cancel') }}</button>
                                <button type="submit" x-bind:disabled="pending" x-bind:aria-busy="pending" class="rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300 disabled:cursor-not-allowed disabled:opacity-60">{{ __('admin.confirm') }}</button>
                            </div>
                        </form>
                    </x-admin.form-state>
                </x-modal>
                @endcan
            @endunless

            @can('duplicate', $invoice)
            <form method="POST" action="{{ route('admin.invoices.duplicate', $invoice) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
                    {{ __('admin.duplicate_invoice') }}
                </button>
            </form>
            @endcan
        </div>
    </div>
</div>

@include('admin.partials.flash')

{{-- Summary --}}
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.total_amount') }}</p>
        <p class="mt-2 text-xl font-semibold text-gray-100">{{ number_format((float) $invoice->total) }} <span class="text-sm font-normal text-gray-500">{{ __('admin.currency_toman') }}</span></p>
    </div>
    <div class="rounded-2xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.amount_paid') }}</p>
        <p class="mt-2 text-xl font-semibold text-emerald-400">{{ number_format($amountPaid) }} <span class="text-sm font-normal text-gray-500">{{ __('admin.currency_toman') }}</span></p>
    </div>
    <div class="rounded-2xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.amount_due') }}</p>
        <p class="mt-2 text-xl font-semibold {{ $amountDue > 0 ? 'text-amber-300' : 'text-emerald-400' }}">{{ number_format($amountDue) }} <span class="text-sm font-normal text-gray-500">{{ __('admin.currency_toman') }}</span></p>
    </div>
    <div class="rounded-2xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.due_date') }}</p>
        <p class="mt-2 text-xl font-semibold text-gray-100">{{ \App\Helpers\Jalalian::fromCarbon($invoice->due_date) }}</p>
    </div>
</div>

{{-- Invoice meta --}}
<div class="mb-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.invoice_details') }}</h2>
    </div>
    <dl class="grid grid-cols-1 gap-6 px-6 py-6 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.student') }}</dt>
            <dd class="mt-1 text-sm text-gray-100">
                @if ($invoice->student)
                    <a href="{{ route('admin.students.show', $invoice->student) }}" class="text-amber-400 transition hover:text-amber-300">{{ $invoice->student->full_name }}</a>
                @else
                    —
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.enrollment') }}</dt>
            <dd class="mt-1 text-sm text-gray-100">{{ $invoice->enrollment?->instrument?->display_name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.issue_date') }}</dt>
            <dd class="mt-1 text-sm text-gray-100">{{ \App\Helpers\Jalalian::fromCarbon($invoice->issue_date) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.tax') }}</dt>
            <dd class="mt-1 text-sm text-gray-100">{{ number_format((float) $invoice->tax) }} {{ __('admin.currency_toman') }}</dd>
        </div>
        @if ($invoice->notes)
            <div class="sm:col-span-2 lg:col-span-4">
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.notes') }}</dt>
                <dd class="mt-1 text-sm text-gray-300">{{ $invoice->notes }}</dd>
            </div>
        @endif
    </dl>
</div>

{{-- Line items --}}
<div class="mb-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.invoice_items') }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.item_title') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.quantity') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.unit_price') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.discount') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.line_total') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($invoice->items as $item)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-4 font-medium text-gray-100">
                            {{ $item->title }}
                            @if ($item->description)
                                <span class="block text-xs text-gray-500">{{ $item->description }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-400">{{ $item->quantity }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ number_format((float) $item->unit_price) }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ number_format((float) $item->discount) }}</td>
                        <td class="px-6 py-4 text-gray-100">{{ number_format((float) $item->total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_invoice_items') }}</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($invoice->items->isNotEmpty())
                <tfoot class="border-t border-gray-800/60 bg-gray-800/20">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-xs uppercase tracking-wider text-gray-500">{{ __('admin.subtotal') }}</td>
                        <td class="px-6 py-3 text-gray-400">-{{ number_format((float) $invoice->discount) }}</td>
                        <td class="px-6 py-3 text-gray-100">{{ number_format((float) $invoice->subtotal) }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-6 py-3 text-xs uppercase tracking-wider text-gray-500">{{ __('admin.total_amount') }}</td>
                        <td class="px-6 py-3 font-semibold text-amber-200">{{ number_format((float) $invoice->total) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Payment ledger --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.payments') }}</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.paid_at') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.payment_amount') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.payment_method') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.status') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.payment_reference') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($invoice->payments as $payment)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-4 text-gray-400">{{ \App\Helpers\Jalalian::fromCarbon($payment->paid_at, 'Y/m/d H:i') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-100">{{ number_format((float) $payment->amount) }} {{ __('admin.currency_toman') }}</td>
                        <td class="px-6 py-4">
                            <x-admin.status-badge :label="$payment->method->label()" :color="$payment->method->color()" />
                        </td>
                        <td class="px-6 py-4">
                            <x-admin.status-badge :label="$payment->status->label()" :color="$payment->status->color()" />
                        </td>
                        <td class="px-6 py-4 text-gray-400">{{ $payment->reference ?? '—' }}</td>
                        <td class="px-6 py-4 text-right">
                            @can('deletePayment', $invoice)
                            <button type="button" x-on:click="$dispatch('open-modal', 'confirm-payment-delete-{{ $invoice->id }}-{{ $payment->id }}')" class="text-red-400 transition hover:text-red-300">{{ __('admin.delete') }}</button>
                            <x-modal name="confirm-payment-delete-{{ $invoice->id }}-{{ $payment->id }}" variant="confirmation"
                                     :entity="__('admin.payment') . ' #' . $payment->id"
                                     :action="__('admin.delete')"
                                     :consequence="__('admin.confirmation_consequence_irreversible')">
                                <x-admin.form-state>
                                    <form method="POST" action="{{ route('admin.invoices.payments.destroy', [$invoice, $payment]) }}">
                                        @csrf @method('DELETE')
                                        <div class="flex justify-end gap-2">
                                            <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">{{ __('admin.cancel') }}</button>
                                            <button type="submit" x-bind:disabled="pending" x-bind:aria-busy="pending" class="rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300 disabled:cursor-not-allowed disabled:opacity-60">{{ __('admin.confirm') }}</button>
                                        </div>
                                    </form>
                                </x-admin.form-state>
                            </x-modal>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_payments_yet') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($canPay)
        <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice) }}" class="grid grid-cols-1 gap-4 border-t border-gray-800/60 px-6 py-6 sm:grid-cols-4 sm:items-end">
            @csrf
            <div>
                <label for="amount" class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.payment_amount') }}</label>
                <input id="amount" name="amount" type="number" min="1" step="1" max="{{ $amountDue }}" required value="{{ old('amount', $amountDue) }}"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            </div>
            <div>
                <label for="method" class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.payment_method') }}</label>
                <select id="method" name="method" required class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    @foreach (\App\Enums\PaymentMethodEnum::cases() as $method)
                        <option value="{{ $method->value }}" {{ old('method') === $method->value ? 'selected' : '' }}>{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="reference" class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.payment_reference') }}</label>
                <input id="reference" name="reference" type="text" maxlength="100" value="{{ old('reference') }}"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            </div>
            <div>
                <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
                    {{ __('admin.register_payment') }}
                </button>
            </div>
        </form>
    @endif
</div>

@endsection

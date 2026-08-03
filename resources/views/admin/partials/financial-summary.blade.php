{{--
    Student Financial Summary — billing position derived from the Invoice domain.
    Expects: $student, $financialSummary (invoice_count, total_invoiced, total_paid, total_outstanding, last_payment_at).
    Phase: Billing.
--}}
<div class="mb-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.financial_summary') }}</h2>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.invoices.index', ['student_id' => $student->id]) }}" class="text-sm text-amber-400 transition hover:text-amber-300">
                {{ __('admin.view_invoices') }}
            </a>
            <a href="{{ route('admin.invoices.create', ['student_id' => $student->id]) }}" class="rounded-lg border border-amber-500/40 px-3 py-1.5 text-xs font-medium text-amber-300 transition hover:bg-amber-500/10">
                {{ __('admin.new_invoice') }}
            </a>
        </div>
    </div>

    @if ($financialSummary['invoice_count'] > 0)
        <dl class="grid grid-cols-1 gap-6 px-6 py-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.total_invoiced') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-100">
                    {{ number_format($financialSummary['total_invoiced']) }}
                    <span class="text-sm font-normal text-gray-500">{{ __('admin.currency_toman') }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.total_paid') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-emerald-400">
                    {{ number_format($financialSummary['total_paid']) }}
                    <span class="text-sm font-normal text-gray-500">{{ __('admin.currency_toman') }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.total_outstanding') }}</dt>
                <dd class="mt-1 text-lg font-semibold {{ $financialSummary['total_outstanding'] > 0 ? 'text-amber-300' : 'text-emerald-400' }}">
                    {{ number_format($financialSummary['total_outstanding']) }}
                    <span class="text-sm font-normal text-gray-500">{{ __('admin.currency_toman') }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.last_payment_date') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-100">
                    {{ $financialSummary['last_payment_at'] ? \App\Helpers\Jalalian::fromCarbon($financialSummary['last_payment_at']) : '—' }}
                </dd>
            </div>
        </dl>
    @else
        <div class="px-6 py-12 text-center text-gray-500">
            {{ __('admin.no_financial_records') }}
        </div>
    @endif
</div>

@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.payments') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.manage_payments') }}</p>
        </div>
        <a href="{{ route('admin.payments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('admin.new_payment') }}
        </a>
    </div>
</div>

{{-- Success Message --}}
@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.student') }}</th>
                    @include('admin.partials.sort-th', ['col'=>'amount_total', 'label'=>__('admin.amount_total'), 'currentSort'=>$sortKey, 'currentDir'=>$sortDir, 'route'=>'admin.payments.index'])
                    @include('admin.partials.sort-th', ['col'=>'discount', 'label'=>__('admin.discount'), 'currentSort'=>$sortKey, 'currentDir'=>$sortDir, 'route'=>'admin.payments.index'])
                    @include('admin.partials.sort-th', ['col'=>'amount_paid', 'label'=>__('admin.amount_paid'), 'currentSort'=>$sortKey, 'currentDir'=>$sortDir, 'route'=>'admin.payments.index'])
                    @include('admin.partials.sort-th', ['col'=>'remaining_balance', 'label'=>__('admin.remaining_balance'), 'currentSort'=>$sortKey, 'currentDir'=>$sortDir, 'route'=>'admin.payments.index'])
                    @include('admin.partials.sort-th', ['col'=>'payment_date', 'label'=>__('admin.payment_date'), 'currentSort'=>$sortKey, 'currentDir'=>$sortDir, 'route'=>'admin.payments.index'])
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.status') }}</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($payments as $payment)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-4 font-medium text-gray-100">{{ $payment->enrollment?->student?->full_name ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ number_format($payment->amount_total) }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ number_format($payment->discount) }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ number_format($payment->amount_paid) }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ number_format($payment->remaining_balance) }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $payment->payment_date ? \App\Helpers\Jalalian::fromCarbon($payment->payment_date) : '—' }}</td>
                        <td class="px-6 py-4">
                            @php
                                $statusValue = $payment->payment_status;
                                $statusStyles = [
                                    'fully_paid' => 'bg-emerald-500/10 text-emerald-400',
                                    'partial' => 'bg-amber-500/10 text-amber-300',
                                    'owing' => 'bg-red-500/10 text-red-400',
                                ];
                                $style = $statusStyles[$statusValue] ?? 'bg-gray-700/50 text-gray-400';
                            @endphp
                            <span class="rounded-full {{ $style }} px-2.5 py-0.5 text-xs font-medium">
                                {{ __('admin.payment_statuses.' . $statusValue) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.payments.edit', $payment) }}" class="text-amber-400 transition hover:text-amber-300">{{ __('admin.edit') }}</a>
                            <form method="POST" action="{{ route('admin.payments.destroy', $payment) }}" class="inline ml-3" onsubmit="return confirm('{{ __('admin.delete_payment_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 transition hover:text-red-300">{{ __('admin.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_payments_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
@if ($payments->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $payments->withQueryString()->links() }}
    </div>
@endif

@endsection

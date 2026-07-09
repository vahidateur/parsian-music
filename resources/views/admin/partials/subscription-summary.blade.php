<div class="mt-6 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">Subscription Summary</h2>
        <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">{{ $student->subscriptions->count() }} subscription(s)</span>
    </div>

    @if ($student->subscriptions->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Teacher</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Instrument</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Remaining</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Renewal</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Fee</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($student->subscriptions as $sub)
                        @php
                            $remaining = $sub->sessions_allocated - $sub->sessions_used;
                            $isOverage = $remaining < 0;
                        @endphp
                        <tr class="transition hover:bg-gray-800/20">
                            <td class="px-6 py-4 font-medium text-gray-100">{{ $sub->teacher->full_name }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ $sub->instrument->name }}</td>
                            <td class="px-6 py-4 {{ $isOverage ? 'text-red-400 font-semibold' : 'text-gray-100' }}">
                                {{ $remaining }}/{{ $sub->sessions_allocated }}
                                @if ($isOverage)
                                    <span class="ml-1 text-xs text-red-400">({{ abs($remaining) }} over)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($sub->payment_status === 'paid')
                                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-400">PAID ✓</span>
                                @elseif ($sub->payment_status === 'unpaid')
                                    <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-medium text-amber-400">UNPAID</span>
                                @else
                                    <span class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-medium text-red-400 font-semibold">OVERDUE</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-400">{{ $sub->renewal_date ? $sub->renewal_date->format('Y-m-d') : '—' }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ number_format($sub->monthly_fee) }} ت</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-12 text-center text-gray-500">
            No active subscriptions
        </div>
    @endif
</div>

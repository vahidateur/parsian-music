@extends('layouts.dashboard')

@section('content')

{{-- ═══════════════════════════════════════════════════════
     Heading
════════════════════════════════════════════════════════ --}}
<x-dashboard.section-header :title="__('admin.dashboard')" :subtitle="__('admin.welcome_message')">
    <x-slot:actions>
        @php
            $persianDays = ['Saturday'=>'شنبه','Sunday'=>'یک‌شنبه','Monday'=>'دوشنبه','Tuesday'=>'سه‌شنبه','Wednesday'=>'چهارشنبه','Thursday'=>'پنج‌شنبه','Friday'=>'جمعه'];
            $dayName = $persianDays[now()->format('l')] ?? now()->format('l');
        @endphp
        <p class="flex items-center gap-2 text-sm text-gray-500" aria-label="تاریخ و ساعت امروز">
            <span>{{ $dayName }}،</span>
            <span>{{ \App\Helpers\Jalalian::fromCarbon(now()) }}</span>
            <span class="text-gray-700">|</span>
            <span id="live-clock" class="tabular-nums text-amber-400/80" dir="ltr">{{ now()->format('H:i') }}</span>
        </p>
        <script>
            (function () {
                const el = document.getElementById('live-clock');
                if (!el) return;
                function tick() {
                    const d = new Date();
                    el.textContent = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
                }
                tick();
                setInterval(tick, 1000);
            })();
        </script>
    </x-slot:actions>
</x-dashboard.section-header>

{{-- ═══════════════════════════════════════════════════════
     Quick Actions
════════════════════════════════════════════════════════ --}}
<x-dashboard.chart-container title="دسترسی سریع" aria-label="دسترسی سریع به بخش‌های اصلی">
    @php
        $actions = [
            ['href' => route('admin.students.create'),    'label' => 'هنرجوی جدید',  'color' => 'amber',   'path' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['href' => route('admin.teachers.create'),    'label' => 'استاد جدید',    'color' => 'emerald', 'path' => 'M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5'],
            ['href' => route('admin.sessions.create'),    'label' => 'جلسه جدید',     'color' => 'sky',     'path' => 'M12 4.5v15m7.5-7.5h-15'],
            ['href' => route('admin.sessions.index'),     'label' => 'حضور و غیاب',  'color' => 'violet',  'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['href' => route('admin.calendar.index'),     'label' => 'تقویم',          'color' => 'rose',    'path' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
            ['href' => route('admin.reports.attendance'), 'label' => 'گزارش‌ها',      'color' => 'orange',  'path' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
        ];
    @endphp
    <nav aria-label="دسترسی سریع" class="grid grid-cols-3 gap-2 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($actions as $action)
            <a
                href="{{ $action['href'] }}"
                aria-label="{{ $action['label'] }}"
                class="group flex flex-col items-center gap-2 rounded-xl border border-gray-800/60 bg-gray-800/20 p-3 text-center
                       transition duration-200 hover:-translate-y-1 hover:border-{{ $action['color'] }}-500/40 hover:bg-{{ $action['color'] }}-500/[0.07] hover:shadow-lg hover:shadow-black/20
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/25 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 sm:p-4"
            >
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-{{ $action['color'] }}-500/10 ring-1 ring-{{ $action['color'] }}-500/20 transition duration-200 group-hover:bg-{{ $action['color'] }}-500/20" aria-hidden="true">
                    <svg class="h-4 w-4 text-{{ $action['color'] }}-400 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['path'] }}"/></svg>
                </span>
                <span class="text-[11px] font-medium text-gray-400 transition duration-200 group-hover:text-{{ $action['color'] }}-300 sm:text-xs">{{ $action['label'] }}</span>
            </a>
        @endforeach
    </nav>
</x-dashboard.chart-container>

{{-- ═══════════════════════════════════════════════════════
     Row 1 — KPI Cards
════════════════════════════════════════════════════════ --}}
<div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" role="list" aria-label="شاخص‌های کلیدی">
    <x-dashboard.kpi-card :label="__('admin.total_students')" :value="$totalStudents" :hint="__('admin.total_registered')" badge="+0%" tone="amber">
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>

    <x-dashboard.kpi-card :label="__('admin.active_teachers')" :value="$activeTeachers" :hint="__('admin.currently_teaching')" badge="فعال" tone="emerald">
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>

    <x-dashboard.kpi-card :label="__('admin.today_sessions')" :value="$todaySessions" :hint="__('admin.scheduled_for_today')" badge="امروز" tone="sky">
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>

    <x-dashboard.kpi-card :label="__('admin.monthly_revenue')" value="—" :hint="__('admin.coming_soon')" :badge="__('admin.coming_soon')" tone="violet">
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>
</div>

{{-- ═══════════════════════════════════════════════════════
     Row 2 — Charts (with skeleton loaders)
════════════════════════════════════════════════════════ --}}
<div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
    <x-dashboard.chart-container title="روند حضور و غیاب" subtitle="نمای کلی ۳۰ روز اخیر" badge="نمودار" data-chart="attendance-trend" :data-chart-data="json_encode($chartAttendanceTrend)">
        <div class="relative min-h-[220px]">
            <div class="chart-skeleton absolute inset-0 animate-pulse rounded-xl bg-gray-800/40" aria-hidden="true"></div>
            <div id="chart-attendance-trend" class="relative w-full" role="img" aria-label="نمودار روند حضور و غیاب ۳۰ روز اخیر"></div>
        </div>
    </x-dashboard.chart-container>

    <x-dashboard.chart-container title="بار کاری اساتید" subtitle="توزیع جلسات فعال" badge="نمودار" data-chart="teacher-workload" :data-chart-data="json_encode($chartTeacherWorkload)">
        <div class="relative min-h-[220px]">
            <div class="chart-skeleton absolute inset-0 animate-pulse rounded-xl bg-gray-800/40" aria-hidden="true"></div>
            <div id="chart-teacher-workload" class="relative w-full" role="img" aria-label="نمودار بار کاری اساتید"></div>
        </div>
    </x-dashboard.chart-container>

    <x-dashboard.chart-container title="وضعیت جلسات" subtitle="تمام دوران" badge="نمودار" data-chart="session-status" :data-chart-data="json_encode($chartSessionStatus)">
        <div class="relative min-h-[220px]">
            <div class="chart-skeleton absolute inset-0 animate-pulse rounded-xl bg-gray-800/40" aria-hidden="true"></div>
            <div id="chart-session-status" class="relative w-full" role="img" aria-label="نمودار وضعیت جلسات"></div>
        </div>
    </x-dashboard.chart-container>

    <x-dashboard.chart-container title="درآمد ماهانه" subtitle="ماژول صورت‌حساب به‌زودی" badge="به‌زودی" data-chart="monthly-revenue" :data-chart-data="json_encode($chartMonthlyRevenue)">
        <div class="relative min-h-[220px]">
            <div class="chart-skeleton absolute inset-0 animate-pulse rounded-xl bg-gray-800/40" aria-hidden="true"></div>
            <div id="chart-monthly-revenue" class="relative w-full" role="img" aria-label="نمودار درآمد ماهانه"></div>
        </div>
    </x-dashboard.chart-container>
</div>

{{-- ═══════════════════════════════════════════════════════
     Row 3 — Today's Schedule + Alerts
════════════════════════════════════════════════════════ --}}
<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
    <x-dashboard.chart-container
        class="xl:col-span-2"
        :title="__('admin.todays_schedule')"
        :badge="__('admin.sessions_count', ['count' => $recentSessions->count()])"
    >
        @if ($recentSessions->count())
            @php
                $statusStyles = [
                    'scheduled' => 'bg-sky-500/10 text-sky-400',
                    'completed' => 'bg-emerald-500/10 text-emerald-400',
                    'cancelled' => 'bg-red-500/10 text-red-400',
                    'missed'    => 'bg-red-500/10 text-red-400',
                    'makeup'    => 'bg-amber-500/10 text-amber-300',
                ];
            @endphp
            <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6" role="table" aria-label="جلسات امروز">
                <table class="w-full min-w-[480px] text-start text-sm">
                    <thead>
                        <tr class="border-b border-gray-800/60 bg-gray-800/30">
                            @foreach ([__('admin.time'), __('admin.student'), __('admin.teacher'), __('admin.room'), __('admin.status')] as $th)
                                <th scope="col" class="px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 sm:px-6">{{ $th }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        @foreach ($recentSessions as $session)
                            @php
                                $sv = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
                                $bs = $statusStyles[$sv] ?? 'bg-gray-700/50 text-gray-400';
                                $sn = $session->enrollment?->student?->full_name ?? $session->student?->full_name ?? '—';
                                $tn = $session->enrollment?->teacher?->full_name ?? $session->teacher?->full_name ?? '—';
                            @endphp
                            <tr class="transition duration-150 hover:bg-gray-800/25">
                                <td class="px-4 py-3 font-mono text-sm font-semibold text-amber-400 sm:px-6">{{ $session->start_time?->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-100 sm:px-6">{{ $sn }}</td>
                                <td class="px-4 py-3 text-gray-400 sm:px-6">{{ $tn }}</td>
                                <td class="px-4 py-3 text-gray-400 sm:px-6">{{ $session->room ?? '—' }}</td>
                                <td class="px-4 py-3 sm:px-6">
                                    <span class="rounded-full {{ $bs }} px-2.5 py-0.5 text-xs font-medium">{{ __('admin.session_statuses.'.$sv) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-dashboard.empty-state :message="__('admin.no_sessions_today')" compact>
                <x-slot:icon><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg></x-slot:icon>
            </x-dashboard.empty-state>
        @endif
    </x-dashboard.chart-container>

    <div class="space-y-4">
        <x-dashboard.section-header title="هشدارها" subtitle="۷ روز اخیر" class="mb-0" />
        <x-dashboard.stat-card :label="__('admin.missed_sessions')" :value="$missedSessions" :description="__('admin.missed_last_7_days')" tone="rose" trend="Missed" trend-direction="down" />
        <x-dashboard.stat-card :label="__('admin.cancelled_sessions')" :value="$cancelledSessions" :description="__('admin.cancelled_last_7_days')" tone="amber" trend="Cancelled" trend-direction="flat" />
        <x-dashboard.alert-card
            title="اشتراک‌های معوق"
            :message="$overdueSubscriptions > 0 ? $overdueSubscriptions.' اشتراک نیاز به پیگیری دارد' : 'اشتراک معوقی وجود ندارد'"
            :priority="$overdueSubscriptions > 0 ? 'high' : 'success'"
            :meta="$overdueSubscriptions.' مورد'"
        />
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     Row 3.5 — Lead CRM widgets
════════════════════════════════════════════════════════ --}}
<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
    <x-dashboard.chart-container
        class="xl:col-span-2"
        title="{{ __('admin.leads') }} — سرنخ‌های اخیر"
        :badge="__('admin.leads')"
    >
        <x-slot:actions>
            <a href="{{ route('admin.leads.index') }}" class="text-xs font-medium text-amber-400 transition hover:text-amber-300">{{ __('admin.leads') }} ←</a>
        </x-slot:actions>

        @if ($recentLeads->isEmpty())
            <x-dashboard.empty-state :message="__('admin.no_leads_found')" compact>
                <x-slot:icon><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75z"/></svg></x-slot:icon>
            </x-dashboard.empty-state>
        @else
            @php
                $leadStatusBadge = ['new' => 'bg-sky-500/10 text-sky-400', 'contacted' => 'bg-blue-500/10 text-blue-400', 'interested' => 'bg-violet-500/10 text-violet-400', 'trial_scheduled' => 'bg-amber-500/10 text-amber-400', 'registered' => 'bg-emerald-500/10 text-emerald-400', 'lost' => 'bg-gray-700/50 text-gray-400'];
            @endphp
            <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6">
                <table class="w-full min-w-[420px] text-start text-sm">
                    <thead>
                        <tr class="border-b border-gray-800/60 bg-gray-800/30">
                            <th class="px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 sm:px-6">{{ __('admin.full_name') }}</th>
                            <th class="px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 sm:px-6">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 sm:px-6">{{ __('admin.assigned_admin') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        @foreach ($recentLeads as $lead)
                            <tr class="transition duration-150 hover:bg-gray-800/25">
                                <td class="px-4 py-3 font-medium text-gray-100 sm:px-6">
                                    <a href="{{ route('admin.leads.show', $lead) }}" class="hover:text-amber-300">{{ $lead->full_name }}</a>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <span class="rounded-full {{ $leadStatusBadge[$lead->status->value] ?? 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">{{ $lead->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-400 sm:px-6">{{ $lead->assignedUser?->full_name ?? __('admin.unassigned') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-dashboard.chart-container>

    <div class="space-y-4">
        <x-dashboard.section-header title="پیگیری‌های سرنخ" subtitle="امروز و معوق" class="mb-0" />
        <x-dashboard.stat-card label="پیگیری‌های امروز" :value="$todayFollowUps" description="سرنخ‌هایی که امروز باید پیگیری شوند" tone="sky" />
        <x-dashboard.stat-card label="نرخ تبدیل" :value="$leadConversionRate.'%'" description="سرنخ‌های ثبت‌نام‌شده از کل" tone="emerald" />
        <x-dashboard.alert-card
            title="پیگیری‌های معوق"
            :message="$overdueFollowUps > 0 ? $overdueFollowUps.' سرنخ پیگیری معوق دارد' : 'پیگیری معوقی وجود ندارد'"
            :priority="$overdueFollowUps > 0 ? 'high' : 'success'"
            :meta="$overdueFollowUps.' مورد'"
        />
    </div>
</div>

<div class="mt-5">
    <x-dashboard.chart-container title="منابع سرنخ‌ها" subtitle="توزیع سرنخ بر اساس منبع" badge="نمودار" data-chart="lead-sources" :data-chart-data="json_encode($leadSources)">
        <div class="relative min-h-[220px]">
            <div class="chart-skeleton absolute inset-0 animate-pulse rounded-xl bg-gray-800/40" aria-hidden="true"></div>
            <div id="chart-lead-sources" class="relative w-full" role="img" aria-label="نمودار منابع سرنخ‌ها"></div>
        </div>
    </x-dashboard.chart-container>
</div>

{{-- ═══════════════════════════════════════════════════════
     Row 4 — Mini Calendar + Notifications
════════════════════════════════════════════════════════ --}}
<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">

    {{-- Mini Calendar --}}
    <x-dashboard.chart-container title="تقویم ماه جاری" subtitle="جلسات برنامه‌ریزی‌شده">
        @php
            $calStart      = \Carbon\Carbon::now()->startOfMonth();
            $calEnd        = \Carbon\Carbon::now()->endOfMonth();
            $todayStr      = now()->toDateString();
            // Persian week starts Saturday: Carbon 0=Sun→col1, 6=Sat→col0: offset = ($dow+1)%7
            $calOffset     = ($calStart->dayOfWeek + 1) % 7;
            $calDays       = \Carbon\CarbonPeriod::create($calStart, $calEnd)->toArray();
            $sessionDaySet = array_flip($calendarSessionDates);
        @endphp

        <div>
            <div class="mb-2 grid grid-cols-7 text-center text-[10px] font-semibold uppercase tracking-wide text-gray-600" role="row" aria-hidden="true">
                @foreach (['ش','ی','د','س','چ','پ','ج'] as $d)
                    <span class="py-1">{{ $d }}</span>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-0.5" role="grid" aria-label="تقویم ماه جاری">
                @for ($i = 0; $i < $calOffset; $i++)
                    <span role="gridcell" aria-hidden="true"></span>
                @endfor

                @foreach ($calDays as $day)
                    @php
                        $ds      = $day->toDateString();
                        $isToday = $ds === $todayStr;
                        $hasSess = isset($sessionDaySet[$ds]);
                        $href    = route('admin.calendar.index').'?week='.$ds;
                        $ariaLbl = $ds . ($isToday ? ' امروز' : '') . ($hasSess ? ' دارای جلسه' : '');
                    @endphp
                    <a
                        href="{{ $href }}"
                        role="gridcell"
                        aria-label="{{ $ariaLbl }}"
                        aria-current="{{ $isToday ? 'date' : 'false' }}"
                        class="relative flex flex-col items-center justify-center rounded-lg py-1.5 text-xs transition duration-150
                            focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/60 focus-visible:ring-offset-1 focus-visible:ring-offset-gray-900
                            {{ $isToday
                                ? 'bg-amber-500 font-bold text-gray-950 shadow-lg shadow-amber-500/25'
                                : ($hasSess
                                    ? 'bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20 hover:-translate-y-0.5'
                                    : 'text-gray-600 hover:bg-gray-800/40 hover:text-gray-300') }}"
                    >
                        {{ $day->day }}
                        @if ($hasSess && !$isToday)
                            <span class="mt-0.5 h-1 w-1 rounded-full bg-emerald-400/80" aria-hidden="true"></span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-gray-800/40 pt-3 text-[10px] text-gray-600" aria-label="راهنمای تقویم">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span> امروز</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-400/60" aria-hidden="true"></span> دارای جلسه</span>
            </div>
        </div>
    </x-dashboard.chart-container>

    {{-- Notifications --}}
    <x-dashboard.chart-container
        class="xl:col-span-2"
        title="اعلان‌ها"
        subtitle="رویدادهای نیازمند توجه"
        :badge="(string) $notifications->count()"
        aria-live="polite"
        aria-label="پانل اعلان‌ها"
    >
        @if ($notifications->isEmpty())
            <x-dashboard.empty-state message="هیچ اعلانی وجود ندارد" compact>
                <x-slot:icon><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg></x-slot:icon>
            </x-dashboard.empty-state>
        @else
            <div class="max-h-80 space-y-3 overflow-y-auto" role="list" aria-label="لیست اعلان‌ها">
                @foreach ($notifications as $n)
                    <x-dashboard.alert-card
                        :title="$n['title']"
                        :message="$n['message']"
                        :priority="$n['priority']"
                        :meta="$n['meta']"
                    />
                @endforeach
            </div>
        @endif
    </x-dashboard.chart-container>
</div>

{{-- ═══════════════════════════════════════════════════════
     Row 5 — Recent Activities
════════════════════════════════════════════════════════ --}}
<div class="mt-5">
    <x-dashboard.chart-container title="فعالیت‌های اخیر" subtitle="آخرین رویدادهای سیستم" badge="Live">
        @if ($recentActivities->isEmpty())
            <x-dashboard.empty-state
                title="هنوز فعالیتی ثبت نشده"
                message="با ثبت هنرجو، استاد، ثبت‌نام، حضور و جلسه، رویدادها اینجا نمایش داده می‌شوند."
            >
                <x-slot:icon><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
            </x-dashboard.empty-state>
        @else
            <ol class="relative border-r border-gray-800/60 pr-6" aria-label="خط زمانی فعالیت‌ها">
                @foreach ($recentActivities as $i => $activity)
                    <x-dashboard.activity-timeline-item
                        :title="$activity['title']"
                        :description="$activity['description']"
                        :time="$activity['time']"
                        :badge="$activity['badge']"
                        :tone="$activity['tone']"
                        :last="$i === $recentActivities->count() - 1"
                    />
                @endforeach
            </ol>
        @endif
    </x-dashboard.chart-container>
</div>

@vite(['resources/js/charts/dashboard.js'])

@endsection

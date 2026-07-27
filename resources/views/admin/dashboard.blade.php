@extends('layouts.dashboard')

@section('title', 'داشبورد')

@section('content')
<style>
    body.admin-page {
        background-image: url('/images/admin-dashboard-bg.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }
</style>
{{--
    Dashboard — Apple/macOS-inspired composition.
    Data: $kpis, $chartBars, $activities, $recentEnrollments, $topCourses
    Architecture: frozen. Only visual composition changed.
--}}
<div class="db" x-data="{ panelState: 'ready' }">

    {{-- ═══════════════════════════════════════════
         PAGE HEADER — compact macOS title bar style
    ═══════════════════════════════════════════ --}}
    <div class="db__header">
        <div class="db__header-title-group">
            <h2 class="db__title">داشبورد</h2>
            <p class="db__subtitle">نمای کلی عملکرد آکادمی در یک نگاه</p>
        </div>
        <div class="db__header-controls" role="group" aria-label="پیش‌نمایش وضعیت‌های داشبورد">
            <x-ui.button size="sm" variant="ghost" type="button"
                x-on:click="panelState = 'ready'"
                x-bind:aria-pressed="panelState === 'ready'">آماده</x-ui.button>
            <x-ui.button size="sm" variant="ghost" type="button"
                x-on:click="panelState = 'loading'"
                x-bind:aria-pressed="panelState === 'loading'">بارگذاری</x-ui.button>
            <x-ui.button size="sm" variant="ghost" type="button"
                x-on:click="panelState = 'empty'"
                x-bind:aria-pressed="panelState === 'empty'">خالی</x-ui.button>
            <x-ui.button size="sm" variant="ghost" type="button"
                x-on:click="panelState = 'error'"
                x-bind:aria-pressed="panelState === 'error'">خطا</x-ui.button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         STATE PREVIEWS
    ═══════════════════════════════════════════ --}}
    <section id="dashboard-state-preview" aria-live="polite">
        <div x-show="panelState === 'loading'" x-cloak>
            <x-dashboard.chart-container title="در حال آماده‌سازی داشبورد" subtitle="نمونه وضعیت بارگذاری">
                <x-ui.loading-state message="در حال بارگذاری اطلاعات نمایشی…" />
            </x-dashboard.chart-container>
        </div>
        <div x-show="panelState === 'empty'" x-cloak>
            <x-dashboard.chart-container title="داشبورد خالی" subtitle="نمونه وضعیت بدون داده">
                <x-dashboard.empty-state title="داده‌ای برای نمایش وجود ندارد" message="پس از ثبت اولین رویداد، اطلاعات این بخش نمایش داده می‌شود." />
            </x-dashboard.chart-container>
        </div>
        <div x-show="panelState === 'error'" x-cloak>
            <x-dashboard.chart-container title="وضعیت دریافت اطلاعات" subtitle="نمونه وضعیت خطا">
                <x-ui.alert variant="danger" title="دریافت اطلاعات انجام نشد" message="این پیام فقط برای بررسی حالت خطا در رابط کاربری است." dismissible />
            </x-dashboard.chart-container>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════
         READY STATE — full macOS composition
    ═══════════════════════════════════════════ --}}
    <div x-show="panelState === 'ready'" x-cloak>

        {{-- ROW 1 — 5 KPI cards --}}
        <section class="db__row db__kpi-row" aria-label="شاخص‌های کلیدی">
            @foreach ($kpis as $kpi)
                <x-dashboard.kpi-card
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint']"
                    :badge="$kpi['badge']"
                    :tone="$kpi['tone']"
                >
                    <x-slot:icon><span aria-hidden="true">{{ $kpi['icon'] }}</span></x-slot:icon>
                </x-dashboard.kpi-card>
            @endforeach

            {{-- 5th KPI — sessions (static placeholder, no new module) --}}
            <x-dashboard.stat-card
                label="کلاس‌های امروز"
                value="۲۸"
                description="۴ لغو شده"
                trend="این هفته"
                trendDirection="up"
                tone="emerald"
            />
        </section>

        {{-- ROW 2 — Today's Classes · Weekly Chart · Recent Activity --}}
        <section class="db__row db__row--second" aria-label="کلاس‌ها، نمودار و فعالیت">

            {{-- Today's Classes — narrow left column --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--classes"
                title="کلاس‌های امروز"
                subtitle="برنامه جاری"
            >
                <div class="db-classes" role="list" aria-label="کلاس‌های امروز">
                    @php
                    $todayClasses = [
                        ['time'=>'۹:۰۰','title'=>'پیانو مقدماتی','teacher'=>'استاد علوی','duration'=>'۳۰ دقیقه'],
                        ['time'=>'۱۱:۰۰','title'=>'گیتار کلاسیک','teacher'=>'استاد محمدی','duration'=>'۴۵ دقیقه'],
                        ['time'=>'۱۳:۰۰','title'=>'ویولن متوسط','teacher'=>'استاد احمدی','duration'=>'۳۰ دقیقه'],
                        ['time'=>'۱۵:۰۰','title'=>'سلفژ و تئوری','teacher'=>'استاد کریمی','duration'=>'۴۰ دقیقه'],
                        ['time'=>'۱۶:۰۰','title'=>'پیانو پیشرفته','teacher'=>'استاد رضایی','duration'=>'۴۵ دقیقه'],
                    ];
                    @endphp
                    @foreach($todayClasses as $cls)
                    <article class="db-class" role="listitem">
                        <span class="db-class__time" aria-label="ساعت {{ $cls['time'] }}">{{ $cls['time'] }}</span>
                        <div class="db-class__body">
                            <strong class="db-class__title">{{ $cls['title'] }}</strong>
                            <span class="db-class__meta">{{ $cls['teacher'] }} · {{ $cls['duration'] }}</span>
                        </div>
                        <span class="db-class__dot" aria-hidden="true"></span>
                    </article>
                    @endforeach
                </div>
            </x-dashboard.chart-container>

            {{-- Weekly Chart — centre, wider --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--chart"
                title="نمودار جلسات هفتگی"
                subtitle="تعداد جلسات در هر روز"
                badge="هفتگی"
            >
                <div class="db-chart" role="group" aria-label="نمودار ستونی جلسات هفتگی">
                    @foreach ($chartBars as $bar)
                        <div
                            class="db-chart__bar db-chart__bar--{{ $bar['height'] }}"
                            role="img"
                            aria-label="{{ $bar['label'] }}: {{ $bar['value'] }}"
                        >
                            <span class="db-chart__val">{{ $bar['value'] }}</span>
                            <span class="db-chart__lbl">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-dashboard.chart-container>

            {{-- Recent Activity — narrow right column --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--activity"
                title="فعالیت‌های اخیر"
                subtitle="رویدادهای مهم سیستم"
            >
                <div class="db-activity" role="list" aria-label="فعالیت‌های اخیر">
                    @foreach ($activities as $activity)
                        <article class="db-activity__item" role="listitem">
                            <span class="db-activity__dot" aria-hidden="true"></span>
                            <div class="db-activity__body">
                                <p class="db-activity__title">{{ $activity['title'] }}</p>
                                <p class="db-activity__meta">{{ $activity['description'] }}</p>
                                <p class="db-activity__time">{{ $activity['time'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </x-dashboard.chart-container>

        </section>

        {{-- ROW 3 — Popular Teachers · Popular Courses · Recent Students · Weekly Calendar --}}
        <section class="db__row db__row--third" aria-label="اساتید، دوره‌ها، هنرجویان و تقویم">

            {{-- Popular Teachers --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--teachers"
                title="اساتید محبوب"
                subtitle="این ماه"
            >
                @php
                $teachers = [
                    ['name'=>'استاد علوی','sessions'=>'۹۸ جلسه'],
                    ['name'=>'استاد محمدی','sessions'=>'۸۶ جلسه'],
                    ['name'=>'استاد رضایی','sessions'=>'۷۴ جلسه'],
                    ['name'=>'استاد کریمی','sessions'=>'۶۲ جلسه'],
                ];
                @endphp
                <div class="db-teachers" role="list" aria-label="اساتید محبوب">
                    @foreach($teachers as $t)
                    <article class="db-teacher" role="listitem">
                        <span class="db-teacher__avatar" aria-hidden="true">◉</span>
                        <div class="db-teacher__info">
                            <strong class="db-teacher__name">{{ $t['name'] }}</strong>
                            <span class="db-teacher__meta">{{ $t['sessions'] }}</span>
                        </div>
                        <div class="db-teacher__bar-wrap" aria-hidden="true">
                            <div class="db-teacher__bar"></div>
                        </div>
                    </article>
                    @endforeach
                </div>
            </x-dashboard.chart-container>

            {{-- Popular Courses --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--courses"
                title="دوره‌های پرفروش"
                subtitle="برترین دوره‌های ماه جاری"
            >
                <div class="dashboard-entity-list" role="list" aria-label="دوره‌های پرفروش">
                    @foreach ($topCourses as $course)
                        <article class="dashboard-entity" role="listitem">
                            <span class="dashboard-entity__avatar" aria-hidden="true">♫</span>
                            <div>
                                <strong class="dashboard-entity__name">{{ $course['name'] }}</strong>
                                <span class="dashboard-entity__meta">{{ $course['meta'] }}</span>
                                <div class="dashboard-progress"
                                     role="progressbar"
                                     aria-label="{{ $course['name'] }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     aria-valuenow="{{ $course['value'] }}">
                                    <span class="dashboard-progress__value dashboard-progress__value--{{ $course['value'] }}"></span>
                                </div>
                            </div>
                            <strong class="dashboard-entity__amount">{{ $course['amount'] }}</strong>
                        </article>
                    @endforeach
                </div>
            </x-dashboard.chart-container>

            {{-- Recent Students --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--students"
                title="آخرین هنرجویان"
                subtitle="ثبت‌نام‌های اخیر"
            >
                <x-ui.table class="db-table" caption="آخرین ثبت‌نام‌ها">
                    <x-slot:head>
                        <th scope="col">هنرجو</th>
                        <th scope="col">ساز</th>
                        <th scope="col">وضعیت</th>
                    </x-slot:head>
                    <x-slot:body>
                        @foreach ($recentEnrollments as $enrollment)
                            <tr>
                                <td data-label="هنرجو">{{ $enrollment['student'] }}</td>
                                <td data-label="ساز">{{ $enrollment['course'] }}</td>
                                <td data-label="وضعیت">
                                    <x-ui.badge :variant="$enrollment['variant']">{{ $enrollment['status'] }}</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-ui.table>
            </x-dashboard.chart-container>

            {{-- Weekly Calendar --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--calendar"
                title="تقویم هفتگی"
                subtitle="هفته جاری"
            >
                @php
                $days = ['ش','ی','د','س','چ','پ','ج'];
                $today = 4;
                $events = [
                    ['time'=>'۰۹:۰۰','title'=>'جلسه پیانو — سطح مقدماتی'],
                    ['time'=>'۱۰:۰۰','title'=>'جلسه گیتار کلاسیک — سطح ۱'],
                    ['time'=>'۱۱:۰۰','title'=>'جلسه ویولن — سطح متوسط'],
                ];
                @endphp
                <div class="db-cal">
                    <div class="db-cal__days" role="row" aria-label="روزهای هفته">
                        @foreach($days as $i => $d)
                            <div class="db-cal__day{{ $i === $today ? ' db-cal__day--today' : '' }}" role="gridcell" aria-label="{{ $d }}">
                                {{ $d }}
                            </div>
                        @endforeach
                    </div>
                    <div class="db-cal__events" aria-label="رویدادهای امروز">
                        <p class="db-cal__events-title">رویدادهای امروز</p>
                        @foreach($events as $ev)
                        <div class="db-cal__event">
                            <span class="db-cal__event-time">{{ $ev['time'] }}</span>
                            <span class="db-cal__event-title">{{ $ev['title'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </x-dashboard.chart-container>

        </section>

        {{-- ROW 4 — Quick Actions · System Status · Shortcuts --}}
        <section class="db__row db__row--bottom" aria-label="دسترسی سریع و وضعیت">

            {{-- Quick Actions --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--actions"
                title="دسترسی سریع"
                subtitle="مهم‌ترین قسمت‌های پنل"
            >
                <nav class="db-quick" aria-label="دسترسی سریع">
                    <a class="db-quick__item" href="{{ route('admin.students.index') }}">
                        <span class="db-quick__icon" aria-hidden="true">♙</span>
                        <span>هنرجویان</span>
                    </a>
                    <a class="db-quick__item" href="{{ route('admin.teachers.index') }}">
                        <span class="db-quick__icon" aria-hidden="true">♬</span>
                        <span>اساتید</span>
                    </a>
                    <a class="db-quick__item" href="{{ route('admin.sessions.index') }}">
                        <span class="db-quick__icon" aria-hidden="true">◷</span>
                        <span>جلسات</span>
                    </a>
                    <a class="db-quick__item" href="{{ route('admin.leads.index') }}">
                        <span class="db-quick__icon" aria-hidden="true">☷</span>
                        <span>سرنخ‌ها</span>
                    </a>
                </nav>
            </x-dashboard.chart-container>

            {{-- System Status --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--status"
                title="وضعیت سیستم"
                subtitle="آخرین بررسی"
            >
                @php
                $statusItems = [
                    ['label'=>'سرور','state'=>'فعال','ok'=>true],
                    ['label'=>'پایگاه داده','state'=>'فعال','ok'=>true],
                    ['label'=>'پشتیبان‌گیری','state'=>'آخرین: دیروز','ok'=>true],
                    ['label'=>'صف ایمیل','state'=>'۲ در انتظار','ok'=>false],
                ];
                @endphp
                <div class="db-status" role="list" aria-label="وضعیت سیستم">
                    @foreach($statusItems as $s)
                    <div class="db-status__item" role="listitem">
                        <span class="db-status__dot db-status__dot--{{ $s['ok'] ? 'ok' : 'warn' }}" aria-hidden="true"></span>
                        <span class="db-status__label">{{ $s['label'] }}</span>
                        <span class="db-status__state">{{ $s['state'] }}</span>
                    </div>
                    @endforeach
                </div>
            </x-dashboard.chart-container>

            {{-- Shortcuts --}}
            <x-dashboard.chart-container
                class="db__panel db__panel--shortcuts"
                title="میانبرها"
                subtitle="عملیات متداول"
            >
                <nav class="db-shortcuts" aria-label="میانبرهای کاربردی">
                    <a class="db-shortcut" href="{{ route('admin.reports.attendance') }}">
                        <span class="db-shortcut__icon" aria-hidden="true">▤</span>
                        <span>گزارش حضور</span>
                    </a>
                    <a class="db-shortcut" href="{{ route('admin.calendar.index') }}">
                        <span class="db-shortcut__icon" aria-hidden="true">◷</span>
                        <span>تقویم</span>
                    </a>
                    <a class="db-shortcut" href="{{ route('admin.instruments.index') }}">
                        <span class="db-shortcut__icon" aria-hidden="true">◉</span>
                        <span>سازها</span>
                    </a>
                    <a class="db-shortcut" href="{{ route('admin.rooms.index') }}">
                        <span class="db-shortcut__icon" aria-hidden="true">▣</span>
                        <span>اتاق‌ها</span>
                    </a>
                    <a class="db-shortcut" href="{{ route('admin.settings.index') }}">
                        <span class="db-shortcut__icon" aria-hidden="true">⚙</span>
                        <span>تنظیمات</span>
                    </a>
                </nav>
            </x-dashboard.chart-container>

        </section>

    </div>{{-- /ready --}}
</div>{{-- /db --}}
@endsection

@php
    $savedEvents   = $settings['events']   ?? [];
    $savedChannels = $settings['channels'] ?? [];

    $events = [
        ['key' => 'session_reminder',    'label' => 'یادآوری جلسه',     'desc' => 'قبل از شروع هر جلسه برای هنرجو ارسال می‌شود',       'default' => true],
        ['key' => 'session_cancelled',   'label' => 'لغو جلسه',          'desc' => 'هنگام لغو جلسه به هنرجو و استاد خبر می‌دهد',        'default' => true],
        ['key' => 'enrollment_created',  'label' => 'ثبت‌نام جدید',      'desc' => 'هنگام ثبت هنرجوی جدید برای ادمین ارسال می‌شود',     'default' => true],
        ['key' => 'subscription_expiry', 'label' => 'انقضای اشتراک',     'desc' => '۷ روز قبل از اتمام اشتراک یادآوری می‌کند',           'default' => false],
        ['key' => 'payment_received',    'label' => 'دریافت پرداخت',     'desc' => 'رسید پرداخت برای هنرجو ارسال می‌شود',                'default' => false],
        ['key' => 'attendance_recorded', 'label' => 'ثبت حضور و غیاب',  'desc' => 'خلاصه حضور بعد از هر جلسه برای ادمین',               'default' => true],
        ['key' => 'student_created',     'label' => 'هنرجوی جدید',       'desc' => 'هنگام ثبت هنرجوی جدید از طریق CRM',                 'default' => false],
        ['key' => 'payment_due',         'label' => 'سررسید پرداخت',     'desc' => 'یادآوری پرداخت قبل از موعد فاکتور',                  'default' => false],
    ];

    $channels = [
        ['key' => 'in_app',   'label' => 'درون‌برنامه', 'desc' => 'اعلان‌های داخل پنل مدیریت', 'default' => true],
        ['key' => 'email',    'label' => 'ایمیل',        'desc' => 'ارسال از طریق SMTP',          'default' => false],
        ['key' => 'telegram', 'label' => 'تلگرام',       'desc' => 'ارسال از طریق ربات تلگرام', 'default' => false],
    ];

    // Determine enabled state for each event
    $isEventEnabled = function(array $ev) use ($savedEvents): bool {
        if (empty($savedEvents)) return $ev['default'];
        return in_array($ev['key'], $savedEvents);
    };

    $isChannelEnabled = function(array $ch) use ($savedChannels): bool {
        if (empty($savedChannels)) return $ch['default'];
        return in_array($ch['key'], $savedChannels);
    };
@endphp

<x-dashboard.chart-container title="رویدادهای اطلاع‌رسانی">
    <p class="{{ $hintClass }} mb-4">انتخاب کنید کدام رویدادها باید اعلان تولید کنند.</p>
    <div class="divide-y divide-gray-800/40">
        @foreach ($events as $ev)
        @php $enabled = $isEventEnabled($ev); @endphp
        <div class="flex items-center justify-between gap-4 py-3.5"
             x-data="{ on: {{ $enabled ? 'true' : 'false' }} }">
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-200">{{ $ev['label'] }}</p>
                <p class="mt-0.5 text-xs text-gray-500">{{ $ev['desc'] }}</p>
            </div>
            <div class="relative flex-shrink-0">
                {{-- Hidden checkbox submits actual value --}}
                <input type="checkbox"
                       name="events[]"
                       value="{{ $ev['key'] }}"
                       :checked="on"
                       class="sr-only"
                       :id="'ev_{{ $ev['key'] }}'">
                {{-- Visual toggle --}}
                <button type="button"
                        dir="ltr"
                        role="switch"
                        :aria-checked="on.toString()"
                        aria-label="{{ $ev['label'] }} را فعال یا غیرفعال کن"
                        @click="on = !on; $el.previousElementSibling.checked = on"
                        :class="on ? 'bg-amber-500' : 'bg-gray-700'"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40">
                    <span :class="on ? 'translate-x-5' : 'translate-x-0'"
                          class="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200 ease-in-out"></span>
                </button>
            </div>
        </div>
        @endforeach
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="کانال‌های ارسال">
    <p class="{{ $hintClass }} mb-4">کانال‌هایی که اعلان‌ها از طریق آن‌ها ارسال می‌شوند.</p>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ($channels as $ch)
        @php $checked = $isChannelEnabled($ch); @endphp
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3.5 transition duration-150
                       {{ $checked ? 'border-amber-500/30 bg-amber-500/5' : 'border-gray-700/60 bg-gray-800/30 hover:border-gray-600' }}">
            <input type="checkbox"
                   name="channels[]"
                   value="{{ $ch['key'] }}"
                   {{ $checked ? 'checked' : '' }}
                   class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-600 bg-gray-800 text-amber-500 focus:ring-amber-500/40">
            <div>
                <p class="text-sm font-medium text-gray-200">{{ $ch['label'] }}</p>
                <p class="mt-0.5 text-xs text-gray-500">{{ $ch['desc'] }}</p>
            </div>
        </label>
        @endforeach
    </div>

    <x-dashboard.alert-card
        priority="mid"
        message="برای فعال‌سازی تلگرام و ایمیل، ابتدا تنظیمات مربوطه را در بخش‌های ایمیل و تلگرام پیکربندی کنید."
        class="mt-4" />
</x-dashboard.chart-container>

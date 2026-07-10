@php
    $events = [
        ['key' => 'session_reminder',    'label' => 'یادآوری جلسه',      'desc' => 'قبل از شروع هر جلسه برای هنرجو ارسال می‌شود',       'on' => true],
        ['key' => 'session_cancelled',   'label' => 'لغو جلسه',           'desc' => 'هنگام لغو جلسه به هنرجو و استاد خبر می‌دهد',        'on' => true],
        ['key' => 'enrollment_created',  'label' => 'ثبت‌نام جدید',       'desc' => 'هنگام ثبت هنرجوی جدید برای ادمین ارسال می‌شود',     'on' => true],
        ['key' => 'subscription_expiry', 'label' => 'انقضای اشتراک',      'desc' => '۷ روز قبل از اتمام اشتراک یادآوری می‌کند',           'on' => false],
        ['key' => 'payment_received',    'label' => 'دریافت پرداخت',      'desc' => 'رسید پرداخت برای هنرجو ارسال می‌شود',                'on' => false],
        ['key' => 'attendance_recorded', 'label' => 'ثبت حضور و غیاب',   'desc' => 'خلاصه حضور بعد از هر جلسه برای ادمین',               'on' => true],
    ];
@endphp

<x-dashboard.chart-container title="رویدادهای اطلاع‌رسانی">
    <div class="divide-y divide-gray-800/40">
        @foreach ($events as $ev)
            <div class="flex items-center justify-between gap-4 py-3.5">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-200">{{ $ev['label'] }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $ev['desc'] }}</p>
                </div>
                <button type="button"
                        role="switch"
                        aria-checked="{{ $ev['on'] ? 'true' : 'false' }}"
                        aria-label="{{ $ev['label'] }} را فعال یا غیرفعال کن"
                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 {{ $ev['on'] ? 'bg-amber-500' : 'bg-gray-700' }}">
                    <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition duration-200 {{ $ev['on'] ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                </button>
            </div>
        @endforeach
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="کانال‌های ارسال">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ([['label' => 'ایمیل', 'checked' => true], ['label' => 'تلگرام', 'checked' => false], ['label' => 'پیامک', 'checked' => false]] as $ch)
            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-700/60 bg-gray-800/30 px-4 py-3 transition duration-150 hover:border-gray-600">
                <input type="checkbox" {{ $ch['checked'] ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-600 bg-gray-800 text-amber-500 focus:ring-amber-500/40">
                <span class="text-sm text-gray-300">{{ $ch['label'] }}</span>
            </label>
        @endforeach
    </div>
</x-dashboard.chart-container>

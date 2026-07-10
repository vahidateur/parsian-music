@php
    $s = $settings; // shorthand — loaded from storage/app/settings/general.json
@endphp

<x-dashboard.chart-container title="شناسایی سیستم">
    <div>
        <label for="app_name" class="{{ $labelClass }}">نام سیستم</label>
        <input id="app_name" type="text" name="app_name"
               value="{{ old('app_name', $s['app_name'] ?? 'آموزشگاه موسیقی پارسیان') }}"
               class="{{ $inputClass }}">
        <p class="{{ $hintClass }}">در عنوان مرورگر، ایمیل‌ها و هدر پنل نمایش داده می‌شود.</p>
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="زبان و منطقه">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="locale" class="{{ $labelClass }}">زبان پنل مدیریت</label>
            <select id="locale" name="locale" class="{{ $inputClass }}">
                <option value="fa" @selected(old('locale', $s['locale'] ?? 'fa') === 'fa')>فارسی</option>
                <option value="en" @selected(old('locale', $s['locale'] ?? 'fa') === 'en')>English</option>
            </select>
            <p class="{{ $hintClass }}">زبان رابط کاربری پنل مدیریت</p>
        </div>

        <div>
            <label for="timezone" class="{{ $labelClass }}">منطقه زمانی</label>
            <select id="timezone" name="timezone" class="{{ $inputClass }}">
                @foreach ([
                    'Asia/Tehran'    => 'تهران (UTC+3:30)',
                    'Asia/Dubai'     => 'دبی (UTC+4)',
                    'Asia/Istanbul'  => 'استانبول (UTC+3)',
                    'UTC'            => 'UTC',
                    'Europe/London'  => 'لندن (UTC+0)',
                ] as $tz => $label)
                <option value="{{ $tz }}" @selected(old('timezone', $s['timezone'] ?? 'Asia/Tehran') === $tz)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="{{ $hintClass }}">برای محاسبه زمان جلسات و گزارش‌ها استفاده می‌شود.</p>
        </div>

        <div>
            <label for="date_format" class="{{ $labelClass }}">فرمت تاریخ</label>
            <select id="date_format" name="date_format" class="{{ $inputClass }}">
                <option value="jalali"    @selected(old('date_format', $s['date_format'] ?? 'jalali')    === 'jalali')>شمسی (جلالی)</option>
                <option value="gregorian" @selected(old('date_format', $s['date_format'] ?? 'jalali')    === 'gregorian')>میلادی</option>
            </select>
        </div>

        <div>
            <label for="week_start" class="{{ $labelClass }}">شروع هفته</label>
            <select id="week_start" name="week_start" class="{{ $inputClass }}">
                <option value="saturday" @selected(old('week_start', $s['week_start'] ?? 'saturday') === 'saturday')>شنبه</option>
                <option value="monday"   @selected(old('week_start', $s['week_start'] ?? 'saturday') === 'monday')>دوشنبه</option>
            </select>
        </div>
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="پیکربندی سیستم">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="per_page" class="{{ $labelClass }}">تعداد ردیف در هر صفحه</label>
            <input id="per_page" type="number" name="per_page"
                   value="{{ old('per_page', $s['per_page'] ?? 15) }}"
                   min="5" max="100" class="{{ $inputClass }}">
            <p class="{{ $hintClass }}">برای تمام جداول اعمال می‌شود (۵ تا ۱۰۰).</p>
        </div>

        <div>
            <label for="session_default_duration" class="{{ $labelClass }}">مدت پیش‌فرض جلسه (دقیقه)</label>
            <input id="session_default_duration" type="number" name="session_default_duration"
                   value="{{ old('session_default_duration', $s['session_default_duration'] ?? 60) }}"
                   min="15" max="180" class="{{ $inputClass }}">
            <p class="{{ $hintClass }}">هنگام ایجاد جلسه جدید به‌صورت پیش‌فرض پر می‌شود.</p>
        </div>
    </div>
</x-dashboard.chart-container>

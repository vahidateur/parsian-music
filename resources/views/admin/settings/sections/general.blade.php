<x-dashboard.chart-container title="زبان و منطقه">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="locale" class="{{ $labelClass }}">زبان پنل مدیریت</label>
            <select id="locale" name="locale" class="{{ $inputClass }}">
                <option value="fa" selected>فارسی</option>
                <option value="en">English</option>
            </select>
            <p class="{{ $hintClass }}">زبان رابط کاربری پنل مدیریت</p>
        </div>
        <div>
            <label for="timezone" class="{{ $labelClass }}">منطقه زمانی</label>
            <select id="timezone" name="timezone" class="{{ $inputClass }}">
                <option value="Asia/Tehran" selected>تهران (UTC+3:30)</option>
                <option value="UTC">UTC</option>
            </select>
            <p class="{{ $hintClass }}">برای محاسبه زمان جلسات استفاده می‌شود</p>
        </div>
        <div>
            <label for="date_format" class="{{ $labelClass }}">فرمت تاریخ</label>
            <select id="date_format" name="date_format" class="{{ $inputClass }}">
                <option value="jalali" selected>شمسی (جلالی)</option>
                <option value="gregorian">میلادی</option>
            </select>
        </div>
        <div>
            <label for="week_start" class="{{ $labelClass }}">شروع هفته</label>
            <select id="week_start" name="week_start" class="{{ $inputClass }}">
                <option value="saturday" selected>شنبه</option>
                <option value="monday">دوشنبه</option>
            </select>
        </div>
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="پیکربندی سیستم">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="per_page" class="{{ $labelClass }}">تعداد ردیف در هر صفحه</label>
            <input id="per_page" type="number" name="per_page" value="15" min="5" max="100" class="{{ $inputClass }}">
            <p class="{{ $hintClass }}">برای تمام جداول اعمال می‌شود</p>
        </div>
        <div>
            <label for="session_default_duration" class="{{ $labelClass }}">مدت پیش‌فرض جلسه (دقیقه)</label>
            <input id="session_default_duration" type="number" name="session_default_duration" value="60" min="15" max="180" class="{{ $inputClass }}">
        </div>
        <div class="sm:col-span-2">
            <label for="app_name" class="{{ $labelClass }}">نام سیستم</label>
            <input id="app_name" type="text" name="app_name" value="آموزشگاه موسیقی پارسیان" class="{{ $inputClass }}">
            <p class="{{ $hintClass }}">در عنوان مرورگر و ایمیل‌ها نمایش داده می‌شود</p>
        </div>
    </div>
</x-dashboard.chart-container>

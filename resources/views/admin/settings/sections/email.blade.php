<x-dashboard.chart-container title="تنظیمات SMTP">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="mail_host" class="{{ $labelClass }}">آدرس سرور (Host)</label>
            <input id="mail_host" type="text" name="mail_host" placeholder="smtp.gmail.com" class="{{ $inputClass }}" dir="ltr">
        </div>
        <div>
            <label for="mail_port" class="{{ $labelClass }}">پورت</label>
            <input id="mail_port" type="number" name="mail_port" placeholder="587" class="{{ $inputClass }}" dir="ltr">
        </div>
        <div>
            <label for="mail_username" class="{{ $labelClass }}">نام کاربری</label>
            <input id="mail_username" type="text" name="mail_username" placeholder="your@email.com" class="{{ $inputClass }}" dir="ltr">
        </div>
        <div>
            <label for="mail_password" class="{{ $labelClass }}">رمز عبور</label>
            <input id="mail_password" type="password" name="mail_password" placeholder="••••••••" class="{{ $inputClass }}" dir="ltr">
        </div>
        <div>
            <label for="mail_encryption" class="{{ $labelClass }}">رمزنگاری</label>
            <select id="mail_encryption" name="mail_encryption" class="{{ $inputClass }}">
                <option value="tls" selected>TLS</option>
                <option value="ssl">SSL</option>
                <option value="">بدون رمزنگاری</option>
            </select>
        </div>
        <div>
            <label for="mail_from_name" class="{{ $labelClass }}">نام فرستنده</label>
            <input id="mail_from_name" type="text" name="mail_from_name" placeholder="آموزشگاه پارسیان" class="{{ $inputClass }}">
        </div>
        <div class="sm:col-span-2">
            <label for="mail_from_address" class="{{ $labelClass }}">ایمیل فرستنده</label>
            <input id="mail_from_address" type="email" name="mail_from_address" placeholder="noreply@example.com" class="{{ $inputClass }}" dir="ltr">
        </div>
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="تست اتصال">
    <p class="{{ $hintClass }} mb-4">پس از ذخیره تنظیمات، می‌توانید یک ایمیل آزمایشی ارسال کنید.</p>
    <div class="flex flex-wrap items-center gap-3">
        <input type="email" placeholder="آدرس ایمیل آزمایشی"
               class="{{ $inputClass }} max-w-xs" dir="ltr">
        <button type="button" disabled
                class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-500 opacity-60">
            ارسال تست
        </button>
    </div>
</x-dashboard.chart-container>

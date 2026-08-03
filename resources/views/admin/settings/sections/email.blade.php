@php $s = $settings; @endphp

<x-dashboard.chart-container title="تنظیمات SMTP">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="mail_host" class="{{ $labelClass }}">آدرس سرور (Host)</label>
            <input id="mail_host" type="text" name="mail_host"
                   value="{{ old('mail_host', $s['mail_host'] ?? config('mail.mailers.smtp.host', '')) }}"
                   placeholder="smtp.gmail.com" class="{{ $inputClass }}" dir="ltr">
        </div>
        <div>
            <label for="mail_port" class="{{ $labelClass }}">پورت</label>
            <input id="mail_port" type="number" name="mail_port"
                   value="{{ old('mail_port', $s['mail_port'] ?? config('mail.mailers.smtp.port', 587)) }}"
                   placeholder="587" class="{{ $inputClass }}" dir="ltr">
        </div>
        <div>
            <label for="mail_username" class="{{ $labelClass }}">نام کاربری</label>
            <input id="mail_username" type="text" name="mail_username"
                   value="{{ old('mail_username', $s['mail_username'] ?? config('mail.mailers.smtp.username', '')) }}"
                   placeholder="your@email.com" class="{{ $inputClass }}" dir="ltr">
        </div>
        <div>
            <label for="mail_password" class="{{ $labelClass }}">رمز عبور</label>
            <input id="mail_password" type="password" name="mail_password"
                   placeholder="••••••••" class="{{ $inputClass }}" dir="ltr">
            <p class="{{ $hintClass }}">فیلد را خالی بگذارید تا رمز قبلی حفظ شود.</p>
        </div>
        <div>
            <label for="mail_encryption" class="{{ $labelClass }}">رمزنگاری</label>
            <select id="mail_encryption" name="mail_encryption" class="{{ $inputClass }}">
                @php $enc = old('mail_encryption', $s['mail_encryption'] ?? config('mail.mailers.smtp.encryption', 'tls')); @endphp
                <option value="tls" @selected($enc === 'tls')>TLS</option>
                <option value="ssl" @selected($enc === 'ssl')>SSL</option>
                <option value=""   @selected($enc === '')>بدون رمزنگاری</option>
            </select>
        </div>
        <div>
            <label for="mail_from_name" class="{{ $labelClass }}">نام فرستنده</label>
            <input id="mail_from_name" type="text" name="mail_from_name"
                   value="{{ old('mail_from_name', $s['mail_from_name'] ?? config('mail.from.name', '')) }}"
                   placeholder="آموزشگاه پارسیان" class="{{ $inputClass }}">
        </div>
        <div class="sm:col-span-2">
            <label for="mail_from_address" class="{{ $labelClass }}">ایمیل فرستنده</label>
            <input id="mail_from_address" type="email" name="mail_from_address"
                   value="{{ old('mail_from_address', $s['mail_from_address'] ?? config('mail.from.address', '')) }}"
                   placeholder="noreply@example.com" class="{{ $inputClass }}" dir="ltr">
        </div>
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="تست اتصال">
    <p class="{{ $hintClass }} mb-4">پس از ذخیره تنظیمات، می‌توانید یک ایمیل آزمایشی ارسال کنید.</p>
    <x-dashboard.alert-card priority="mid"
        message="قابلیت ارسال ایمیل آزمایشی در نسخه بعدی اضافه می‌شود. ابتدا تنظیمات را ذخیره کنید." />
</x-dashboard.chart-container>

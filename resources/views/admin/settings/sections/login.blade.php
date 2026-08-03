@php $s = $settings; @endphp

{{-- Logo Upload --}}
<x-dashboard.chart-container title="لوگو صفحه ورود">
    <div>
        <label for="login_logo" class="{{ $labelClass }}">آپلود لوگو جدید</label>
        <div class="relative border-2 border-dashed border-gray-700/60 rounded-lg p-6 hover:border-gray-600 transition">
            <input 
                type="file" 
                name="login_logo" 
                accept="image/*"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                id="login_logo"
            >
            <div class="text-center">
                @if(isset($s['login_logo']) && $s['login_logo'])
                    <div class="mb-4">
                        <img 
                            src="{{ Storage::url($s['login_logo']) }}" 
                            alt="Logo" 
                            class="h-24 mx-auto rounded"
                        >
                    </div>
                    <p class="{{ $hintClass }}">برای تغییر لوگو فایل جدید را انتخاب کنید</p>
                @else
                    <svg class="mx-auto h-12 w-12 text-gray-600" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-8a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <p class="text-sm font-medium text-gray-300 mt-2">تصویری را انتخاب کنید</p>
                    <p class="{{ $hintClass }}">PNG, JPG یا SVG تا ۲ مگابایت</p>
                @endif
            </div>
        </div>
        @error('login_logo')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
</x-dashboard.chart-container>

{{-- Header Text --}}
<x-dashboard.chart-container title="متن سرصفحه">
    <div class="space-y-5">
        <div>
            <label for="login_title" class="{{ $labelClass }}">عنوان صفحه (فارسی)</label>
            <input 
                type="text"
                name="login_title"
                id="login_title"
                value="{{ old('login_title', $s['login_title'] ?? '') }}"
                placeholder="آموزشگاه موسیقی پارسیان"
                class="{{ $inputClass }}"
            >
            <p class="{{ $hintClass }}">در بالای فرم ورود نمایش داده می‌شود</p>
        </div>

        <div>
            <label for="login_subtitle" class="{{ $labelClass }}">زیرعنوان (فارسی)</label>
            <textarea 
                name="login_subtitle"
                id="login_subtitle"
                rows="2"
                placeholder="تالار هنر، جادو و موسیقی"
                class="{{ $inputClass }}"
            >{{ old('login_subtitle', $s['login_subtitle'] ?? '') }}</textarea>
        </div>

        <div>
            <label for="login_title_en" class="{{ $labelClass }}">عنوان (انگلیسی)</label>
            <input 
                type="text"
                name="login_title_en"
                id="login_title_en"
                value="{{ old('login_title_en', $s['login_title_en'] ?? '') }}"
                placeholder="PARSIAN MUSIC"
                class="{{ $inputClass }}"
                dir="ltr"
            >
        </div>

        <div>
            <label for="login_academy_name" class="{{ $labelClass }}">نام آموزشگاه (انگلیسی)</label>
            <input 
                type="text"
                name="login_academy_name"
                id="login_academy_name"
                value="{{ old('login_academy_name', $s['login_academy_name'] ?? '') }}"
                placeholder="PARSIAN MUSIC ACADEMY"
                class="{{ $inputClass }}"
                dir="ltr"
            >
            <p class="{{ $hintClass }}">متن انگلیسی زیر زیرعنوان</p>
        </div>
    </div>
</x-dashboard.chart-container>

{{-- Form Elements --}}
<x-dashboard.chart-container title="متن فرم ورود">
    <div class="space-y-5">
        <div>
            <label for="login_divider_text" class="{{ $labelClass }}">متن Divider</label>
            <input 
                type="text"
                name="login_divider_text"
                id="login_divider_text"
                value="{{ old('login_divider_text', $s['login_divider_text'] ?? '') }}"
                placeholder="فرم ورود"
                class="{{ $inputClass }}"
            >
        </div>

        <div>
            <label for="login_phone_placeholder" class="{{ $labelClass }}">Placeholder شماره موبایل</label>
            <input 
                type="text"
                name="login_phone_placeholder"
                id="login_phone_placeholder"
                value="{{ old('login_phone_placeholder', $s['login_phone_placeholder'] ?? '') }}"
                placeholder="شماره موبایل"
                class="{{ $inputClass }}"
            >
        </div>

        <div>
            <label for="login_password_placeholder" class="{{ $labelClass }}">Placeholder رمز عبور</label>
            <input 
                type="text"
                name="login_password_placeholder"
                id="login_password_placeholder"
                value="{{ old('login_password_placeholder', $s['login_password_placeholder'] ?? '') }}"
                placeholder="رمز عبور"
                class="{{ $inputClass }}"
            >
        </div>

        <div>
            <label for="login_button_text" class="{{ $labelClass }}">متن دکمه ورود</label>
            <input 
                type="text"
                name="login_button_text"
                id="login_button_text"
                value="{{ old('login_button_text', $s['login_button_text'] ?? '') }}"
                placeholder="ورود به تالار"
                class="{{ $inputClass }}"
            >
        </div>

        <div>
            <label for="login_forgot_password_text" class="{{ $labelClass }}">متن فراموشی رمز</label>
            <input 
                type="text"
                name="login_forgot_password_text"
                id="login_forgot_password_text"
                value="{{ old('login_forgot_password_text', $s['login_forgot_password_text'] ?? '') }}"
                placeholder="فراموشی رمز عبور؟"
                class="{{ $inputClass }}"
            >
        </div>

        <div>
            <label for="login_show_password_label" class="{{ $labelClass }}">برچسب نمایش رمز</label>
            <input 
                type="text"
                name="login_show_password_label"
                id="login_show_password_label"
                value="{{ old('login_show_password_label', $s['login_show_password_label'] ?? '') }}"
                placeholder="نمایش رمز عبور"
                class="{{ $inputClass }}"
            >
        </div>

        <div>
            <label for="login_hide_password_label" class="{{ $labelClass }}">برچسب مخفی کردن رمز</label>
            <input 
                type="text"
                name="login_hide_password_label"
                id="login_hide_password_label"
                value="{{ old('login_hide_password_label', $s['login_hide_password_label'] ?? '') }}"
                placeholder="مخفی کردن رمز عبور"
                class="{{ $inputClass }}"
            >
        </div>
    </div>
</x-dashboard.chart-container>

{{-- Footer --}}
<x-dashboard.chart-container title="متن پاورقی">
    <div class="space-y-5">
        <div>
            <label for="login_quote" class="{{ $labelClass }}">نقل‌قول (اختیاری)</label>
            <textarea 
                name="login_quote"
                id="login_quote"
                rows="2"
                placeholder="«موسیقی جادوی بی‌کلام است»"
                class="{{ $inputClass }}"
            >{{ old('login_quote', $s['login_quote'] ?? '') }}</textarea>
        </div>

        <div>
            <label for="login_copyright" class="{{ $labelClass }}">متن کپی‌رایت</label>
            <input 
                type="text"
                name="login_copyright"
                id="login_copyright"
                value="{{ old('login_copyright', $s['login_copyright'] ?? '') }}"
                placeholder="Parsian Music Academy. All rights reserved."
                class="{{ $inputClass }}"
                dir="ltr"
            >
        </div>

        <div>
            <label for="login_english_text" class="{{ $labelClass }}">متن انگلیسی پاورقی</label>
            <input 
                type="text"
                name="login_english_text"
                id="login_english_text"
                value="{{ old('login_english_text', $s['login_english_text'] ?? '') }}"
                placeholder="PARSIAN MUSIC"
                class="{{ $inputClass }}"
                dir="ltr"
            >
        </div>
    </div>
</x-dashboard.chart-container>

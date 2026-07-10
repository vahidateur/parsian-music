{{--
    Variables available: $profile (InstituteProfile), $inputClass, $labelClass, $hintClass, $divider
--}}

<form action="{{ route('admin.settings.institute.update') }}"
      method="POST"
      enctype="multipart/form-data"
      id="institute-form"
      novalidate>
    @csrf

    {{-- Flash success --}}
    @if (session('success'))
        <x-dashboard.alert-card
            title="ذخیره شد"
            :message="session('success')"
            priority="success"
            class="mb-5"
        />
    @endif

    {{-- ── Identity ──────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="هویت آموزشگاه">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="{{ $labelClass }}">نام آموزشگاه <span class="text-red-400">*</span></label>
                <input id="name" type="text" name="name"
                       value="{{ old('name', $profile->name) }}"
                       class="{{ $inputClass }} @error('name') border-red-500/60 @enderror"
                       required>
                @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="name_en" class="{{ $labelClass }}">نام انگلیسی</label>
                <input id="name_en" type="text" name="name_en"
                       value="{{ old('name_en', $profile->name_en) }}"
                       class="{{ $inputClass }} @error('name_en') border-red-500/60 @enderror"
                       dir="ltr" placeholder="Parsian Music Academy">
                @error('name_en')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="{{ $labelClass }}">توضیحات کوتاه</label>
                <textarea id="description" name="description" rows="2"
                          class="{{ $inputClass }} @error('description') border-red-500/60 @enderror"
                          placeholder="معرفی مختصر آموزشگاه…">{{ old('description', $profile->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                <p class="{{ $hintClass }}">حداکثر ۵۰۰ کاراکتر — در صفحه عمومی نمایش داده می‌شود</p>
            </div>
        </div>
    </x-dashboard.chart-container>

    {{-- ── Media ─────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="لوگو و کاور">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

            {{-- Logo --}}
            <div>
                <p class="{{ $labelClass }}">لوگو</p>
                <div class="flex items-start gap-4">
                    @if ($profile->logo_url)
                        <img src="{{ $profile->logo_url }}" alt="لوگوی فعلی"
                             class="h-16 w-16 shrink-0 rounded-xl border border-gray-700/60 object-contain bg-gray-900 p-1">
                    @else
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border-2 border-dashed border-gray-700/60 bg-gray-800/40 text-gray-600">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-2 text-xs font-medium text-gray-300 transition duration-150 hover:border-gray-600 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                            </svg>
                            انتخاب فایل
                            <input type="file" name="logo" class="sr-only" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        </label>
                        <p class="{{ $hintClass }} mt-1.5">PNG, JPG, SVG — حداکثر ۲ مگابایت</p>
                        @error('logo')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Cover --}}
            <div>
                <p class="{{ $labelClass }}">تصویر کاور</p>
                <div class="flex items-start gap-4">
                    @if ($profile->cover_url)
                        <img src="{{ $profile->cover_url }}" alt="کاور فعلی"
                             class="h-16 w-28 shrink-0 rounded-xl border border-gray-700/60 object-cover">
                    @else
                        <div class="flex h-16 w-28 shrink-0 items-center justify-center rounded-xl border-2 border-dashed border-gray-700/60 bg-gray-800/40 text-gray-600 text-xs">
                            بدون تصویر
                        </div>
                    @endif
                    <div>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-2 text-xs font-medium text-gray-300 transition duration-150 hover:border-gray-600 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                            </svg>
                            انتخاب فایل
                            <input type="file" name="cover" class="sr-only" accept="image/png,image/jpeg,image/webp">
                        </label>
                        <p class="{{ $hintClass }} mt-1.5">PNG, JPG, WebP — حداکثر ۴ مگابایت</p>
                        @error('cover')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>
    </x-dashboard.chart-container>

    {{-- ── Contact ────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="اطلاعات تماس">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="phone" class="{{ $labelClass }}">تلفن ثابت</label>
                <input id="phone" type="tel" name="phone"
                       value="{{ old('phone', $profile->phone) }}"
                       class="{{ $inputClass }}" dir="ltr" placeholder="021-XXXXXXXX">
                @error('phone')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="mobile" class="{{ $labelClass }}">موبایل</label>
                <input id="mobile" type="tel" name="mobile"
                       value="{{ old('mobile', $profile->mobile) }}"
                       class="{{ $inputClass }}" dir="ltr" placeholder="09XXXXXXXXX">
                @error('mobile')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="{{ $labelClass }}">ایمیل</label>
                <input id="email" type="email" name="email"
                       value="{{ old('email', $profile->email) }}"
                       class="{{ $inputClass }}" dir="ltr" placeholder="info@example.com">
                @error('email')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="website" class="{{ $labelClass }}">وب‌سایت</label>
                <input id="website" type="url" name="website"
                       value="{{ old('website', $profile->website) }}"
                       class="{{ $inputClass }}" dir="ltr" placeholder="https://example.com">
                @error('website')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
    </x-dashboard.chart-container>

    {{-- ── Social ─────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="شبکه‌های اجتماعی">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <label for="instagram" class="{{ $labelClass }}">اینستاگرام</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-gray-500 text-sm">@</span>
                    <input id="instagram" type="text" name="instagram"
                           value="{{ old('instagram', $profile->instagram) }}"
                           class="{{ $inputClass }} pe-7" dir="ltr" placeholder="username">
                </div>
                @error('instagram')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="telegram" class="{{ $labelClass }}">تلگرام</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-gray-500 text-sm">@</span>
                    <input id="telegram_handle" type="text" name="telegram"
                           value="{{ old('telegram', $profile->telegram) }}"
                           class="{{ $inputClass }} pe-7" dir="ltr" placeholder="channel_or_username">
                </div>
                @error('telegram')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="whatsapp" class="{{ $labelClass }}">واتساپ</label>
                <input id="whatsapp" type="tel" name="whatsapp"
                       value="{{ old('whatsapp', $profile->whatsapp) }}"
                       class="{{ $inputClass }}" dir="ltr" placeholder="+98XXXXXXXXXX">
                @error('whatsapp')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
    </x-dashboard.chart-container>

    {{-- ── Location ───────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="آدرس و موقعیت">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <label for="province" class="{{ $labelClass }}">استان</label>
                <input id="province" type="text" name="province"
                       value="{{ old('province', $profile->province) }}"
                       class="{{ $inputClass }}" placeholder="تهران">
                @error('province')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="city" class="{{ $labelClass }}">شهر</label>
                <input id="city" type="text" name="city"
                       value="{{ old('city', $profile->city) }}"
                       class="{{ $inputClass }}" placeholder="تهران">
                @error('city')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="postal_code" class="{{ $labelClass }}">کد پستی</label>
                <input id="postal_code" type="text" name="postal_code"
                       value="{{ old('postal_code', $profile->postal_code) }}"
                       class="{{ $inputClass }}" dir="ltr" placeholder="XXXXXXXXXX">
                @error('postal_code')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-3">
                <label for="address" class="{{ $labelClass }}">آدرس کامل</label>
                <textarea id="address" name="address" rows="2"
                          class="{{ $inputClass }} @error('address') border-red-500/60 @enderror"
                          placeholder="خیابان، کوچه، پلاک…">{{ old('address', $profile->address) }}</textarea>
                @error('address')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
    </x-dashboard.chart-container>

    {{-- ── Schedule ───────────────────────────────────────────────── --}}
    @php
        $days = [
            'saturday'  => 'شنبه',
            'sunday'    => 'یک‌شنبه',
            'monday'    => 'دوشنبه',
            'tuesday'   => 'سه‌شنبه',
            'wednesday' => 'چهارشنبه',
            'thursday'  => 'پنج‌شنبه',
            'friday'    => 'جمعه',
        ];
        $activeDays = old('working_days', $profile->working_days ?? []);
    @endphp

    <x-dashboard.chart-container title="ساعات و روزهای کاری">
        <p class="{{ $labelClass }} mb-3">روزهای کاری</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($days as $val => $label)
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition duration-150
                    {{ in_array($val, $activeDays) ? 'border-amber-500/40 bg-amber-500/10 text-amber-300' : 'border-gray-700/60 bg-gray-800/40 text-gray-400 hover:border-gray-600 hover:text-gray-200' }}">
                    <input type="checkbox" name="working_days[]" value="{{ $val }}"
                           class="sr-only"
                           {{ in_array($val, $activeDays) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        @error('working_days')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror

        <div class="mt-5 grid grid-cols-2 gap-5">
            <div>
                <label for="working_hours_from" class="{{ $labelClass }}">از ساعت</label>
                <input id="working_hours_from" type="time" name="working_hours_from"
                       value="{{ old('working_hours_from', $profile->working_hours_from) }}"
                       class="{{ $inputClass }}" dir="ltr">
                @error('working_hours_from')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="working_hours_to" class="{{ $labelClass }}">تا ساعت</label>
                <input id="working_hours_to" type="time" name="working_hours_to"
                       value="{{ old('working_hours_to', $profile->working_hours_to) }}"
                       class="{{ $inputClass }}" dir="ltr">
                @error('working_hours_to')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>
    </x-dashboard.chart-container>

    {{-- ── Submit (inside form) ───────────────────────────────────── --}}
    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('admin.settings.show', 'institute') }}"
           class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-150 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30">
            بازنشانی
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-5 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-950">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            ذخیره اطلاعات آموزشگاه
        </button>
    </div>

</form>

{{-- Alpine: toggle day-chip highlight on click --}}
<script>
    document.querySelectorAll('input[name="working_days[]"]').forEach(function (cb) {
        var label = cb.closest('label');
        cb.addEventListener('change', function () {
            label.classList.toggle('border-amber-500/40', cb.checked);
            label.classList.toggle('bg-amber-500/10', cb.checked);
            label.classList.toggle('text-amber-300', cb.checked);
            label.classList.toggle('border-gray-700/60', !cb.checked);
            label.classList.toggle('bg-gray-800/40', !cb.checked);
            label.classList.toggle('text-gray-400', !cb.checked);
        });
    });
</script>

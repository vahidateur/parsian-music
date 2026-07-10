@php
    $s       = $settings;
    $enabled = (bool) ($s['telegram_enabled'] ?? false);
@endphp

<x-dashboard.chart-container title="ربات تلگرام" x-data="{ botEnabled: {{ $enabled ? 'true' : 'false' }} }">
    <div class="grid grid-cols-1 gap-5">
        <div>
            <label for="telegram_bot_token" class="{{ $labelClass }}">توکن ربات (Bot Token)</label>
            <input id="telegram_bot_token" type="text" name="telegram_bot_token"
                   value="{{ old('telegram_bot_token', $s['telegram_bot_token'] ?? config('services.telegram.token', '')) }}"
                   placeholder="123456789:AABBccdd..." class="{{ $inputClass }}" dir="ltr">
            <p class="{{ $hintClass }}">از @BotFather در تلگرام دریافت کنید.</p>
        </div>
        <div>
            <label for="telegram_chat_id" class="{{ $labelClass }}">شناسه چت (Chat ID)</label>
            <input id="telegram_chat_id" type="text" name="telegram_chat_id"
                   value="{{ old('telegram_chat_id', $s['telegram_chat_id'] ?? config('services.telegram.chat_id', '')) }}"
                   placeholder="-1001234567890" class="{{ $inputClass }}" dir="ltr">
            <p class="{{ $hintClass }}">شناسه گروه یا کانالی که ربات عضو آن است.</p>
        </div>
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="وضعیت ربات">
    <div class="flex items-center justify-between gap-4" x-data="{ on: {{ $enabled ? 'true' : 'false' }} }">
        <div>
            <p class="text-sm font-medium text-gray-200">فعال‌سازی ربات تلگرام</p>
            <p class="mt-0.5 text-xs text-gray-500">اعلان‌های سیستم از طریق ربات ارسال خواهند شد.</p>
        </div>
        <div class="relative flex-shrink-0">
            <input type="checkbox" name="telegram_enabled" value="1"
                   :checked="on" class="sr-only">
            <button type="button"
                    role="switch"
                    :aria-checked="on.toString()"
                    aria-label="ربات تلگرام را فعال یا غیرفعال کن"
                    @click="on = !on; $el.previousElementSibling.checked = on"
                    :class="on ? 'bg-amber-500' : 'bg-gray-700'"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40">
                <span :class="on ? 'translate-x-5' : 'translate-x-0.5'"
                      class="inline-block h-5 w-5 rounded-full bg-white shadow transition duration-200"></span>
            </button>
        </div>
    </div>
</x-dashboard.chart-container>

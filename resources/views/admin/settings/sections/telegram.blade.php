<x-dashboard.chart-container title="ربات تلگرام">
    <div class="grid grid-cols-1 gap-5">
        <div>
            <label for="telegram_bot_token" class="{{ $labelClass }}">توکن ربات (Bot Token)</label>
            <input id="telegram_bot_token" type="text" name="telegram_bot_token"
                   placeholder="123456789:AABBccdd..." class="{{ $inputClass }}" dir="ltr">
            <p class="{{ $hintClass }}">از @BotFather در تلگرام دریافت کنید</p>
        </div>
        <div>
            <label for="telegram_chat_id" class="{{ $labelClass }}">شناسه چت (Chat ID)</label>
            <input id="telegram_chat_id" type="text" name="telegram_chat_id"
                   placeholder="-1001234567890" class="{{ $inputClass }}" dir="ltr">
            <p class="{{ $hintClass }}">شناسه گروه یا کانالی که ربات عضو آن است</p>
        </div>
    </div>
</x-dashboard.chart-container>

<x-dashboard.chart-container title="وضعیت ربات">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-gray-200">فعال‌سازی ربات تلگرام</p>
            <p class="mt-0.5 text-xs text-gray-500">اعلان‌های سیستم از طریق ربات ارسال خواهند شد</p>
        </div>
        <button type="button"
                role="switch"
                aria-checked="false"
                aria-label="ربات تلگرام را فعال یا غیرفعال کن"
                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-gray-700 transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40">
            <span class="inline-block h-5 w-5 translate-x-0.5 rounded-full bg-white shadow transition duration-200"></span>
        </button>
    </div>
</x-dashboard.chart-container>

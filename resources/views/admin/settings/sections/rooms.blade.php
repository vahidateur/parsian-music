<x-dashboard.chart-container title="مدیریت اتاق‌ها">
    <x-dashboard.alert-card
        title="مدیریت اتاق‌ها در بخش جداگانه‌ای قرار دارد"
        message="برای افزودن، ویرایش یا غیرفعال کردن اتاق‌ها به صفحه مدیریت اتاق‌ها مراجعه کنید."
        priority="info"
    />
    <div class="mt-4">
        <a href="{{ route('admin.rooms.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
            </svg>
            رفتن به مدیریت اتاق‌ها
        </a>
    </div>
</x-dashboard.chart-container>

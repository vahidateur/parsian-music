@props(['schedule'])

<section class="schedule-card cinematic-card" aria-labelledby="schedule-title">
    <header class="section-heading">
        <span class="section-heading__line" aria-hidden="true"></span>
        <h2 id="schedule-title">برنامه هفتگی</h2>
    </header>

    <div class="schedule-scroll" tabindex="0" aria-label="جدول برنامه هفتگی؛ برای مشاهده کامل به طرفین پیمایش کنید">
        <div class="schedule-grid" role="table" aria-label="برنامه کلاس‌های استاد">
            @foreach($schedule['days'] as $day)
                <div class="schedule-day" role="columnheader">{{ $day }}</div>
            @endforeach

            @foreach($schedule['slots'] as $slot)
                <div class="schedule-slot schedule-slot--{{ $slot['type'] }}" style="--schedule-column: {{ $slot['column'] }}; --schedule-row: {{ $slot['row'] }}" role="cell">
                    <strong>{{ $slot['title'] }}</strong>
                    <span>{{ $slot['time'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <ul class="schedule-legend" aria-label="راهنمای نوع کلاس">
        <li><span class="legend-dot legend-dot--group"></span>جلسه گروهی</li>
        <li><span class="legend-dot legend-dot--private"></span>کلاس خصوصی</li>
        <li><span class="legend-dot legend-dot--workshop"></span>کارگاه / مسترکلاس</li>
    </ul>
</section>

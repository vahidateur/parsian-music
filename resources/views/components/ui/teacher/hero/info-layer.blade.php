@props(['teacher'])

<article class="teacher-info" aria-label="اطلاعات مدرس">
    <h1 id="teacher-name">استاد {{ $teacher['name'] }}</h1>
    <p class="teacher-role">{{ $teacher['role'] }}</p>
    <x-ui.teacher.badges.experience-badge :experience="$teacher['experience']" />

    <ul class="teacher-features" aria-label="ویژگی‌های تدریس">
        @foreach($teacher['instruments'] as $index => $instrument)
            <li>
                <span class="teacher-feature__icon" aria-hidden="true">{{ ['♩', '▤', '♬'][$index] ?? '♪' }}</span>
                <span>{{ $instrument }}</span>
            </li>
        @endforeach
    </ul>

    <div class="teacher-cta-wrap">
        <x-ui.teacher.buttons.cta-button />
    </div>
</article>

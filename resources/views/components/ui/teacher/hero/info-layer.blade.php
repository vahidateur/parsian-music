@props(['teacher'])

<article class="teacher-info" dir="rtl" aria-label="اطلاعات مدرس">
    <h1>{{ $teacher['name'] }}</h1>
    <p>{{ $teacher['role'] }}</p>
    <x-ui.teacher.badges.experience-badge :experience="$teacher['experience']" />
    <div class="teacher-chips-row" aria-label="سازها">
        @foreach($teacher['instruments'] as $instrument)
            <x-ui.teacher.chips.instrument-chip :instrument="$instrument" />
        @endforeach
    </div>
    <div class="teacher-cta-wrap">
        <x-ui.teacher.buttons.cta-button />
    </div>
</article>

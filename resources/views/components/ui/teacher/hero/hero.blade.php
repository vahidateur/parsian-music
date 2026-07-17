@props(['teacher'])

<div class="teacher-hero" dir="rtl">
    <div class="teacher-hero__portrait reveal-item">
        <x-ui.teacher.portrait.portrait-frame :teacher="$teacher" />
    </div>

    <div class="teacher-hero__intro reveal-item">
        <p class="teacher-eyebrow">آکادمی موسیقی پارسیان</p>
        <x-ui.teacher.hero.info-layer :teacher="$teacher" />
    </div>
</div>

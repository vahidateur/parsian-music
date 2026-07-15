@props(['teacher'])

<section class="teacher-hero" aria-label="معرفی مدرس">

    <div class="hero-left">
        <div class="background-stack">
            <x-ui.teacher.hero.background-layer />
            <x-ui.teacher.hero.decoration-layer />
        </div>
    </div>

    <div class="hero-right">
        <x-ui.teacher.hero.portrait-layer />
        <x-ui.teacher.hero.info-layer :teacher="$teacher" />
    </div>

</section>

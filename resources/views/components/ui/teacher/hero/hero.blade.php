{{--
    Teacher Hero — cinematic two-column scene composition.
    Props: teacher (content and independent artwork paths).
    Slots remain stable for background, glow, frame, photo and decorations.
--}}
@props(['teacher'])

<section class="teacher-hero" aria-label="معرفی {{ $teacher['name'] }}">
    <div class="hero-left">
        <div class="background-stack">
            <x-ui.teacher.hero.background-layer
                :src="$teacher['background_image']"
                :alt="'فضای آموزشی ' . $teacher['name']"
            />
            <x-ui.teacher.hero.decoration-layer />
        </div>
    </div>

    <div class="hero-right">
        <x-ui.teacher.hero.portrait-layer :teacher="$teacher" />
        <x-ui.teacher.hero.info-layer :teacher="$teacher" />
    </div>
</section>

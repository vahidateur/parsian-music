@php
    $teacher = require base_path('resources/mock/teachers/teacher.php');
@endphp

<x-public-layout :title="$teacher['name'] . ' | آکادمی موسیقی پارسیان'">
    <x-ui.navigation.navbar active="teachers" />

    <main id="main-content" class="teacher-main" role="main">
        <div class="teacher-page">
            <section class="teacher-hero-area" aria-labelledby="teacher-name">
                <div
                    class="teacher-bg-image"
                    style="--teacher-bg: url('{{ $teacher['reference_image'] }}')"
                    aria-hidden="true"
                ></div>

                <x-ui.teacher.hero.hero :teacher="$teacher" />
            </section>

            <div class="teacher-content">
                <x-ui.teacher.biography.biography-panel :teacher="$teacher" />

                <div class="teacher-lower-grid">
                    <x-ui.teacher.schedule :schedule="$teacher['schedule']" />
                    <x-ui.teacher.quote :quote="$teacher['quote']" :author="$teacher['quote_author']" />
                </div>
            </div>
        </div>
    </main>

    <footer class="teacher-footer">
        <span>تجربه‌ای جادویی در مسیر هنر</span>
        <span class="teacher-footer__mark" aria-hidden="true">♪</span>
        <span lang="en" dir="ltr">Parsian Music Academy</span>
    </footer>
</x-public-layout>

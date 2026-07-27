@php
    $teacher = require base_path('resources/mock/teachers/teacher.php');
@endphp

<x-public-layout>
    <x-ui.navigation.navbar active="teachers" />

    <main id="main-content" class="teacher-main">
        <div class="teacher-page">
            <div class="teacher-hero-area">
                <x-ui.navigation.breadcrumb :items="[
                    ['label' => 'خانه', 'url' => '/'],
                    ['label' => 'اساتید', 'url' => '/teachers'],
                    ['label' => $teacher['name'], 'url' => null],
                ]" />

                <x-ui.teacher.hero.hero :teacher="$teacher" />
            </div>

            <x-ui.teacher.biography.biography-panel :teacher="$teacher" />
        </div>
    </main>
</x-public-layout>

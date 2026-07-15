@php
    $teacher = require base_path('resources/mock/teachers/teacher.php');
@endphp

<x-public-layout>
    <x-ui.navigation.navbar active="teachers" />

    <main id="main-content" role="main" class="teacher-main">
        <div class="teacher-page">

            {{-- Hero area wrapper — background image stretches to bottom of this div --}}
            <div class="teacher-hero-area">

                {{-- Background image behind navbar + breadcrumb + hero --}}
                <div
                    class="teacher-bg-image"
                    aria-hidden="true"
                    @if(!empty($teacher['background_image']))
                        style="--teacher-bg: url('{{ $teacher['background_image'] }}')"
                    @endif
                ></div>

                <x-ui.navigation.breadcrumb :items="[
                    ['label' => 'خانه', 'url' => '/'],
                    ['label' => 'اساتید', 'url' => '/teachers'],
                    ['label' => $teacher['name'], 'url' => null],
                ]" />

                <x-ui.teacher.hero.hero :teacher="$teacher" />

            </div>

            {{-- Biography section — below the image --}}
            <x-ui.teacher.biography.biography-panel :teacher="$teacher" />

        </div>
    </main>
</x-public-layout>

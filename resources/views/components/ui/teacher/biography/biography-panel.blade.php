{{--
    Biography Panel — Glass surface containing biography + professional card.
    Phase 2.5: Production UI with mock data. Phase 3: Real data from model.

    Props:
    - teacher: full teacher array
--}}
@props(['teacher'])

<section class="teacher-biography-section" aria-labelledby="biography-heading">
    <div class="teacher-biography-panel">

        {{-- Left: Biography --}}
        <div class="teacher-biography-left">
            <x-ui.teacher.biography.biography-content :teacher="$teacher" />
        </div>

        {{-- Right: Professional Card --}}
        <aside class="teacher-biography-right">
            <x-ui.teacher.professional.professional-card :teacher="$teacher" />
        </aside>

    </div>
</section>

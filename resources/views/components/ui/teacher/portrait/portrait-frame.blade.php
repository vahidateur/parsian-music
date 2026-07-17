@props(['teacher'])

<figure class="teacher-portrait" aria-labelledby="teacher-portrait-caption">
    <div class="teacher-portrait__crown" aria-hidden="true">◇</div>
    <div class="teacher-portrait__ring teacher-portrait__ring--outer" aria-hidden="true"></div>
    <div class="teacher-portrait__ring teacher-portrait__ring--inner" aria-hidden="true"></div>
    <div
        class="teacher-portrait__photo"
        role="img"
        aria-label="پرتره {{ $teacher['name'] }}"
        style="--portrait-image: url('{{ $teacher['reference_image'] }}')"
    ></div>
    <figcaption id="teacher-portrait-caption" class="sr-only">{{ $teacher['name'] }}، {{ $teacher['role'] }}</figcaption>
</figure>

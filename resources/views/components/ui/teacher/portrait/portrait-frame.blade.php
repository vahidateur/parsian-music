{{--
    Portrait Frame — composes independent oval photo and decorative frame assets.
    Props: frame, photo, teacherName. Slots: #teacher-frame-slot, #teacher-photo-slot.
--}}
@props([
    'frame',
    'photo' => null,
    'teacherName' => '',
])

<figure aria-label="پرتره {{ $teacherName }}">
    <figcaption class="sr-only">تصویر {{ $teacherName }}</figcaption>

    <div id="teacher-photo-slot">
        @if ($photo)
            <img
                src="{{ $photo }}"
                alt="پرتره {{ $teacherName }}"
                width="460"
                height="660"
                loading="eager"
                fetchpriority="high"
                decoding="async"
            >
        @endif
    </div>

    <div id="teacher-frame-slot" aria-hidden="true">
        <img
            src="{{ $frame }}"
            alt=""
            width="302"
            height="377"
            loading="eager"
            fetchpriority="high"
            decoding="async"
        >
    </div>
</figure>

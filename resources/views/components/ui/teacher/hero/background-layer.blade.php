{{--
    Hero Background Layer — owns only the teacher scene artwork and readability overlay.
    Props: src, alt. Phase 2.4 integration.
    Slots: #teacher-background-slot, #teacher-glow-slot.
--}}
@props([
    'src',
    'alt' => '',
])

<div id="teacher-background-slot">
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        width="1672"
        height="941"
        loading="eager"
        fetchpriority="high"
        decoding="async"
    >
</div>

<div id="teacher-glow-slot" aria-hidden="true"></div>

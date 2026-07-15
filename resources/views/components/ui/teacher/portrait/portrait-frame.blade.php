{{--
    Portrait Frame — Phase 2.4F: Frame artwork loaded.
    Frame sits OUTSIDE the oval clip (position absolute, not clipped by figure overflow).

    Slots:
    - #teacher-frame-slot  → golden frame artwork (frame-main.png)
    - #teacher-photo-slot  → teacher portrait photo (portrait.webp)
--}}

<figure aria-label="پرتره مدرس">
    <figcaption class="sr-only">تصویر مدرس</figcaption>

    {{-- Photo slot (inside oval) --}}
    <div
        id="teacher-photo-slot"
        role="img"
        aria-label="تصویر پروفایل مدرس"
    ></div>

    {{-- Frame slot — sits outside oval, overlays the border area --}}
    <div
        id="teacher-frame-slot"
        role="img"
        aria-label="قاب پرتره مدرس"
    >
        <img
            src="/storage/ui/teacher/frames/frame-main.png"
            alt=""
            fetchpriority="high"
            loading="eager"
            decoding="async"
        >
    </div>

</figure>

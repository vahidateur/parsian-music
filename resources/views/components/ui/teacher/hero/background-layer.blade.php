{{--
    Background Layer — Phase 2.4E.
    Image rendered full-width via .teacher-bg-image in show.blade.php.
    This slot holds: glow overlay + portrait reservation zone.

    Slots:
    - #teacher-background-slot  → Phase 2.4F: glow/vignette overlay
    - #teacher-glow-slot        → Phase 2.4F: golden bloom
    - .teacher-frame-zone       → invisible soft glow marking where portrait goes
    - (decoration in decoration-layer)
--}}

{{-- Transparent background slot (image is on .teacher-bg-image) --}}
<div
    id="teacher-background-slot"
    role="img"
    aria-label="تصویر پس‌زمینه مدرس"
></div>

{{-- Portrait reservation zone — invisible radial glow, marks future frame/portrait position --}}
<div class="teacher-frame-zone" aria-hidden="true"></div>

{{-- Glow slot — reserved, invisible --}}
<div
    id="teacher-glow-slot"
    aria-hidden="true"
></div>

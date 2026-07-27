{{-- Portrait Layer — composes independent frame and optional teacher photo assets. --}}
@props(['teacher'])

<x-ui.teacher.portrait.portrait-frame
    :frame="$teacher['frame_image']"
    :photo="$teacher['photo_image'] ?? null"
    :teacher-name="$teacher['name']"
/>

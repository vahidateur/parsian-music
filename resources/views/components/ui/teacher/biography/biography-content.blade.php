{{--
    Biography Content — Teacher biography sections with gold dividers.
    Phase 2.5: Mock data. Each section has an anchor ID for Professional Card links.
--}}
@props(['teacher'])

<div class="biography-content" dir="rtl">
    <h2 class="biography-heading" id="biography-heading">رزومه استاد</h2>

    @foreach($teacher['biography'] as $i => $section)
        <article class="biography-section" id="{{ $section['id'] }}" aria-labelledby="bio-{{ $section['id'] }}">
            <h3 class="biography-section-title" id="bio-{{ $section['id'] }}">{{ $section['title'] }}</h3>
            <p class="biography-section-body">{{ $section['body'] }}</p>
        </article>

        @unless($loop->last)
            <div class="biography-divider" aria-hidden="true"></div>
        @endunless
    @endforeach
</div>

{{--
    Professional Card — Sticky on desktop, normal flow on mobile.
    Each row links to its related biography section via anchor.
    Phase 2.5: Mock data. Phase 3: Real data from model.
--}}
@props(['teacher'])

@php
    $rows = [
        ['label' => 'تجربه تدریس', 'value' => $teacher['experience_years'], 'anchor' => '#experience'],
        ['label' => 'تخصص',        'value' => $teacher['specialties'],      'anchor' => '#teaching'],
        ['label' => 'تحصیلات',     'value' => $teacher['education'],        'anchor' => '#background'],
        ['label' => 'مدرک',         'value' => $teacher['degree'],           'anchor' => '#background'],
        ['label' => 'وضعیت',        'value' => $teacher['status'],           'anchor' => null],
    ];
@endphp

<div class="professional-card" dir="rtl">

    <div class="professional-card__header">
        <h3 class="professional-card__title">اطلاعات حرفه‌ای</h3>
    </div>

    <ul class="professional-card__list">
        @foreach($rows as $row)
            <li class="professional-card__row">
                @if($row['anchor'])
                    <a href="{{ $row['anchor'] }}" class="professional-card__link">
                        <span class="professional-card__label">{{ $row['label'] }}</span>
                        <span class="professional-card__value">{{ $row['value'] }}</span>
                    </a>
                @else
                    <div class="professional-card__static">
                        <span class="professional-card__label">{{ $row['label'] }}</span>
                        <span class="professional-card__value professional-card__value--active">{{ $row['value'] }}</span>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

</div>

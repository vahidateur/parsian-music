{{--
  Art-Deco Corner Ornament Component
  
  Decorative flourish for card corners (top-left, top-right, bottom-right, bottom-left)
  
  Usage:
    <x-ui.corner-ornament position="top-left" />
    <x-ui.corner-ornament position="top-right" />
    <x-ui.corner-ornament position="bottom-right" />
    <x-ui.corner-ornament position="bottom-left" />
  
  Props:
    - position: 'top-left' | 'top-right' | 'bottom-right' | 'bottom-left'
    - color: rgba color (default: rgba(213,175,88,0.42))
    - opacity: 0–1 (default: 0.65)
--}}

@props([
    'position' => 'top-left',
    'color' => 'rgba(213,175,88,0.42)',
    'opacity' => 0.65,
])

@php
    $rotations = [
        'top-left'     => '0deg',
        'top-right'    => '90deg',
        'bottom-right' => '180deg',
        'bottom-left'  => '270deg',
    ];
    
    $positions = [
        'top-left'     => 'top:12px;left:12px;',
        'top-right'    => 'top:12px;right:12px;',
        'bottom-right' => 'bottom:12px;right:12px;',
        'bottom-left'  => 'bottom:12px;left:12px;',
    ];
    
    $rotation = $rotations[$position] ?? '0deg';
    $pos = $positions[$position] ?? 'top:12px;left:12px;';
@endphp

<span
    {{ $attributes->merge([
        'class' => 'corner-ornament',
        'aria-hidden' => 'true',
        'style' => "
            position: absolute;
            {$pos}
            width: 34px;
            height: 34px;
            opacity: {$opacity};
            pointer-events: none;
            transform: rotate({$rotation});
            color: {$color};
        ",
    ]) }}
>
    <svg viewBox="0 0 34 34" width="34" height="34" xmlns="http://www.w3.org/2000/svg" fill="none" style="width:100%;height:100%;color:inherit;">
        <!-- Art-Deco corner flourish -->
        <g opacity="0.85">
            <!-- Outer decorative frame -->
            <rect x="2" y="2" width="30" height="30" rx="3" ry="3" stroke="currentColor" stroke-width="0.8" fill="none" opacity="0.6"/>
            
            <!-- Inner accent lines -->
            <line x1="6" y1="6" x2="6" y2="14" stroke="currentColor" stroke-width="0.6" opacity="0.5"/>
            <line x1="6" y1="6" x2="14" y2="6" stroke="currentColor" stroke-width="0.6" opacity="0.5"/>
            
            <!-- Decorative flourish - Art Deco style -->
            <circle cx="8" cy="8" r="2.5" fill="currentColor" opacity="0.7"/>
            <path d="M 8 4 Q 12 6 14 10" stroke="currentColor" stroke-width="0.7" fill="none" opacity="0.6"/>
            <path d="M 4 8 Q 6 12 10 14" stroke="currentColor" stroke-width="0.7" fill="none" opacity="0.6"/>
            
            <!-- Small accent dots -->
            <circle cx="12" cy="6" r="1" fill="currentColor" opacity="0.5"/>
            <circle cx="6" cy="12" r="1" fill="currentColor" opacity="0.5"/>
        </g>
    </svg>
</span>

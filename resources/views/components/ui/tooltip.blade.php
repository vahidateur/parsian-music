{{-- Core keyboard-accessible tooltip. Props: id. Slots: trigger, content. Phase: 0.5. --}}
@props(['id' => null])
<div class="ui-tooltip">
    <span class="ui-tooltip__trigger" @if($id) aria-describedby="{{ $id }}" @endif tabindex="0">{{ $trigger }}</span>
    <span @if($id) id="{{ $id }}" @endif class="ui-tooltip__content" role="tooltip">{{ $content }}</span>
</div>
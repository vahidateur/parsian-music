{{-- Core responsive table shell. Slots: caption, head, body. Phase: 0.5. --}}
@props(['caption' => null])
<div {{ $attributes->merge(['class' => 'ui-table-wrap']) }}>
    <table class="ui-table">
        @if($caption)<caption class="sr-only">{{ $caption }}</caption>@endif
        @isset($head)<thead><tr>{{ $head }}</tr></thead>@endisset
        @isset($body)<tbody>{{ $body }}</tbody>@endisset
        {{ $slot }}
    </table>
</div>
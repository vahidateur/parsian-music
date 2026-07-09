@props([
    'title' => null,
    'message' => '',
    'compact' => false,
])

<div {{ $attributes->merge([
    'class' => $compact
        ? 'px-4 py-8 text-center'
        : 'flex flex-col items-center justify-center px-6 py-12 text-center',
]) }}>
    @isset($icon)
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-800/60 bg-gray-800/40 text-gray-500 shadow-inner">
            {{ $icon }}
        </div>
    @endisset

    @if ($title)
        <p class="text-sm font-medium text-gray-300">{{ $title }}</p>
    @endif

    @if ($message)
        <p class="{{ $title ? 'mt-1' : '' }} text-sm text-gray-500">{{ $message }}</p>
    @endif

    @isset($action)
        <div class="mt-5">
            {{ $action }}
        </div>
    @endisset

    {{ $slot }}
</div>

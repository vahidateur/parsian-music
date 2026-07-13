{{--
    Reusable login input.
    Props: id, type, name, label, placeholder, autocomplete, value, autofocus (bool), toggle (bool)
--}}
@php
    $isPassword = $toggle ?? false;
@endphp

<div>
    <label for="{{ $id }}" class="block text-[14px] font-medium text-p-gold-light/85 mb-2">
        {{ $label }}
    </label>

    <div class="relative flex items-center">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            :type="{{ $isPassword ? "showPw ? 'text' : 'password'" : "'".$type."'" }}"
            value="{{ $value ?? '' }}"
            @if($autocomplete ?? null) autocomplete="{{ $autocomplete }}" @endif
            @if($autofocus ?? false) autofocus @endif
            required
            dir="rtl"
            placeholder="{{ $placeholder }}"
            class="w-full h-[70px] rounded-[18px] pr-6 {{ $isPassword ? 'pl-14' : 'pl-6' }}
                   bg-[rgba(255,255,255,.06)] border border-[rgba(213,175,88,.35)]
                   text-white text-[15px] placeholder-[rgba(255,255,255,.55)]
                   outline-none transition-colors duration-300
                   focus:border-p-gold focus:shadow-[0_0_0_3px_rgba(213,175,88,.15),0_0_24px_rgba(213,175,88,.18)]"
        >

        {{-- leading icon (right side, RTL) --}}
        <span class="absolute right-6 pointer-events-none text-p-gold/80">
            @if($id === 'phone')
                {{-- lucide "smartphone" --}}
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="6" y="2" width="12" height="20" rx="2.5"/>
                    <line x1="10" y1="18.5" x2="14" y2="18.5"/>
                </svg>
            @else
                {{-- lucide "lock" --}}
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="10" width="14" height="10" rx="2"/>
                    <path d="M8 10V7a4 4 0 018 0v3"/>
                </svg>
            @endif
        </span>

        {{-- password visibility toggle (left side) --}}
        @if($isPassword)
            <button
                type="button"
                @click="showPw = !showPw"
                class="absolute left-5 text-p-gold/60 hover:text-p-gold transition-colors duration-200
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-p-gold/50 rounded"
                :aria-label="showPw ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور'"
            >
                {{-- lucide "eye" / "eye-off" toggled via x-show --}}
                <svg x-show="!showPw" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg x-show="showPw" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.94 10.94 0 0112 20c-7 0-11-7-11-7a19.7 19.7 0 014.22-5.94M9.9 4.24A10.4 10.4 0 0112 4c7 0 11 7 11 7a19.7 19.7 0 01-2.16 3.19"/>
                    <path d="M14.12 14.12a3 3 0 11-4.24-4.24"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
            </button>
        @endif
    </div>

    @error($name)
        <p class="mt-1.5 text-[13px] text-red-400">{{ $message }}</p>
    @enderror
</div>

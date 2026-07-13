@extends('layouts.auth')

@section('content')
<div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950 px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8">

        {{-- Header --}}
        <div class="text-center">
            <h1 class="text-4xl font-bold tracking-tight text-amber-100">
                Parsian Music
            </h1>
            <p class="mt-2 text-sm text-gray-400">
                بازیابی رمز عبور
            </p>
        </div>

        {{-- Status --}}
        @if (session('status'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-emerald-300">{{ session('status') }}</p>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('password.phone.send') }}"
              class="rounded-2xl border border-gray-700/50 bg-gray-800/60 p-8 shadow-2xl backdrop-blur-sm">
            @csrf

            <p class="mb-6 text-sm text-gray-400 leading-relaxed">
                شماره موبایل حساب خود را وارد کنید. توکن بازیابی رمز برای شما ارسال خواهد شد.
            </p>

            {{-- Phone --}}
            <div class="space-y-1.5">
                <label for="phone" class="block text-sm font-medium text-gray-300">شماره موبایل</label>
                <input id="phone" type="tel" name="phone"
                       value="{{ old('phone') }}"
                       required
                       autocomplete="tel"
                       autofocus
                       dir="ltr"
                       class="block w-full rounded-lg border border-gray-600 bg-gray-700/50 px-4 py-3 text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 @error('phone') border-red-500/60 @enderror"
                       placeholder="09123456789">
                @error('phone')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="mt-7 w-full rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-3 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                ارسال توکن بازیابی
            </button>

            {{-- Back to login --}}
            <p class="mt-5 text-center text-sm text-gray-500">
                <a href="{{ route('login') }}" class="text-amber-400 transition hover:text-amber-300">
                    بازگشت به صفحه ورود
                </a>
            </p>
        </form>

        {{-- Footer --}}
        <p class="text-center text-xs text-gray-600">
            Parsian Music Academy
        </p>
    </div>
</div>
@endsection

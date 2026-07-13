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
                تغییر رمز عبور
            </p>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('password.phone.reset') }}"
              class="rounded-2xl border border-gray-700/50 bg-gray-800/60 p-8 shadow-2xl backdrop-blur-sm space-y-5">
            @csrf

            {{-- Phone (hidden, prefilled from query string) --}}
            <input type="hidden" name="phone" value="{{ old('phone', $phone) }}">

            {{-- Token --}}
            <div class="space-y-1.5">
                <label for="token" class="block text-sm font-medium text-gray-300">توکن بازیابی</label>
                <input id="token" type="text" name="token"
                       value="{{ old('token', $token) }}"
                       required
                       autocomplete="one-time-code"
                       dir="ltr"
                       class="block w-full rounded-lg border border-gray-600 bg-gray-700/50 px-4 py-3 text-center font-mono tracking-widest text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 @error('token') border-red-500/60 @enderror"
                       placeholder="توکن ۶۴ کاراکتری">
                @error('token')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- New password --}}
            <div class="space-y-1.5">
                <label for="password" class="block text-sm font-medium text-gray-300">رمز عبور جدید</label>
                <input id="password" type="password" name="password"
                       required
                       autocomplete="new-password"
                       class="block w-full rounded-lg border border-gray-600 bg-gray-700/50 px-4 py-3 text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 @error('password') border-red-500/60 @enderror"
                       placeholder="حداقل ۸ کاراکتر">
                @error('password')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm password --}}
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-300">تکرار رمز عبور جدید</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       required
                       autocomplete="new-password"
                       class="block w-full rounded-lg border border-gray-600 bg-gray-700/50 px-4 py-3 text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="تکرار رمز عبور جدید">
            </div>

            {{-- Phone display (editable so user can correct it) --}}
            @if ($errors->has('phone'))
                <p class="text-sm text-red-400">{{ $errors->first('phone') }}</p>
            @endif

            {{-- Submit --}}
            <button type="submit"
                    class="mt-2 w-full rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-3 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                تغییر رمز عبور
            </button>

            {{-- Back --}}
            <p class="text-center text-sm text-gray-500">
                <a href="{{ route('password.phone.request') }}" class="text-amber-400 transition hover:text-amber-300">
                    درخواست توکن جدید
                </a>
            </p>
        </form>

        <p class="text-center text-xs text-gray-600">
            Parsian Music Academy
        </p>
    </div>
</div>
@endsection

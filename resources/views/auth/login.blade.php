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
                Sign in to your account
            </p>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('login') }}"
              class="rounded-2xl border border-gray-700/50 bg-gray-800/60 p-8 shadow-2xl backdrop-blur-sm">
            @csrf

            {{-- Phone --}}
            <div class="space-y-1.5">
                <label for="phone" class="block text-sm font-medium text-gray-300">Phone</label>
                <input id="phone" type="tel" name="phone"
                       value="{{ old('phone') }}"
                       required
                       autocomplete="tel"
                       autofocus
                       class="block w-full rounded-lg border border-gray-600 bg-gray-700/50 px-4 py-3 text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="09123456789">
                @error('phone')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="mt-5 space-y-1.5">
                <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                <input id="password" type="password" name="password"
                       required
                       autocomplete="current-password"
                       class="block w-full rounded-lg border border-gray-600 bg-gray-700/50 px-4 py-3 text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="••••••••">
                @error('password')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="mt-7 w-full rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-3 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                Sign In
            </button>
        </form>

        {{-- Footer --}}
        <p class="text-center text-xs text-gray-600">
            Parsian Music Academy
        </p>
    </div>
</div>
@endsection

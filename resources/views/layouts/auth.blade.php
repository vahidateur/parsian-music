<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased">
    @yield('content')
    
    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        // Initial render
        lucide.createIcons();

        // Re-render after Alpine mutations (e.g. eye/eye-off toggle)
        document.addEventListener('alpine:initialized', () => {
            document.querySelectorAll('[x-data]').forEach(el => {
                el.addEventListener('click', () => {
                    requestAnimationFrame(() => lucide.createIcons());
                });
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>

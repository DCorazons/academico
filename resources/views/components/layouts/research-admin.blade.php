<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    {{-- Admin pages of the research: Blade, same structure as the student layout (the real admin panel is Filament) --}}
    <nav class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('research.courses') }}" class="text-xl font-semibold text-gray-800">{{ config('app.name') }}</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('research.courses') }}" class="text-blue-600 hover:text-blue-800">Course</a>
                <a href="{{ route('research.reports.courses', ['period_id' => 1]) }}" class="text-blue-600 hover:text-blue-800">Laporan</a>
                <span class="text-gray-600">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-gray-700">Keluar</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>
</body>
</html>

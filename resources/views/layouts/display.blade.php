<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Klantenscherm - {{ config('app.name', 'Kassa') }}</title>

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="h-full font-sans antialiased text-slate-100 bg-slate-950 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-indigo-950/70 to-slate-950 flex flex-col overflow-hidden select-none">
        
        <!-- Ambient Glowing Background Elements -->
        <div class="fixed top-[-15%] left-[-10%] w-[500px] h-[500px] bg-indigo-600/15 rounded-full filter blur-[140px] pointer-events-none"></div>
        <div class="fixed bottom-[-15%] right-[-10%] w-[550px] h-[550px] bg-fuchsia-600/15 rounded-full filter blur-[150px] pointer-events-none"></div>
        <div class="fixed top-[40%] right-[30%] w-[400px] h-[400px] bg-emerald-600/10 rounded-full filter blur-[160px] pointer-events-none"></div>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col h-full w-full relative z-10">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>

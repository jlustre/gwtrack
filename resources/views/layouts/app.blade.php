<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'GWTrack') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-[#F5F7FA] font-sans text-[#172033] antialiased">
        {{-- Persisted chrome: reused across Livewire wire:navigate visits (no sidebar/topbar remount). --}}
        @persist('page-chrome')
            <div id="gw-page-chrome">
                @include('layouts.partials.page-chrome')
            </div>
        @endpersist

        <div id="gw-app-shell" class="min-h-screen lg:flex">
            <div
                x-data
                x-show="$store.shell.sidebarOpen && ! $store.shell.isDesktop"
                x-transition.opacity
                x-on:click="$store.shell.closeMobileSidebar()"
                class="fixed inset-0 z-30 bg-slate-900/50"
                style="display: none;"
            ></div>

            @persist('sidebar')
                <aside
                    id="gw-sidebar-aside"
                    class="fixed inset-y-0 left-0 z-40 flex h-screen w-72 -translate-x-full flex-col overflow-hidden bg-gw-primary text-white transition-all duration-300 lg:sticky lg:top-0 lg:translate-x-0 lg:self-start"
                    x-data
                    x-bind:style="$store.shell.sidebarCollapsed && $store.shell.isDesktop ? 'width: 0px;' : 'width: 18rem;'"
                    x-bind:class="{ 'translate-x-0': $store.shell.sidebarOpen }"
                >
                    <div class="flex h-16 shrink-0 items-center border-b border-white/10 px-6">
                        <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" wire:navigate.hover>
                            <div class="text-lg font-semibold tracking-wide">GWTrack</div>
                            <div class="text-xs uppercase text-gw-accent">Greatway Team Portal</div>
                        </a>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain" wire:scroll>
                        @include('layouts.navigation')
                    </div>
                </aside>
            @endpersist

            <div class="flex min-w-0 flex-1 flex-col">
                @persist('topbar')
                    <div id="gw-topbar">
                        @include('layouts.partials.topbar')
                    </div>
                @endpersist

                @persist('shell-overlays')
                    @include('layouts.partials.shell-overlays')
                @endpersist

                {{-- Only this region swaps on wire:navigate --}}
                <main id="gw-main" class="flex-1 px-4 py-6 lg:px-8">
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>
            </div>
        </div>

        @livewireScripts

        @auth
            @if (session('show_todays_agenda_modal'))
                <div x-data x-init="$nextTick(() => Livewire.dispatch('open-todays-agenda-modal'))"></div>
            @endif
        @endauth
    </body>
</html>

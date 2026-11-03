<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'GWTracks') }}</title>

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
                    class="fixed inset-y-0 left-0 z-40 flex h-screen w-72 shrink-0 -translate-x-full flex-col overflow-hidden bg-gw-primary text-white transition-all duration-300 lg:sticky lg:top-0 lg:translate-x-0 lg:self-start"
                    x-data
                    x-bind:class="{ '!translate-x-0': $store.shell.sidebarOpen, 'lg:!w-0': $store.shell.sidebarCollapsed }"
                >
                    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-white/10 bg-white px-6">
                        <a href="{{ url('/') }}" class="inline-flex flex-col items-start gap-1.5">
                            <img src="{{ asset('images/gwtrackslogo_small.png') }}" alt="GWTracks" class="h-6 w-auto">
                            <span class="text-xs uppercase text-gw-accent-dark">Greatway Team Portal</span>
                        </a>

                        <button
                            type="button"
                            class="rounded-md p-2 text-gw-primary/70 hover:bg-slate-100 hover:text-gw-primary lg:hidden"
                            x-on:click="$store.shell.closeMobileSidebar()"
                        >
                            <span class="sr-only">Close navigation</span>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
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

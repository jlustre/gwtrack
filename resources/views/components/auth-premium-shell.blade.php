@props([
    'headline',
    'intro',
    'title',
    'subtitle',
])

<div class="min-h-screen bg-[radial-gradient(circle_at_10%_20%,#111111,#000000)] px-4 py-8 text-slate-100 sm:px-6 lg:px-8">
    <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center justify-center">
        <div class="w-full overflow-hidden rounded-[2rem] border border-gw-accent/25 bg-black/80 shadow-[0_25px_45px_-12px_rgba(0,0,0,0.8),0_0_18px_rgba(132, 187, 65,0.22)]">
            <div class="grid bg-[#0b0b0c] lg:grid-cols-[0.95fr_1fr]">
                <aside class="border-b border-gw-accent/25 bg-gradient-to-br from-black to-[#101012] p-6 sm:p-8 lg:border-b-0 lg:border-r">
                    <div>
                        <a href="/" class="inline-flex items-center rounded-xl bg-white px-3 py-2">
                            <img src="{{ asset('images/gwtrackslogo_small.png') }}" alt="GWTracks" class="h-8 w-auto sm:h-9">
                        </a>
                        <div class="mt-2 border-l-2 border-gw-accent pl-3 text-xs font-semibold uppercase tracking-[0.16em] text-gw-accent">
                            Financial Intelligence &middot; Insurance Team Portal
                        </div>
                    </div>

                    <div class="mt-8 overflow-hidden rounded-3xl border border-gw-accent/20 bg-[#070707] shadow-[0_18px_34px_-24px_rgba(132, 187, 65,0.55)]">
                        <img
                            src="{{ asset('images/authimage.jpg') }}"
                            alt="GWTracks secure access"
                            class="h-64 w-full object-cover object-top sm:h-80 lg:h-[22rem]"
                            style="-webkit-mask-image: linear-gradient(to bottom, black 0%, black 62%, rgba(0,0,0,0.3) 100%); mask-image: linear-gradient(to bottom, black 0%, black 62%, rgba(0,0,0,0.3) 100%);"
                        >
                    </div>

                    <div class="mt-8">
                        <h1 class="max-w-lg bg-gradient-to-r from-white to-gw-accent-hover bg-clip-text text-3xl font-bold leading-tight text-transparent sm:text-4xl">
                            {{ $headline }}
                        </h1>
                        <div class="mt-4 h-0.5 w-16 bg-gradient-to-r from-gw-accent to-transparent"></div>
                        <p class="mt-5 max-w-md border-l-2 border-gw-accent/50 pl-4 text-base leading-7 text-slate-300">
                            {{ $intro }}
                        </p>

                        <div class="mt-8 space-y-4 text-sm text-slate-200">
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-gw-accent shadow-[0_0_8px_#84BB41]"></span>
                                Private member dashboard
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-gw-accent shadow-[0_0_8px_#84BB41]"></span>
                                Sponsor-connected team tracking
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-gw-accent shadow-[0_0_8px_#84BB41]"></span>
                                Training, resources, events, and recognition
                            </div>
                        </div>
                    </div>
                </aside>

                <main class="flex items-center bg-[#0a0a0c] p-6 sm:p-8">
                    <div class="w-full">
                        <div class="mb-8">
                            <h2 class="text-2xl font-semibold text-white sm:text-3xl">{{ $title }}</h2>
                            <p class="mt-2 text-sm text-slate-400">{{ $subtitle }}</p>
                        </div>

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </div>
</div>

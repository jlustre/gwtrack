@php

    $actions = $home['quick_actions'] ?? [];



    $iconFor = fn (string $icon): string => match ($icon) {

        'tasks' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',

        'invite' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',

        'prospects' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',

        'log_activity' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',

        'prospect_activity' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',

        'schedule_activity' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',

        'schedule_phone_call' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',

        'training' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',

        'messages' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',

        'goals' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',

        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',

        'bookings' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',

        'resources' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',

        'profile' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',

        'team' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',

        default => 'M13 7l5 5m0 0l-5 5m5-5H6',

    };



    $themeFor = fn (string $icon): array => match ($icon) {

        'tasks' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#0F766E]/50 bg-gradient-to-br from-[#071B33] via-[#0B3A67] to-[#0F766E] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-gw-accent hover:shadow-md',
            'icon' => 'bg-white/12 text-[#A7F3D0] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-cyan-50/85',
        ],

        'invite' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-gw-accent/60 bg-gradient-to-br from-[#14532D] via-[#3F6212] to-[#84BB41] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#D9F99D] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#ECFCCB] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-lime-50/90',
        ],

        'prospects' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#0E7490]/50 bg-gradient-to-br from-[#083344] via-[#155E75] to-[#0E7490] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#67E8F9] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#A5F3FC] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-cyan-50/85',
        ],

        'log_activity' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#7E22CE]/50 bg-gradient-to-br from-[#2E1065] via-[#5B21B6] to-[#7E22CE] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#C4B5FD] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#DDD6FE] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-violet-50/85',
        ],

        'prospect_activity' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#7E22CE]/50 bg-gradient-to-br from-[#2E1065] via-[#5B21B6] to-[#0F766E] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#C4B5FD] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#DDD6FE] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-violet-50/85',
        ],

        'schedule_activity' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#0D9488]/50 bg-gradient-to-br from-[#042F2E] via-[#0F766E] to-[#14B8A6] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#5EEAD4] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#99F6E4] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-teal-50/85',
        ],

        'schedule_phone_call' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#1D4ED8]/50 bg-gradient-to-br from-[#1E3A8A] via-[#1D4ED8] to-[#2563EB] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#93C5FD] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#BFDBFE] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-blue-50/85',
        ],

        'training' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#B45309]/50 bg-gradient-to-br from-[#431407] via-[#92400E] to-[#B45309] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#FDBA74] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#FED7AA] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-orange-50/85',
        ],

        'messages' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#2563EB]/50 bg-gradient-to-br from-[#0F172A] via-[#1E3A8A] to-[#2563EB] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#93C5FD] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#BFDBFE] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-blue-50/85',
        ],

        'goals' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#059669]/50 bg-gradient-to-br from-[#022C22] via-[#065F46] to-[#059669] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#6EE7B7] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#A7F3D0] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-emerald-50/85',
        ],

        'calendar' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#BE123C]/50 bg-gradient-to-br from-[#450A0A] via-[#991B1B] to-[#BE123C] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#FDA4AF] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#FFE4E6] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-rose-50/85',
        ],

        'bookings' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#4338CA]/50 bg-gradient-to-br from-[#1E1B4B] via-[#312E81] to-[#4338CA] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#A5B4FC] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#C7D2FE] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-indigo-50/85',
        ],

        'resources' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-[#64748B]/50 bg-gradient-to-br from-[#0F172A] via-[#334155] to-[#475569] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#CBD5E1] hover:shadow-md',
            'icon' => 'bg-white/12 text-[#E2E8F0] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-slate-50/85',
        ],

        'team' => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-gw-accent/50 bg-gradient-to-br from-gw-primary via-[#243846] to-[#3d5a40] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-gw-accent hover:shadow-md',
            'icon' => 'bg-white/12 text-gw-accent group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-slate-50/85',
        ],

        default => [
            'card' => 'group flex w-full items-center gap-2 rounded-md border border-gw-primary/50 bg-gradient-to-br from-gw-primary via-[#132a4d] to-[#334155] px-3 py-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-gw-accent hover:shadow-md',
            'icon' => 'bg-white/12 text-[#FDE68A] group-hover:bg-white/20',
            'label' => 'text-white',
            'description' => 'text-slate-50/85',
        ],

    };

@endphp



@if ($actions !== [])

    <section>

        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Quick Actions</h2>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

            @foreach ($actions as $action)

                @php
                    $theme = $themeFor($action['icon'] ?? 'default');
                @endphp

                @if (filled($action['action'] ?? null))

                    <button

                        type="button"

                        onclick="Livewire.dispatch(@js($action['action']))"

                        class="{{ $theme['card'] }}"

                    >

                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition {{ $theme['icon'] }}">

                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">

                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconFor($action['icon'] ?? 'default') }}" />

                            </svg>

                        </span>

                        <span class="min-w-0">

                            <span class="block text-xs font-semibold {{ $theme['label'] }}">{{ $action['label'] }}</span>

                            <span class="mt-0.5 block text-[11px] leading-snug {{ $theme['description'] }}">{{ $action['description'] }}</span>

                        </span>

                    </button>

                @else

                    <a href="{{ $action['route'] }}" class="{{ $theme['card'] }}">

                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition {{ $theme['icon'] }}">

                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">

                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconFor($action['icon'] ?? 'default') }}" />

                            </svg>

                        </span>

                        <span class="min-w-0">

                            <span class="block text-xs font-semibold {{ $theme['label'] }}">{{ $action['label'] }}</span>

                            <span class="mt-0.5 block text-[11px] leading-snug {{ $theme['description'] }}">{{ $action['description'] }}</span>

                        </span>

                    </a>

                @endif

            @endforeach

        </div>

    </section>

@endif


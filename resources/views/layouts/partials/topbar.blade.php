@php
    $user = auth()->user();
    $openTaskCount = $user ? app(\App\Http\Controllers\TaskController::class)->openTaskCountFor($user) : 0;
    $todaysAgendaSummary = $user
        ? app(\App\Services\TodaysAgendaService::class)->summaryFor($user)
        : null;
    $user?->loadMissing(['profile', 'rank']);
    $canInviteMembers = $user
        ? app(\App\Services\Members\MemberInvitationService::class)->canCreateInvitation($user)
        : false;
@endphp

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white px-4 py-3 shadow-sm lg:px-8" x-data>
    <div class="flex flex-wrap items-center gap-3">
        <button
            type="button"
            class="rounded-md p-2 text-gw-primary hover:bg-slate-100"
            x-on:click="$store.shell.toggleSidebar()"
            x-bind:aria-expanded="$store.shell.isDesktop ? ! $store.shell.sidebarCollapsed : $store.shell.sidebarOpen"
        >
            <span class="sr-only">Toggle navigation</span>
            <span class="block h-0.5 w-5 bg-current"></span>
            <span class="mt-1 block h-0.5 w-5 bg-current"></span>
            <span class="mt-1 block h-0.5 w-5 bg-current"></span>
        </button>

        @auth
            <form
                method="GET"
                action="{{ route('search.index') }}"
                class="order-3 w-full sm:order-none sm:min-w-72 sm:max-w-md sm:flex-1 lg:max-w-xl"
                x-data="globalSearch()"
                x-on:keydown.escape.window="open = false"
                data-no-page-loader
            >
                <label for="topbar-search" class="sr-only">Search GWTrack</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-3.5-3.5"></path>
                        </svg>
                    </span>
                    <input
                        id="topbar-search"
                        x-ref="searchInput"
                        name="q"
                        x-model="query"
                        x-on:input="onInput()"
                        x-on:focus="query.trim().length >= 2 && (open = true)"
                        x-on:blur="closeSuggestions()"
                        value="{{ request('q') }}"
                        type="search"
                        placeholder="Search members, training, resources..."
                        autocomplete="off"
                        class="h-10 w-full rounded-full border-slate-200 bg-slate-50 pl-10 pr-4 text-sm shadow-sm transition focus:border-gw-accent focus:bg-white focus:ring-gw-accent"
                    >

                    <div
                        x-show="open && (loading || results.length > 0 || query.trim().length >= 2)"
                        x-cloak
                        class="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-50 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                    >
                        <template x-if="loading">
                            <div class="px-4 py-3 text-sm text-slate-500">Searching...</div>
                        </template>
                        <template x-if="! loading && results.length === 0 && query.trim().length >= 2">
                            <div class="px-4 py-3 text-sm text-slate-500">No quick matches. Press Enter for full results.</div>
                        </template>
                        <ul x-show="results.length > 0" class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                            <template x-for="result in results" :key="result.url">
                                <li>
                                    <a
                                        :href="result.url"
                                        wire:navigate
                                        class="block px-4 py-3 transition hover:bg-gw-accent-soft"
                                    >
                                        <p class="text-sm font-semibold text-gw-primary" x-text="result.title"></p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            <span x-text="result.type"></span>
                                            <span x-show="result.subtitle"> · </span>
                                            <span x-text="result.subtitle"></span>
                                        </p>
                                    </a>
                                </li>
                            </template>
                        </ul>
                        <div class="border-t border-slate-100 bg-slate-50 px-4 py-2">
                            <button type="submit" class="text-xs font-semibold text-gw-accent-dark hover:underline">
                                View all results
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @else
            <form method="GET" action="{{ route('search.index') }}" class="order-3 w-full sm:order-none sm:min-w-72 sm:max-w-md sm:flex-1 lg:max-w-xl" data-no-page-loader>
                <label for="topbar-search" class="sr-only">Search GWTrack</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-3.5-3.5"></path>
                        </svg>
                    </span>
                    <input
                        id="topbar-search"
                        name="q"
                        value="{{ request('q') }}"
                        type="search"
                        placeholder="Search members, training, resources..."
                        class="h-10 w-full rounded-full border-slate-200 bg-slate-50 pl-10 pr-4 text-sm shadow-sm transition focus:border-gw-accent focus:bg-white focus:ring-gw-accent"
                    >
                </div>
            </form>
        @endauth

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
            @auth
                @if ($canInviteMembers)
                    <button
                        type="button"
                        class="hidden items-center gap-1.5 rounded-full border border-gw-accent/60 bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-muted sm:inline-flex"
                        title="Invite a new member"
                        onclick="Livewire.dispatch('open-invite-member-modal')"
                    >
                        <svg class="h-4 w-4 text-[#0F766E]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 8v6m3-3h-6"></path>
                        </svg>
                        Invite Member
                    </button>

                    <button
                        type="button"
                        class="gw-icon-btn-lg sm:hidden"
                        title="Invite a member"
                        onclick="Livewire.dispatch('open-invite-member-modal')"
                    >
                        <span class="sr-only">Invite Member</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 8v6m3-3h-6"></path>
                        </svg>
                    </button>
                @endif

                <button
                    type="button"
                    class="gw-icon-btn-lg"
                    title="Assign a task"
                    onclick="Livewire.dispatch('open-assign-task-modal')"
                >
                    <span class="sr-only">Assign a task</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        <path d="M12 11v6"></path>
                        <path d="M9 14h6"></path>
                    </svg>
                </button>

                @if ($todaysAgendaSummary)
                    @include('layouts.partials.todays-agenda-button', ['todaysAgendaSummary' => $todaysAgendaSummary])
                @endif

                <button
                    type="button"
                    onclick="Livewire.dispatch('open-my-tasks-modal')"
                    class="gw-icon-btn-lg relative"
                    title="{{ $openTaskCount > 0 ? $openTaskCount.' open tasks' : 'My Tasks' }}"
                >
                    <span class="sr-only">Open my tasks</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    <livewire:dashboard.open-task-count-badge wire:key="open-task-count-badge" />
                </button>

                @can('view notifications')
                    <livewire:notifications.notification-bell wire:key="notification-bell" />
                @endcan

                <x-dropdown align="right" width="64" contentClasses="bg-white p-0">
                    <x-slot name="trigger">
                        <button type="button" class="flex items-center gap-2 rounded-full border border-slate-200 py-1 pl-1 pr-2 transition hover:border-gw-accent hover:bg-slate-50 sm:pr-3">
                            <x-user-avatar :user="$user" size="sm" class="!h-8 !w-8" />
                            <span class="hidden text-left sm:block">
                                <span class="block max-w-32 truncate text-sm font-semibold text-gw-primary">{{ $user->name }}</span>
                                <span class="block max-w-32 truncate text-xs text-slate-500">{{ $user->topbarRankRoleLabel() }}</span>
                            </span>
                            <svg class="hidden h-4 w-4 text-slate-400 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="w-64 max-w-[calc(100vw-2rem)]">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <div class="truncate text-sm font-semibold text-gw-primary">{{ $user->name }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $user->email }}</div>
                            </div>

                            <div class="py-1">
                                <x-dropdown-link :href="route('dashboard')">Dashboard</x-dropdown-link>
                                <x-dropdown-link :href="route('profile.edit')">My Profile</x-dropdown-link>
                                @if (session(\App\Services\MonetizationSettingsService::SESSION_KEY))
                                    <x-dropdown-link :href="route('billing.index')">My Billing</x-dropdown-link>
                                @endif
                                @if ($canInviteMembers)
                                    <x-dropdown-link :href="route('invites.index')">My Invites</x-dropdown-link>
                                @endif
                                @can('view team')
                                    <x-dropdown-link :href="route('team.directs')">My Team</x-dropdown-link>
                                @endcan
                                <x-dropdown-link :href="route('resources.index')">Resources</x-dropdown-link>

                                @if ($user->hasAnyRole(['super-admin', 'admin', 'agency-owner', 'team-leader', 'certified-field-mentor', 'trainer']))
                                    <x-dropdown-link :href="route('admin.index')">Admin Management</x-dropdown-link>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100" data-no-page-loader>
                                @csrf
                                <button type="submit" class="block w-full px-4 py-3 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            @else
                <a href="{{ route('login') }}" class="gw-button-primary" data-no-navigate>Login</a>
            @endauth
        </div>
    </div>
</header>

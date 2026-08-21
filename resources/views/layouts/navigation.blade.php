@php
    $childLinkClass = fn (bool $active) => 'gw-sidebar-child-link flex items-center rounded-md px-3 py-2 text-sm font-medium transition '.($active
        ? 'bg-white text-gw-primary'
        : 'text-slate-300 hover:bg-white/10 hover:text-white');

    $activeIsStrict = function (array $item): bool {
        $patterns = $item['active'] ?? [$item['route']];

        foreach ((array) $patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                return false;
            }
        }

        return true;
    };

    $canSee = function (array $item): bool {
        if (! Route::has($item['route'])) {
            return false;
        }

        if (! auth()->check()) {
            return ! isset($item['roles']) && ! isset($item['permissions']);
        }

        if (isset($item['roles']) && ! auth()->user()->hasAnyRole($item['roles'])) {
            return false;
        }

        if (isset($item['permissions']) && ! auth()->user()->hasAnyPermission($item['permissions'])) {
            return false;
        }

        if (isset($item['access']) && ! auth()->user()->{$item['access']}()) {
            return false;
        }

        if (isset($item['session']) && ! session($item['session'])) {
            return false;
        }

        return true;
    };

    $isActive = function (array $item): bool {
        if (isset($item['active_resources'])) {
            return request()->routeIs('admin.management.*') && in_array(request()->route('resource'), $item['active_resources'], true);
        }

        if (isset($item['active_resource'])) {
            return request()->routeIs('admin.management.*') && request()->route('resource') === $item['active_resource'];
        }

        $patterns = $item['active'] ?? [$item['route']];

        foreach ((array) $patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                foreach (($item['active_params'] ?? []) as $key => $value) {
                    $actual = request()->route($key);

                    if ($actual === null) {
                        $actual = request()->query($key);
                    }

                    if ((string) $actual !== (string) $value) {
                        return false;
                    }
                }

                foreach (($item['active_query'] ?? []) as $key => $value) {
                    if ((string) request()->query($key, '') !== (string) $value) {
                        return false;
                    }
                }

                foreach (($item['missing_query'] ?? []) as $key) {
                    if (filled(request()->query($key))) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    };

    $user = auth()->user()?->loadMissing('profile');

    $groups = [
        [
            'label' => 'My Workspace',
            'active' => ['dashboard', 'profile.*', 'tasks.*', 'invites.*', 'daily-activities.*', 'messages.*', 'calendar.*', 'bookings.my', 'bookings.calendar', 'billing.*'],
            'items' => [
                ['label' => 'My Dashboard', 'route' => 'dashboard'],
                ['label' => 'My Profile', 'route' => 'profile.edit', 'active' => ['profile.*']],
                ['label' => 'My Tasks', 'route' => 'tasks.index', 'active' => ['tasks.*']],
                [
                    'label' => 'My Invites',
                    'route' => 'invites.index',
                    'roles' => ['member', 'agency-owner', 'team-leader', 'certified-field-mentor'],
                    'active' => ['invites.*'],
                ],
                ['label' => 'My Daily Activities', 'route' => 'daily-activities.index', 'active' => ['daily-activities.*']],
                ['label' => 'My Messages', 'route' => 'messages.index', 'permissions' => ['view conversations'], 'active' => ['messages.*']],
                ['label' => 'My Calendar', 'route' => 'calendar.index', 'active' => ['calendar.*']],
                ['label' => 'My Appointments', 'route' => 'bookings.my', 'permissions' => ['view own bookings'], 'active' => ['bookings.my', 'bookings.calendar', 'bookings.dashboard', 'bookings.availability', 'bookings.event-types', 'bookings.links', 'bookings.requests', 'bookings.settings']],
                ['label' => 'My Billing', 'route' => 'billing.index', 'active' => ['billing.*'], 'session' => \App\Services\MonetizationSettingsService::SESSION_KEY],
            ],
        ],
        [
            'label' => 'My Progress',
            'active' => ['onboarding.*', 'apprenticeship.*', 'licensing.*', 'contracting.*', 'compliance.*', 'assessments.*', 'rank-advancement.*', 'cfm-training.*', 'goals.*', 'team.production'],
            'items' => [
                ['label' => 'My Onboarding', 'route' => 'onboarding.index', 'active' => ['onboarding.*']],
                ['label' => 'Field Apprenticeship', 'route' => 'apprenticeship.index', 'active' => ['apprenticeship.*']],
                ['label' => 'Licensing Tracker', 'route' => 'licensing.index', 'active' => ['licensing.*']],
                ['label' => 'Contracting Tracker', 'route' => 'contracting.index', 'active' => ['contracting.*']],
                ['label' => 'Compliance & Renewals', 'route' => 'compliance.index', 'active' => ['compliance.*']],
                ['label' => 'Assessments', 'route' => 'assessments.index', 'active' => ['assessments.*']],
                ['label' => 'Rank Advancement', 'route' => 'rank-advancement.index', 'active' => ['rank-advancement.*']],
                ['label' => 'CFM Training', 'route' => 'cfm-training.index', 'active' => ['cfm-training.*']],
                ['label' => 'Goals & Performance', 'route' => 'goals.index', 'permissions' => ['manage goals'], 'active' => ['goals.*']],
                ['label' => 'Production Dashboard', 'route' => 'team.production', 'active' => ['team.production']],
            ],
        ],
        [
            'label' => 'Learning & Resources',
            'active' => ['training.*', 'resources.*'],
            'items' => [
                ['label' => 'Training Center', 'route' => 'training.index', 'active' => ['training.*']],
                ['label' => 'Library Home', 'route' => 'resources.index', 'active' => ['resources.index']],
                ['label' => 'Documents', 'route' => 'resources.documents', 'active' => ['resources.documents']],
                ['label' => 'Videos', 'route' => 'resources.videos', 'active' => ['resources.videos']],
                ['label' => 'Recorded Webinars', 'route' => 'resources.recorded-webinars', 'active' => ['resources.recorded-webinars']],
                ['label' => 'Links', 'route' => 'resources.links', 'active' => ['resources.links', 'resources.zoom-links']],
            ],
        ],
        [
            'label' => 'CRM Funnel System',
            'active' => ['crm.*'],
            'items' => [
                ['label' => 'CRM Dashboard', 'route' => 'crm.index', 'permissions' => ['view crm funnels'], 'active' => ['crm.index']],
                ['label' => 'Consultant Tracker', 'route' => 'crm.consultants', 'permissions' => ['view crm funnels'], 'active' => ['crm.consultants']],
                ['label' => 'Leads & Prospects', 'route' => 'crm.funnels.show', 'params' => ['lead-prospect'], 'permissions' => ['view crm funnels'], 'active' => ['crm.funnels.show'], 'active_params' => ['pipeline' => 'lead-prospect']],
                ['label' => 'Sales Funnel', 'route' => 'crm.funnels.show', 'params' => ['sales'], 'permissions' => ['view crm funnels'], 'active' => ['crm.funnels.show'], 'active_params' => ['pipeline' => 'sales']],
                ['label' => 'Recruiting Funnel', 'route' => 'crm.funnels.show', 'params' => ['recruiting'], 'permissions' => ['view crm funnels'], 'active' => ['crm.funnels.show'], 'active_params' => ['pipeline' => 'recruiting']],
                ['label' => 'Onboarding Funnel', 'route' => 'crm.funnels.show', 'params' => ['onboarding'], 'permissions' => ['view crm funnels'], 'active' => ['crm.funnels.show'], 'active_params' => ['pipeline' => 'onboarding']],
                ['label' => 'FAP Funnel', 'route' => 'crm.funnels.show', 'params' => ['fap'], 'permissions' => ['view crm funnels'], 'active' => ['crm.funnels.show'], 'active_params' => ['pipeline' => 'fap']],
                ['label' => 'Licensing Funnel', 'route' => 'crm.funnels.show', 'params' => ['licensing'], 'permissions' => ['view crm funnels'], 'active' => ['crm.funnels.show'], 'active_params' => ['pipeline' => 'licensing']],
                ['label' => 'Compliance Pipeline', 'route' => 'crm.funnels.show', 'params' => ['compliance'], 'permissions' => ['view crm funnels'], 'active' => ['crm.funnels.show'], 'active_params' => ['pipeline' => 'compliance']],
                ['label' => 'Contracting Pipeline', 'route' => 'crm.funnels.show', 'params' => ['contracting'], 'permissions' => ['view crm funnels'], 'active' => ['crm.funnels.show'], 'active_params' => ['pipeline' => 'contracting']],
                ['label' => 'Pipelines & Stages', 'route' => 'crm.pipelines.compare', 'permissions' => ['view crm funnels'], 'active' => ['crm.pipelines.compare']],
            ],
        ],
        [
            'label' => 'Prospect Management',
            'active' => ['team.prospects*', 'team.recruiting.*', 'team.fna.*'],
            'items' => [
                ['label' => 'Summary', 'route' => 'team.prospects', 'permissions' => ['manage prospects'], 'active' => ['team.prospects']],
                ['label' => 'Prospects', 'route' => 'team.prospects.screen', 'params' => ['prospects'], 'permissions' => ['manage prospects'], 'active' => ['team.prospects.screen'], 'active_params' => ['screen' => 'prospects']],
                ['label' => 'Follow-Up Center', 'route' => 'team.prospects.follow-ups', 'permissions' => ['manage prospects'], 'active' => ['team.prospects.follow-ups']],
                ['label' => 'Activity Logs', 'route' => 'team.prospects.screen', 'params' => ['activity-logs'], 'permissions' => ['manage prospects'], 'active' => ['team.prospects.screen'], 'active_params' => ['screen' => 'activity-logs']],
                ['label' => 'Recruiting Pipeline', 'route' => 'team.recruiting.index', 'permissions' => ['manage prospects'], 'active' => ['team.recruiting.*']],
                ['label' => 'Sales Pipeline', 'route' => 'team.prospects.pipeline', 'permissions' => ['manage prospects'], 'active' => ['team.prospects.pipeline']],
                ['label' => 'Appointments', 'route' => 'team.prospects.appointments', 'permissions' => ['manage prospects'], 'active' => ['team.prospects.appointments']],
                ['label' => 'FNA Management', 'route' => 'team.fna.dashboard', 'permissions' => ['manage fna records'], 'active' => ['team.fna.*']],
                ['label' => 'Access Manager', 'route' => 'team.prospects.access-manager', 'permissions' => ['manage prospects'], 'active' => ['team.prospects.access-manager']],
                ['label' => 'Analytics & Goals', 'route' => 'team.prospects.analytics', 'permissions' => ['manage prospects'], 'active' => ['team.prospects.analytics']],
                ['label' => 'AI Coach', 'route' => 'team.prospects.ai-coach', 'permissions' => ['manage prospects'], 'active' => ['team.prospects.ai-coach']],
                ['label' => 'Import Prospects', 'route' => 'team.prospects.import', 'permissions' => ['import prospects'], 'active' => ['team.prospects.import']],
            ],
        ],
        [
            'label' => 'My Team',
            'active' => ['team.index', 'team.tree', 'team.hierarchy', 'team.org-chart', 'team.directs', 'team.downlines'],
            'items' => [
                ['label' => 'Downline Dashboard', 'route' => 'team.index', 'permissions' => ['view own team'], 'active' => ['team.index']],
                ['label' => 'My Directs', 'route' => 'team.directs', 'permissions' => ['view own team'], 'active' => ['team.directs']],
                ['label' => 'All Downlines', 'route' => 'team.downlines', 'permissions' => ['view team table'], 'active' => ['team.downlines']],
                ['label' => 'Genealogy Tree', 'route' => 'team.tree', 'permissions' => ['view team tree'], 'active' => ['team.tree']],
                ['label' => 'Hierarchy Table', 'route' => 'team.hierarchy', 'permissions' => ['view team tree'], 'active' => ['team.hierarchy']],
                ['label' => 'Org Chart', 'route' => 'team.org-chart', 'permissions' => ['view org chart'], 'active' => ['team.org-chart']],
            ],
        ],
        [
            'label' => 'Mentoring & CFM',
            'active' => ['team.cfms*', 'cfm.portal*', 'cfm.effectiveness.*', 'team.trainees', 'bookings.dashboard', 'bookings.availability', 'bookings.event-types', 'bookings.links', 'bookings.requests', 'bookings.settings'],
            'items' => [
                ['label' => 'CFM Portal', 'route' => 'cfm.portal', 'access' => 'canAccessCfmPortal', 'active' => ['cfm.portal*']],
                ['label' => 'CFM Management', 'route' => 'team.cfms', 'access' => 'canAccessCfmManagement', 'active' => ['team.cfms*']],
                ['label' => 'CFM Effectiveness', 'route' => 'cfm.effectiveness.index', 'permissions' => ['view CFM effectiveness'], 'active' => ['cfm.effectiveness.*']],
                ['label' => 'Trainee CFM Evaluation', 'route' => 'cfm.effectiveness.reviews', 'permissions' => ['view own mentor feedback requests'], 'active' => ['cfm.effectiveness.reviews*']],
                ['label' => 'My Trainees', 'route' => 'team.trainees', 'access' => 'canAccessCfmPortal', 'active' => ['team.trainees']],
                ['label' => 'Mentor Scheduling', 'route' => 'bookings.dashboard', 'permissions' => ['view booking dashboard'], 'active' => ['bookings.dashboard', 'bookings.availability', 'bookings.event-types', 'bookings.links', 'bookings.requests', 'bookings.settings']],
            ],
        ],
        [
            'label' => 'Communications',
            'active' => ['support.*', 'communications.*', 'notifications.*', 'events.*'],
            'items' => [
                ['label' => 'Notifications', 'route' => 'notifications.index', 'active' => ['notifications.*']],
                ['label' => 'Help & Support', 'route' => 'support.index', 'permissions' => ['submit support ticket'], 'active' => ['support.*']],
                ['label' => 'Communication Hub', 'route' => 'communications.index', 'active' => ['communications.index']],
                ['label' => 'Leadership Desk', 'route' => 'communications.leadership', 'active' => ['communications.leadership']],
                ['label' => 'Campaigns', 'route' => 'communications.campaigns.index', 'active' => ['communications.campaigns.*']],
                ['label' => 'Recognition', 'route' => 'communications.recognition', 'active' => ['communications.recognition']],
                ['label' => 'Events', 'route' => 'events.index', 'active' => ['events.*']],
            ],
        ],
        [
            'label' => 'Admin Management',
            'active' => ['admin.*'],
            'items' => [
                ['label' => 'Admin Dashboard', 'route' => 'admin.index', 'active' => ['admin.index'], 'roles' => ['super-admin', 'admin', 'agency-owner', 'team-leader', 'certified-field-mentor', 'trainer']],
                ['label' => 'CRM Pipelines & Stages', 'route' => 'admin.crm.pipelines', 'permissions' => ['manage crm funnels'], 'active' => ['admin.crm.*']],
                ['label' => 'Support Queue', 'route' => 'admin.support.index', 'permissions' => ['view all support tickets'], 'active' => ['admin.support.*']],
                ['label' => 'User Management', 'route' => 'admin.users.index', 'active' => ['admin.users.*'], 'roles' => ['super-admin', 'admin', 'agency-owner']],
                ['label' => 'Roles & Permissions', 'route' => 'admin.roles.index', 'permissions' => ['manage roles']],
                ['label' => 'Teams', 'route' => 'admin.management.resource.index', 'params' => ['teams'], 'active_resource' => 'teams', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Ranks', 'route' => 'admin.management.resource.index', 'params' => ['ranks'], 'active_resource' => 'ranks', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Profile Completion Fields', 'route' => 'admin.management.resource.index', 'params' => ['profile-completion-fields'], 'active_resource' => 'profile-completion-fields', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Task Categories', 'route' => 'admin.management.resource.index', 'params' => ['task-categories'], 'active_resource' => 'task-categories', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Tasks', 'route' => 'admin.management.resource.index', 'params' => ['tasks'], 'active_resource' => 'tasks', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Task Assignments', 'route' => 'admin.management.resource.index', 'params' => ['task-users'], 'active_resource' => 'task-users', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Checklists', 'route' => 'admin.checklists.index', 'active' => ['admin.checklists.*'], 'active_resources' => ['checklist-types', 'checklist-instructions', 'checklists'], 'roles' => ['super-admin', 'admin', 'agency-owner', 'team-leader', 'certified-field-mentor', 'trainer']],
                ['label' => 'Calendar Categories', 'route' => 'admin.management.resource.index', 'params' => ['calendar-categories'], 'active_resource' => 'calendar-categories', 'roles' => ['super-admin', 'admin', 'agency-owner']],
                ['label' => 'Calendar Event Types', 'route' => 'admin.management.resource.index', 'params' => ['calendar-event-types'], 'active_resource' => 'calendar-event-types', 'roles' => ['super-admin', 'admin', 'agency-owner']],
                ['label' => 'Calendar Events', 'route' => 'admin.management.resource.index', 'params' => ['calendar-events'], 'active_resource' => 'calendar-events', 'roles' => ['super-admin', 'admin', 'agency-owner']],
                ['label' => 'Booking Event Types', 'route' => 'admin.management.resource.index', 'params' => ['booking-event-types'], 'active_resource' => 'booking-event-types', 'roles' => ['super-admin', 'admin', 'agency-owner']],
                ['label' => 'Booking Links', 'route' => 'admin.management.resource.index', 'params' => ['booking-links'], 'active_resource' => 'booking-links', 'roles' => ['super-admin', 'admin', 'agency-owner']],
                ['label' => 'Bookings', 'route' => 'admin.management.resource.index', 'params' => ['bookings'], 'active_resource' => 'bookings', 'roles' => ['super-admin', 'admin', 'agency-owner']],
                ['label' => 'Email Templates', 'route' => 'admin.management.resource.index', 'params' => ['email-templates'], 'active_resource' => 'email-templates', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Email Template Tokens', 'route' => 'admin.management.resource.index', 'params' => ['email-template-tokens'], 'active_resource' => 'email-template-tokens', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Daily Quote Management', 'route' => 'admin.management.resource.index', 'params' => ['daily-quotes'], 'active_resource' => 'daily-quotes', 'roles' => ['super-admin', 'admin']],
                ['label' => 'Training Management', 'route' => 'admin.training.management', 'active' => ['admin.training.management'], 'permissions' => ['manage training enrollments']],
                ['label' => 'Training Setup', 'route' => 'admin.training.index', 'active' => ['admin.training.index', 'admin.training.courses.*', 'admin.training.paths.*'], 'permissions' => ['manage training']],
                ['label' => 'CFM Certification', 'route' => 'admin.cfm.index', 'permissions' => ['manage CFM certification']],
                ['label' => 'CFM Performance Manager', 'route' => 'admin.cfm-performance.index', 'permissions' => ['manage mentor reviews'], 'active' => ['admin.cfm-performance.*']],
                ['label' => 'All Setup Tables', 'route' => 'admin.management.index', 'active' => ['admin.management.index'], 'roles' => ['super-admin', 'admin']],
                ['label' => 'Backup Operations', 'route' => 'admin.backups.index', 'active' => ['admin.backups.*'], 'roles' => ['super-admin'], 'permissions' => ['view backups']],
                ['label' => 'Admin Settings', 'route' => 'admin.settings', 'roles' => ['super-admin', 'admin', 'agency-owner']],
                ['label' => 'Subscription Payments', 'route' => 'admin.payments', 'roles' => ['super-admin', 'admin', 'agency-owner'], 'session' => \App\Services\MonetizationSettingsService::SESSION_KEY],
            ],
        ],
    ];

    $activeGroup = collect($groups)->first(function ($group) use ($canSee, $isActive) {
        $visibleItems = collect($group['items'])->filter(fn ($item) => $canSee($item))->values();

        return collect($group['active'] ?? [])->contains(fn ($pattern) => request()->routeIs($pattern))
            || $visibleItems->contains(fn ($item) => $isActive($item));
    })['label'] ?? null;

    $activeGroupKey = $activeGroup ? (string) str($activeGroup)->slug() : null;
@endphp

<nav
    id="gw-sidebar-navigation"
    class="space-y-2 px-3 py-5"
    data-server-active-group="{{ $activeGroupKey }}"
>
    <style>
        .gw-sidebar-group-panel[hidden] { display: none; }
        .gw-sidebar-group-button[data-open="true"] { background: rgb(255 255 255 / .1); color: #84BB41; }
        .gw-sidebar-group-button[data-open="true"] .gw-sidebar-group-icon { background: rgb(132 187 65 / .2); color: #84BB41; }
        .gw-sidebar-group-button[data-open="true"] svg { transform: rotate(180deg); }
    </style>

    @foreach ($groups as $group)
        @php
            $visibleItems = collect($group['items'])->filter(fn ($item) => $canSee($item))->values();
            $groupActive = collect($group['active'] ?? [])->contains(fn ($pattern) => request()->routeIs($pattern))
                || $visibleItems->contains(fn ($item) => $isActive($item));
        @endphp

        @if ($visibleItems->isNotEmpty())
            @php
                $groupKey = (string) str($group['label'])->slug();
                $groupId = 'sidebar-group-'.$groupKey;
            @endphp

            <div class="rounded-md">
                <button
                    type="button"
                    id="{{ $groupId }}"
                    class="gw-sidebar-group-button group flex w-full cursor-pointer items-center justify-between rounded-md px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-400 transition hover:bg-white/10 hover:text-white"
                    data-sidebar-group-button
                    data-sidebar-group="{{ $groupKey }}"
                    data-open="{{ $groupActive ? 'true' : 'false' }}"
                    aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                    aria-controls="{{ $groupId }}-panel"
                >
                    <span>{{ $group['label'] }}</span>
                    <span
                        class="gw-sidebar-group-icon flex h-6 w-6 items-center justify-center rounded-full bg-white/10 text-slate-300 transition group-hover:bg-white/15 group-hover:text-white"
                    >
                        <svg class="h-3.5 w-3.5 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </button>

                <div
                    id="{{ $groupId }}-panel"
                    class="gw-sidebar-group-panel mt-1 space-y-1 border-l border-white/10 pl-3"
                    data-sidebar-group-panel
                    data-sidebar-group="{{ $groupKey }}"
                    @if (! $groupActive) hidden @endif
                >
                    @foreach ($visibleItems as $item)
                        @php
                            $itemKey = $groupKey.'-'.str($item['label'])->slug();
                        @endphp

                        <a
                            href="{{ route($item['route'], $item['params'] ?? []) }}"
                            wire:navigate
                            class="{{ $childLinkClass($isActive($item)) }}"
                            data-sidebar-link
                            data-sidebar-group="{{ $groupKey }}"
                            data-sidebar-item="{{ $itemKey }}"
                            data-active-strict="{{ $activeIsStrict($item) ? 'true' : 'false' }}"
                            @if ($isActive($item)) data-server-active-item="{{ $itemKey }}" @endif
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    @auth
        <form method="POST" action="{{ route('logout') }}" class="pt-4">
            @csrf
            <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">
                Log Out
            </button>
        </form>
    @endauth
</nav>

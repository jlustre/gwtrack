<x-app-layout>
    <section class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="Super Admin Control Center"
            description="Full platform oversight for users, roles, ranks, teams, training, CFM certification, and system settings."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-admin.nav-tile
                :href="route('admin.management.index')"
                title="Admin Management"
                description="CRUD access for ranks, teams, onboarding, licensing, content, events, badges, and templates."
                icon="grid"
            />
            <x-admin.nav-tile
                :href="route('admin.users.index')"
                title="User Management"
                description="Change roles, rank, team, status, and sponsorship details."
                icon="users"
            />
            <x-admin.nav-tile
                :href="route('admin.roles.index')"
                title="Roles & Permissions"
                description="Manage role-based access across the portal."
                icon="shield"
            />
            <x-admin.nav-tile
                :href="route('admin.ranks.index')"
                title="Ranks & Teams"
                description="Adjust TA defaults, rank paths, and team placement."
                icon="rank"
            />
            <x-admin.nav-tile
                :href="route('admin.management.resource.index', 'email-templates')"
                title="Email Templates"
                description="Edit transactional email copy, subjects, and activation status."
                icon="mail"
            />
            @if (session(\App\Services\MonetizationSettingsService::SESSION_KEY))
                <x-admin.nav-tile
                    :href="route('admin.payments')"
                    title="Subscription Payments"
                    description="Collect open invoices, record card, cash, check, or bank payments, and review paid history."
                    icon="clipboard"
                />
            @endif
        </div>
    </section>
</x-app-layout>

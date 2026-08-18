<x-app-layout>
    <section class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="Team Leader Workspace"
            description="Team visibility for onboarding, licensing, rank advancement, mentor assignment, and apprentice progress."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-admin.nav-tile
                :href="route('team.index')"
                title="My Team"
                description="View team hierarchy and member progress."
                icon="team"
            />
            <x-admin.nav-tile
                :href="route('rank-advancement.index')"
                title="Rank Advancement"
                description="Track promotion readiness and requirements."
                icon="rank"
            />
            <x-admin.nav-tile
                :href="route('apprenticeship.index')"
                title="Apprenticeship"
                description="Review mentor assignments and field progress."
                icon="mentor"
            />
        </div>
    </section>
</x-app-layout>

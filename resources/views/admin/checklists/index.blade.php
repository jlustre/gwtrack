<x-app-layout>
    <section class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="Checklists"
            description="Unified checklist administration for onboarding, licensing, contracting, FAP, CFM training, mentoring, and future checklist types."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-admin.nav-tile
                :href="route('admin.management.resource.index', 'checklist-types')"
                title="Checklist Types"
                description="Define checklist categories such as onboarding, licensing, contracting, FAP, CFM training, and mentoring."
                icon="list"
            />
            <x-admin.nav-tile
                :href="route('admin.management.resource.index', 'checklist-instructions')"
                title="Checklist Instructions"
                description="Rich-text help content, manual links, and reference URLs shown while members complete each checklist."
                icon="document"
            />
            <x-admin.nav-tile
                :href="route('admin.management.resource.index', 'checklists')"
                title="Checklist Items"
                description="Manage all checklist steps and items across onboarding, licensing, contracting, FAP, CFM training, and CFM mentoring."
                icon="check"
            />
        </div>
    </section>
</x-app-layout>

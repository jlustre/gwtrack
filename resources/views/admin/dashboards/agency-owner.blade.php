<x-app-layout>
    <section class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="Agency Owner Dashboard"
            description="Agency-level controls for users, ranks, teams, licensing progress, training, mentorship, and announcements."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-admin.nav-tile
                :href="route('admin.users.index')"
                title="Agency Users"
                description="Change member role, rank, and team assignment."
                icon="users"
            />
            <x-admin.nav-tile
                :href="route('admin.training.index')"
                title="Training Operations"
                description="Review training readiness and publishing queues."
                icon="book"
            />
            <x-admin.nav-tile
                :href="route('admin.cfm.index')"
                title="CFM Certification"
                description="Monitor mentor readiness and approval workflow."
                icon="badge"
            />
        </div>
    </section>
</x-app-layout>

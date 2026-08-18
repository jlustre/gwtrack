<x-app-layout>
    <section class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="Certified Field Mentor Workspace"
            description="Mentor-focused workspace for assigned apprentices, notes, sessions, and Field Apprenticeship Program progress."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-admin.nav-tile
                :href="route('apprenticeship.index')"
                title="Apprentice Roster"
                description="View apprentices assigned to you."
                icon="mentor"
            />
            <x-admin.nav-tile
                :href="route('training.index')"
                title="Training Center"
                description="Support trainee academy progress and enrollments."
                icon="book"
            />
            <x-admin.nav-tile
                :href="route('resources.index')"
                title="Resources"
                description="Share mentoring resources and playbooks."
                icon="resource"
            />
        </div>
    </section>
</x-app-layout>

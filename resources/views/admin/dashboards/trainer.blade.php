<x-app-layout>
    <section class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="Trainer Workspace"
            description="Training and assessment workspace for modules, lessons, assessments, and learning progress."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-admin.nav-tile
                :href="route('admin.training.index')"
                title="Training Manager"
                description="Create and organize training modules."
                icon="book"
            />
            <x-admin.nav-tile
                :href="route('assessments.index')"
                title="Assessments"
                description="Build and review assessments tied to courses."
                icon="clipboard"
            />
            <x-admin.nav-tile
                :href="route('resources.index')"
                title="Resources"
                description="Manage training support materials and documents."
                icon="resource"
            />
        </div>
    </section>
</x-app-layout>

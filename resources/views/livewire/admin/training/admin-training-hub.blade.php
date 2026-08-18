<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin · Academy Builder"
        title="Training Content Studio"
        description="Build courses, lessons, and learning paths for the GWTrack Academy. Publish content to the member Training Center."
        class="rounded-xl"
    />

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'Published courses', 'value' => $stats['courses_published'], 'theme' => 'emerald', 'subtitle' => 'Live in academy'],
                ['label' => 'Draft courses', 'value' => $stats['courses_draft'], 'theme' => 'amber', 'subtitle' => 'Not yet published'],
                ['label' => 'Lessons', 'value' => $stats['lessons'], 'theme' => 'navy', 'subtitle' => 'Across all courses'],
                ['label' => 'Learning paths', 'value' => $stats['paths'], 'theme' => 'gold', 'subtitle' => 'Role-based sequences'],
                ['label' => 'Certifications', 'value' => $stats['certifications'], 'theme' => 'cyan', 'subtitle' => 'Credential programs'],
            ] as $card)
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :subtitle="$card['subtitle']"
                    :theme="$card['theme']"
                />
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-admin.nav-tile
            :href="route('admin.training.courses.index')"
            title="Course Builder"
            description="Create and edit academy courses with lessons, publishing rules, and academy metadata."
            icon="book"
        />
        <x-admin.nav-tile
            :href="route('admin.training.paths.index')"
            title="Learning Path Builder"
            description="Assemble role-based paths from published courses with required sequencing."
            icon="path"
        />
        <x-admin.nav-tile
            :href="route('admin.training.management')"
            title="Training Management"
            description="Enroll associates in learning paths or assign a CFM enroll task."
            icon="users"
        />
        <x-admin.nav-tile
            :href="route('training.assignments.manage')"
            title="Assign Training"
            description="Assign courses to members with due dates and track completion."
            icon="clipboard"
        />
        <x-admin.nav-tile
            :href="route('admin.management.resource.index', 'training-categories')"
            title="Categories"
            description="Manage training category taxonomy via Admin Management tables."
            icon="folder"
        />
        <x-admin.nav-tile
            :href="route('admin.management.resource.index', 'assessments')"
            title="Assessments & Questions"
            description="Build assessments, questions, and answers linked to courses."
            icon="check"
        />
        <x-admin.nav-tile
            :href="route('training.index')"
            title="Preview Training Center"
            description="Open the member-facing academy to verify published content."
            icon="eye"
        />
    </div>
</div>

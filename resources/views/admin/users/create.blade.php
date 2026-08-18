<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="Add User"
            description="Create a portal user and assign role, rank, team, sponsor, and status."
        />

        <form method="POST" action="{{ route('admin.users.store') }}" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            @include('admin.users.partials.form', [
                'managedUser' => null,
                'roles' => $roles,
                'ranks' => $ranks,
                'teams' => $teams,
                'sponsors' => $sponsors,
                'mentors' => $mentors,
                'locationOptions' => $locationOptions,
                'profile' => null,
            ])

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gw-primary-hover">Create User</button>
            </div>
        </form>
    </div>
</x-app-layout>

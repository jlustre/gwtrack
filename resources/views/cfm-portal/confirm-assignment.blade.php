<x-guest-layout>
    <div class="mx-auto max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">CFM Assignment</p>
        <h1 class="mt-2 text-xl font-semibold text-gw-primary">Confirm your new trainee</h1>

        <p class="mt-4 text-sm leading-6 text-slate-600">
            {{ $assignment->assignedBy?->name ?? 'Agency leadership' }} assigned
            <span class="font-semibold text-gw-primary">{{ $assignment->apprentice->name }}</span>
            to you as a Field Apprenticeship trainee.
        </p>

        <p class="mt-3 text-sm leading-6 text-slate-600">
            Sign in as <span class="font-semibold">{{ $assignment->mentor->name }}</span> to confirm this assignment.
        </p>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="{{ $loginUrl }}" class="inline-flex flex-1 items-center justify-center rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#132a4d]">
                Sign in to confirm
            </a>
            <a href="{{ route('cfm.portal') }}" class="inline-flex flex-1 items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Open CFM portal
            </a>
        </div>
    </div>
</x-guest-layout>

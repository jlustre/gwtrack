<x-app-layout>
    @php
        $memberProfile = $member->profile;
    @endphp

    <section class="mx-auto max-w-3xl space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">CFM Assignment</p>
                <h1 class="mt-1 text-2xl font-semibold text-gw-primary">Confirm your new trainee</h1>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $assignment->assignedBy?->name ?? 'Agency leadership' }} assigned
                    <span class="font-semibold text-gw-primary">{{ $member->name }}</span>
                    to you as a Field Apprenticeship trainee.
                </p>
            </div>

            <div class="space-y-5 px-6 py-5">
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Trainee details</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-slate-500">Name</dt>
                            <dd class="font-semibold text-gw-primary">{{ $member->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Email</dt>
                            <dd class="font-semibold text-gw-primary">{{ $member->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Rank</dt>
                            <dd class="font-semibold text-gw-primary">{{ $member->rank?->code ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Sponsor</dt>
                            <dd class="font-semibold text-gw-primary">{{ $member->sponsor?->name ?? 'Not assigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Location</dt>
                            <dd class="font-semibold text-gw-primary">
                                {{ collect([$memberProfile?->city, $memberProfile?->province, $memberProfile?->country])->filter()->implode(', ') ?: '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Start date</dt>
                            <dd class="font-semibold text-gw-primary">{{ $assignment->started_at?->format('M j, Y') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gw-accent/30 bg-gw-accent-soft p-4 text-sm text-slate-700">
                    <p class="font-semibold text-gw-primary">What happens when you confirm?</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>{{ $member->name }} becomes your active trainee in the CFM portal.</li>
                        <li>Onboarding, training, and field apprenticeship tracking can begin.</li>
                        <li>Your confirmation task in My Tasks will be marked complete automatically.</li>
                    </ul>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <a
                        href="{{ route('cfm.portal') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Back to CFM portal
                    </a>

                    <form method="POST" action="{{ route('cfm.portal.assignments.confirm', $assignment) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-gw-accent px-5 py-2.5 text-sm font-bold text-gw-primary transition hover:bg-gw-accent-hover sm:w-auto"
                        >
                            Confirm assignment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

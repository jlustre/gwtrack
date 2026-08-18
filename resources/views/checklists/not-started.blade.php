<x-app-layout>
    <section class="mx-auto max-w-3xl space-y-6 py-8">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="border-b border-slate-200 bg-gw-primary px-6 py-8 text-white">
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">{{ $checklistTypeName }}</p>
                <h1 class="mt-2 text-2xl font-semibold">Not started yet</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                    @if ($canStartNow ?? false)
                        @if ($isSelfStart ?? false)
                            This checklist has not been started yet. Choose your Day 1 below to open the tracker and begin working through your checklist items.
                        @else
                            This checklist has not been opened yet. Set Day 1 below to open the tracker and view checklist items.
                        @endif
                    @else
                        @if ($isSelfStart ?? false)
                            This checklist is not available to start yet. Complete the required prerequisites below, then return here to begin.
                        @else
                            This checklist has not been opened for you yet. Your Agency Owner or Certified Field Mentor will start it and set your Day 1 schedule when you are ready to begin.
                        @endif
                    @endif
                </p>
            </div>
            <div class="space-y-4 px-6 py-6 text-sm text-slate-600">
                @if ($canStartNow ?? false)
                    <form
                        method="POST"
                        action="{{ ($isSelfStart ?? false) ? route('checklists.type.start', $typeCode) : route('team.member.checklist-type.start', [$member, $typeCode]) }}"
                        class="rounded-md border border-gw-accent/40 bg-gw-accent-soft p-4"
                    >
                        @csrf
                        @if (! empty($taskUserId))
                            <input type="hidden" name="task_user" value="{{ $taskUserId }}">
                        @endif
                        <p class="font-semibold text-gw-primary">Start checklist schedule</p>
                        <p class="mt-1 text-slate-600">
                            @if ($isSelfStart ?? false)
                                Day 1 is the date you choose below. Your expected due dates will be calculated from this start date.
                            @else
                                Day 1 is the date you choose below — not the member join date.
                            @endif
                        </p>
                        @if (! empty($unmetPrerequisites))
                            <p class="mt-2 text-xs text-amber-800">
                                Note: typical prerequisites ({{ implode(', ', $unmetPrerequisites) }}) are not complete. Admins may still start this checklist for testing.
                            </p>
                        @endif
                        @if ($errors->any())
                            <p class="mt-3 text-sm text-red-700">{{ $errors->first() }}</p>
                        @endif
                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            <label class="block flex-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Day 1 start date</span>
                                <input
                                    type="date"
                                    name="started_at"
                                    value="{{ old('started_at', now()->toDateString()) }}"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                            </label>
                            <button class="inline-flex items-center justify-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                                Start checklist
                            </button>
                        </div>
                    </form>
                @else
                    @if (! ($isSelfStart ?? false))
                        <p>Once started, this tracker will appear on your dashboard and in the navigation menu with your expected due dates.</p>
                    @endif
                    @if (! empty($unmetPrerequisites))
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                            <p class="font-semibold">Prerequisites required</p>
                            <p class="mt-1">Complete these checklist types first: {{ implode(', ', $unmetPrerequisites) }}.</p>
                        </div>
                    @endif
                    @if (! ($isSelfStart ?? false))
                        <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="font-semibold text-gw-primary">Need help?</p>
                            <p class="mt-1">Contact your sponsor, Agency Owner, or CFM if you believe this checklist should already be active.</p>
                        </div>
                    @endif
                @endif
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">
                    Back to dashboard
                </a>
            </div>
        </div>
    </section>
</x-app-layout>

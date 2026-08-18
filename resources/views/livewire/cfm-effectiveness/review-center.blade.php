<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-gw-accent-soft to-gw-primary-soft shadow-sm">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-gw-accent/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 left-10 h-48 w-48 rounded-full bg-gw-primary/5 blur-3xl"></div>

        <div class="relative grid gap-8 px-6 py-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:px-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-gw-accent-dark">Trainee CFM Evaluation</p>
                <h2 class="mt-3 text-3xl font-semibold leading-tight text-gw-primary md:text-4xl">Grade your Certified Field Mentor</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                    Rate every mentoring aspect with <span class="font-semibold text-gw-accent-dark">1–5 stars</span>.
                    Your responses are anonymous — leadership only sees aggregated trends that help CFMs improve.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($cfm)
                        <button
                            type="button"
                            wire:click="startEvaluation"
                            class="inline-flex items-center gap-2 rounded-full bg-gw-accent px-5 py-2.5 text-sm font-bold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z"/></svg>
                            Start / Continue Evaluation
                        </button>
                    @endif
                    <div class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-600">
                        {{ $aspectCount }} mentoring aspects · {{ count($categories) }} categories
                    </div>
                </div>

                @if ($statusMessage)
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ $statusMessage }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @if ($cfm)
                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">Your CFM</p>
                    <div class="mt-4 flex items-center gap-4">
                        <x-user-avatar :user="$cfm" size="lg" class="!h-16 !w-16 ring-2 ring-gw-accent/50" />
                        <div>
                            <p class="text-xl font-semibold text-gw-primary">{{ $cfm->name }}</p>
                            <p class="text-sm text-slate-500">{{ $cfm->email }}</p>
                        </div>
                    </div>
                    <p class="mt-5 text-sm leading-6 text-slate-600">
                        Evaluate how well they supported your onboarding, coaching, accountability, systems training, and personal growth.
                    </p>
                @else
                    <p class="text-sm font-semibold text-gw-primary">No CFM assigned yet</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Once a Certified Field Mentor is assigned to you, you can begin a full star evaluation from this page.
                    </p>
                @endif
            </div>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($categories as $meta)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-semibold text-gw-primary">{{ $meta['label'] }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-600">{{ $meta['description'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gw-primary">Pending evaluations</h3>
                <p class="mt-1 text-sm text-slate-600">Complete these when ready — milestone and requested reviews appear here.</p>
            </div>
            <p class="text-sm font-semibold text-gw-accent-dark">{{ $pendingReviews->count() }} open</p>
        </div>

        @if ($pendingReviews->isEmpty())
            <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                <p class="text-sm font-semibold text-gw-primary">No pending review invitations</p>
                <p class="mt-2 text-sm text-slate-600">You can still start a trainee-initiated evaluation for your current CFM using the button above.</p>
            </div>
        @else
            <div class="mt-6 space-y-3">
                @foreach ($pendingReviews as $review)
                    <div class="flex flex-col gap-4 rounded-xl border border-gw-accent/25 bg-gradient-to-r from-gw-accent-soft via-white to-white p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">
                                {{ config("cfm-effectiveness.review_triggers.{$review->trigger_type}.label") ?? ucfirst(str_replace('_', ' ', $review->trigger_type)) }}
                            </p>
                            <p class="mt-1 text-lg font-semibold text-gw-primary">{{ $review->cfm->name ?? 'Your CFM' }}</p>
                            @if ($review->due_at)
                                <p class="mt-1 text-xs text-slate-500">Due {{ $review->due_at->format('M j, Y') }}</p>
                            @endif
                        </div>
                        <a
                            href="{{ route('cfm.effectiveness.reviews.show', $review) }}"
                            wire:navigate
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-gw-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-gw-primary-hover"
                        >
                            Continue with stars
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gw-primary">Your submitted evaluations</h3>
        <p class="mt-1 text-sm text-slate-600">Past anonymous submissions (details stay private; only your completion history is shown).</p>

        @if ($completedReviews->isEmpty())
            <p class="mt-6 text-sm text-slate-500">You have not submitted a CFM evaluation yet.</p>
        @else
            <div class="mt-5 divide-y divide-slate-100 rounded-xl border border-slate-200">
                @foreach ($completedReviews as $review)
                    <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-gw-primary">{{ $review->cfm->name ?? 'CFM' }}</p>
                            <p class="text-xs text-slate-500">
                                {{ config("cfm-effectiveness.review_triggers.{$review->trigger_type}.label") ?? $review->trigger_type }}
                                · Submitted {{ optional($review->submitted_at)->format('M j, Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 text-sm font-bold text-gw-accent-dark">
                            <svg class="h-4 w-4 text-gw-accent" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z"/></svg>
                            {{ number_format((float) $review->average_rating, 1) }} / 5 avg
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin · Mentorship Quality"
        title="CFM Performance Manager"
        description="Define every CFM mentoring aspect associates will grade, monitor star-based evaluation trends, and request trainee evaluations for active mentor assignments."
    >
        <x-slot:actions>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3">
                    <p class="text-2xl font-bold text-gw-accent">{{ $activeAspectCount }}</p>
                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-300">Active aspects</p>
                </div>
                <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3">
                    <p class="text-2xl font-bold text-gw-accent">{{ number_format($agencyAverage, 1) }}</p>
                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-300">Agency avg ★</p>
                </div>
                <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3">
                    <p class="text-2xl font-bold text-gw-accent">{{ $pendingCount }}</p>
                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-300">Pending evals</p>
                </div>
            </div>
        </x-slot:actions>
    </x-admin.page-hero>

    @if ($statusMessage)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        @foreach ([
            'aspects' => 'Mentoring Aspects',
            'scores' => 'CFM Scoreboard',
            'requests' => 'Evaluation Requests',
        ] as $key => $label)
            <button
                type="button"
                wire:click="setTab('{{ $key }}')"
                class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $tab === $key ? 'bg-gw-primary text-white' : 'border border-slate-300 bg-white text-slate-600 hover:border-gw-accent hover:text-gw-primary' }}"
            >
                {{ $label }}
            </button>
        @endforeach
        <a
            href="{{ route('cfm.effectiveness.reviews') }}"
            wire:navigate
            class="rounded-full border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-muted"
        >
            Open Trainee Evaluation →
        </a>
    </div>

    @if ($tab === 'aspects')
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-5">
                @forelse ($categories as $categoryKey => $meta)
                    @php $categoryQuestions = $questionsByCategory->get($categoryKey, collect()); @endphp
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-gw-accent-soft via-white to-white px-5 py-4">
                            <h2 class="text-lg font-semibold text-gw-primary">{{ $meta['label'] }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $meta['description'] }}</p>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($categoryQuestions as $question)
                                @php $stats = $responseStats->get($question->id); @endphp
                                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between {{ $question->is_active ? '' : 'bg-slate-50 opacity-70' }}">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gw-primary">{{ $question->question }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold uppercase tracking-wide">{{ $question->key }}</span>
                                            @if ($stats)
                                                <span>Avg ★ {{ number_format((float) $stats->avg_rating, 1) }} · {{ $stats->response_count }} ratings</span>
                                            @else
                                                <span>No ratings yet</span>
                                            @endif
                                            <span class="{{ $question->is_active ? 'text-emerald-700' : 'text-slate-400' }}">
                                                {{ $question->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" wire:click="moveQuestion({{ $question->id }}, 'up')" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">↑</button>
                                        <button type="button" wire:click="moveQuestion({{ $question->id }}, 'down')" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">↓</button>
                                        <button type="button" wire:click="editQuestion({{ $question->id }})" class="rounded-md border border-gw-accent/40 bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-primary">Edit</button>
                                        <button type="button" wire:click="toggleQuestion({{ $question->id }})" class="rounded-md border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            {{ $question->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="px-5 py-6 text-sm text-slate-500">No aspects in this category yet.</p>
                            @endforelse
                        </div>
                    </section>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No mentoring categories configured.</p>
                @endforelse
            </div>

            <aside class="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:sticky xl:top-24">
                <h3 class="text-lg font-semibold text-gw-primary">{{ $editingQuestionId ? 'Edit Aspect' : 'Add Mentoring Aspect' }}</h3>
                <p class="mt-1 text-sm text-slate-600">These items appear on the Trainee CFM Evaluation star form.</p>

                <form wire:submit="saveQuestion" class="mt-5 space-y-4">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Category</label>
                        <select wire:model="questionCategory" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($categories as $categoryKey => $meta)
                                <option value="{{ $categoryKey }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Aspect statement</label>
                        <textarea
                            wire:model="questionText"
                            rows="4"
                            placeholder="e.g. Helped me prepare for my first client presentation"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        ></textarea>
                        @error('questionText') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-gw-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-gw-primary-hover">
                            {{ $editingQuestionId ? 'Save Changes' : 'Add Aspect' }}
                        </button>
                        @if ($editingQuestionId)
                            <button type="button" wire:click="cancelEdit" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>

                <div class="mt-6 rounded-lg border border-gw-accent/30 bg-gw-accent-soft p-4 text-sm text-gw-primary">
                    <p class="font-semibold">Star scale</p>
                    <ul class="mt-2 space-y-1 text-xs text-slate-700">
                        @foreach (config('cfm-effectiveness.star_labels') as $stars => $label)
                            <li><span class="font-bold text-gw-accent-dark">{{ $stars }}★</span> — {{ $label }}</li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    @endif

    @if ($tab === 'scores')
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-semibold text-gw-primary">CFM Star Scoreboard</h2>
                <p class="mt-1 text-sm text-slate-600">Aggregated trainee evaluation averages (1–5 stars). Based on {{ $submittedCount }} submitted evaluations.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">CFM</th>
                            <th class="px-5 py-3">Average stars</th>
                            <th class="px-5 py-3">Evaluations</th>
                            <th class="px-5 py-3">Visual</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cfmScores as $row)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-gw-primary">{{ $row['cfm']?->name ?? 'Unknown CFM' }}</td>
                                <td class="px-5 py-4 tabular-nums font-bold text-gw-accent-dark">{{ number_format($row['avg_rating'], 2) }} / 5</td>
                                <td class="px-5 py-4 text-slate-600">{{ $row['review_count'] }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-0.5 text-gw-accent">
                                        @for ($s = 1; $s <= 5; $s++)
                                            <svg class="h-4 w-4 {{ $row['avg_rating'] >= $s - 0.25 ? 'text-gw-accent' : 'text-slate-200' }}" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z"/></svg>
                                        @endfor
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-slate-500">No submitted trainee evaluations yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'requests')
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-lg font-semibold text-gw-primary">Active Mentor Assignments</h2>
                    <p class="mt-1 text-sm text-slate-600">Request a trainee evaluation for an active CFM–associate pair.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($activeAssignments as $assignment)
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-gw-primary">{{ $assignment->apprentice?->name }}</p>
                                <p class="text-sm text-slate-600">CFM: {{ $assignment->mentor?->name }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="requestReviewForAssignment({{ $assignment->id }})"
                                class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover"
                            >
                                Request Evaluation
                            </button>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-sm text-slate-500">No active mentor assignments found.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-lg font-semibold text-gw-primary">Pending Evaluations</h2>
                    <p class="mt-1 text-sm text-slate-600">Waiting for associates to submit star ratings.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($pendingReviews as $review)
                        <div class="px-5 py-4">
                            <p class="font-semibold text-gw-primary">{{ $review->trainee?->name }} → {{ $review->cfm?->name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ config("cfm-effectiveness.review_triggers.{$review->trigger_type}.label") ?? $review->trigger_type }}
                                @if ($review->due_at)
                                    · Due {{ $review->due_at->format('M j, Y') }}
                                @endif
                            </p>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-sm text-slate-500">No pending evaluations.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
</div>

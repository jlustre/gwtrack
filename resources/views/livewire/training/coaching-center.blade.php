<div class="space-y-6">
    @if (session('coaching_status') === 'review-submitted')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Coaching review submitted.</div>
    @elseif (session('coaching_status') === 'fap-signed-off')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">FAP sign-off recorded.</div>
    @elseif (session('coaching_status') === 'session-registered')
        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800">Registered for session.</div>
    @elseif (session('coaching_status') === 'session-created')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Coaching session scheduled.</div>
    @endif

    @if ($hub['is_trainee'] || $hub['trainee']['fap_started'])
        @php $trainee = $hub['trainee']; @endphp
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gw-primary">My FAP Progress</h2>
                <p class="mt-1 text-xs text-slate-500">Field Apprenticeship Program development tracker</p>

                <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-4xl font-bold text-gw-primary">{{ $trainee['fap_percent'] }}%</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $trainee['fap_started'] ? 'Checklist in progress' : 'FAP not started yet' }}
                        </p>
                    </div>
                    <a href="{{ route('apprenticeship.index') }}" class="inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-primary-hover">
                        Open FAP checklist
                    </a>
                </div>

                <div class="mt-4 h-3 rounded-full bg-slate-200">
                    <div class="h-3 rounded-full bg-gw-accent" style="width: {{ $trainee['fap_percent'] }}%"></div>
                </div>

                @if ($trainee['fap_signoff'])
                    <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-sm font-semibold text-emerald-900">FAP signed off by {{ $trainee['fap_signoff']->mentor?->name }}</p>
                        <p class="mt-1 text-xs text-emerald-800">{{ $trainee['fap_signoff']->created_at?->format('M j, Y') }}</p>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm">
                <h3 class="font-semibold text-gw-primary">Your CFM</h3>
                @if ($trainee['mentor'])
                    <p class="mt-2 text-sm text-slate-700">{{ $trainee['mentor']->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $trainee['mentor']->email }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-600">No active mentor assignment on file.</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Coaching feedback</h2>
            <div class="mt-4 space-y-3">
                @forelse ($trainee['reviews'] as $review)
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gw-primary">{{ config('training-academy.coaching.review_types.'.$review->review_type, str($review->review_type)->title()) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $review->mentor?->name }} · {{ $review->created_at?->format('M j, Y') }}</p>
                                @if ($review->feedback)
                                    <p class="mt-2 text-sm text-slate-600">{{ $review->feedback }}</p>
                                @endif
                            </div>
                            @if ($review->score !== null)
                                <span class="rounded-full bg-gw-primary px-2 py-0.5 text-[0.65rem] font-bold text-gw-accent">{{ $review->score }}%</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">Coaching reviews from your CFM will appear here.</p>
                @endforelse
            </div>
        </div>
    @endif

    @if ($hub['is_mentor'] && $hub['mentor'])
        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">My Trainees</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($hub['mentor']['trainees'] as $row)
                        <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gw-primary">{{ $row['trainee']->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">FAP {{ $row['fap_percent'] }}%</p>
                                    <div class="mt-2 h-1.5 max-w-xs rounded-full bg-slate-200">
                                        <div class="h-1.5 rounded-full bg-gw-accent" style="width: {{ $row['fap_percent'] }}%"></div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    @if ($row['can_sign_off'])
                                        <button type="button" wire:click="signOffFap({{ $row['trainee']->id }})" class="inline-flex rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">
                                            FAP sign-off
                                        </button>
                                    @elseif ($row['has_signoff'])
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase text-emerald-800">Signed off</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-600">No active trainees assigned.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Submit coaching review</h2>
                <form wire:submit="submitReview" class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Trainee</label>
                        <select wire:model="traineeId" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Select trainee</option>
                            @foreach ($mentorTrainees as $trainee)
                                <option value="{{ $trainee->id }}">{{ $trainee->name }}</option>
                            @endforeach
                        </select>
                        @error('traineeId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Review type</label>
                        <select wire:model="reviewType" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($hub['review_types'] as $key => $label)
                                @if ($key !== 'fap_signoff')
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Score (optional)</label>
                        <input type="number" min="0" max="100" wire:model="score" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Feedback</label>
                        <textarea wire:model="feedback" rows="4" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Coaching notes, observations, next steps..."></textarea>
                    </div>
                    <button type="submit" class="inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                        Submit review
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-gw-primary">Upcoming coaching sessions</h2>
                <a href="{{ route('training.sessions.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">All live sessions &rarr;</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($hub['sessions'] as $row)
                    @php $session = $row['session']; @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <a href="{{ route('training.sessions.show', $session) }}" class="font-semibold text-gw-primary transition hover:text-gw-accent">{{ $session->title }}</a>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $session->starts_at?->format('M j, Y g:i A') }}
                                    · {{ config('training-academy.coaching.session_types.'.$session->session_type, $session->session_type) }}
                                </p>
                                @if ($session->instructor)
                                    <p class="mt-1 text-xs text-slate-500">Led by {{ $session->instructor->name }}</p>
                                @endif
                                @if ($row['calendar_url'])
                                    <a href="{{ $row['calendar_url'] }}" class="mt-2 inline-flex text-xs font-semibold text-gw-accent underline">View in calendar</a>
                                @endif
                            </div>
                            @if ($row['registered'])
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase text-emerald-800">Registered</span>
                            @elseif ($row['seats_remaining'] === 0)
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[0.65rem] font-bold uppercase text-slate-600">Full</span>
                            @else
                                <button type="button" wire:click="registerSession({{ $session->id }})" class="inline-flex rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-white">
                                    Register
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">No upcoming sessions scheduled.</p>
                @endforelse
            </div>
        </div>

        @if ($hub['is_mentor'])
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Schedule a session</h2>
                <form wire:submit="createSession" class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Title</label>
                        <input type="text" wire:model="sessionTitle" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @error('sessionTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Type</label>
                        <select wire:model="sessionType" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach (config('training-academy.coaching.session_types', []) as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Starts at</label>
                        <input type="datetime-local" wire:model="sessionStartsAt" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @error('sessionStartsAt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Capacity (optional)</label>
                        <input type="number" min="1" wire:model="sessionCapacity" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Description</label>
                        <textarea wire:model="sessionDescription" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                    </div>
                    <button type="submit" class="inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-primary-hover">
                        Create session
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

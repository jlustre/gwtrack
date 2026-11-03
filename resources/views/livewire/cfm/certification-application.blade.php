<section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">Next step</p>
            <h2 class="mt-1 text-xl font-semibold text-gw-primary">CFM Certification Review</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Once you meet the rank and training requirements, submit your application for an authorized review.
                CFM access is activated only after approval.
            </p>
        </div>
        @if (auth()->user()->hasRole('certified-field-mentor'))
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Certified</span>
        @elseif ($latestRequest?->status === 'pending')
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Review pending</span>
        @elseif ($latestRequest?->status === 'rejected')
            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">Changes requested</span>
        @endif
    </div>

    @if (session('cfm-certification-status'))
        <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('cfm-certification-status') }}
        </div>
    @endif

    @if ($latestRequest?->status === 'rejected' && $latestRequest->review_notes)
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <span class="font-semibold">Reviewer feedback:</span> {{ $latestRequest->review_notes }}
        </div>
    @endif

    @if ($latestRequest?->status === 'approved')
        <p class="mt-4 text-sm text-slate-600">
            Approved {{ $latestRequest->reviewed_at?->format('M j, Y') }} by {{ $latestRequest->reviewer?->name ?? 'an authorized reviewer' }}.
        </p>
    @elseif ($latestRequest?->status === 'pending')
        <p class="mt-4 text-sm text-slate-600">
            Submitted {{ $latestRequest->created_at->format('M j, Y') }}. You can continue to view your checklist while the request is under review.
        </p>
    @elseif (auth()->user()->hasRole('certified-field-mentor'))
        <p class="mt-4 text-sm text-slate-600">Your Certified Field Mentor access is active.</p>
    @else
        <div class="mt-5 grid gap-2 sm:grid-cols-2">
            <div class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                <span class="{{ $eligibility['checks']['rank'] ? 'text-emerald-700' : 'text-slate-600' }}">
                    {{ $eligibility['checks']['rank'] ? '✓' : '○' }} Associate rank or higher
                </span>
            </div>
            <div class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                <span class="{{ $eligibility['checks']['training_started'] ? 'text-emerald-700' : 'text-slate-600' }}">
                    {{ $eligibility['checks']['training_started'] ? '✓' : '○' }} CFM Training started
                </span>
            </div>
            <div class="rounded-md border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
                <span class="{{ $eligibility['checks']['required_training'] ? 'text-emerald-700' : 'text-slate-600' }}">
                    {{ $eligibility['completed_required'] }} of {{ $eligibility['total_required'] }} required CFM modules confirmed
                </span>
            </div>
        </div>

        @if ($eligibility['missing'])
            <ul class="mt-4 list-inside list-disc space-y-1 text-sm text-amber-800">
                @foreach ($eligibility['missing'] as $requirement)
                    <li>{{ $requirement }}</li>
                @endforeach
            </ul>
        @endif

        <form wire:submit="submit" class="mt-5 space-y-3">
            <label for="cfm-request-notes" class="block text-sm font-medium text-slate-700">Application notes (optional)</label>
            <textarea
                id="cfm-request-notes"
                wire:model="requestNotes"
                rows="3"
                maxlength="2000"
                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                placeholder="Share your mentoring experience, goals, or context for the reviewer."
            ></textarea>
            @error('requestNotes') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            @error('application') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            <button
                type="submit"
                @disabled(! $eligibility['eligible'])
                class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Submit CFM certification request
            </button>
        </form>
    @endif
</section>

<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <header class="rounded-lg bg-gw-primary px-6 py-7 text-white">
        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Mentorship &amp; Certification</p>
        <h1 class="mt-2 text-2xl font-semibold">CFM Certification Review</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
            Review self-applications and team nominations. Approval rechecks rank and required CFM training before granting CFM access.
        </p>
    </header>

    @if (session('cfm-review-status'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('cfm-review-status') }}
        </div>
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gw-primary">Pending requests</h2>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">{{ $pendingRequests->total() }} pending</span>
        </div>

        @forelse ($pendingRequests as $request)
            <article wire:key="cfm-request-{{ $request->id }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-gw-primary">{{ $request->user?->name ?? 'Deleted member' }}</h3>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $request->user?->rank?->name ?? 'No rank' }}</span>
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ str($request->source)->replace('_', ' ')->title() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Submitted {{ $request->created_at->format('M j, Y g:i A') }}
                            @if ($request->nominator)
                                · Nominated by {{ $request->nominator->name }}
                            @endif
                        </p>
                        @if ($request->request_notes)
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $request->request_notes }}</p>
                        @endif
                        <div class="mt-3 text-xs text-slate-500">
                            Required modules: {{ $request->eligibility['completed_required'] }}
                            / {{ $request->eligibility['total_required'] }} confirmed
                        </div>
                        @unless ($request->eligibility['eligible'])
                            <ul class="mt-2 list-inside list-disc text-xs text-amber-800">
                                @foreach ($request->eligibility['missing'] as $requirement)
                                    <li>{{ $requirement }}</li>
                                @endforeach
                            </ul>
                        @endunless
                        <label class="mt-4 block text-sm font-medium text-slate-700" for="review-notes-{{ $request->id }}">Reviewer notes</label>
                        <textarea
                            id="review-notes-{{ $request->id }}"
                            wire:model="reviewNotes.{{ $request->id }}"
                            rows="2"
                            maxlength="2000"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            placeholder="Required when rejecting; optional when approving."
                        ></textarea>
                        @error("reviewNotes.{$request->id}") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        @error('application') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button wire:click="approve({{ $request->id }})" wire:loading.attr="disabled" @disabled(! $request->eligibility['eligible']) class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">
                            Approve
                        </button>
                        <button wire:click="reject({{ $request->id }})" wire:loading.attr="disabled" class="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50">
                            Reject
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-600">
                No CFM certification requests are awaiting review.
            </div>
        @endforelse
        @if ($pendingRequests->hasPages())
            <div>{{ $pendingRequests->links() }}</div>
        @endif
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-semibold text-gw-primary">Recently reviewed</h2>
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Decision</th>
                        <th class="px-4 py-3">Reviewed by</th>
                        <th class="px-4 py-3">Reviewed</th>
                        <th class="px-4 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentRequests as $request)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gw-primary">{{ $request->user?->name ?? 'Deleted member' }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $request->status === 'approved',
                                    'bg-red-50 text-red-700' => $request->status === 'rejected',
                                ])>{{ str($request->status)->title() }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $request->reviewer?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $request->reviewed_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="max-w-md px-4 py-3 text-slate-600">{{ $request->review_notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No reviewed requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

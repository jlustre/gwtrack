<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-gw-primary">CFM Review</h2>

    @if ($feedbackMessage)
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $feedbackMessage }}</div>
    @endif

    @if ($errorMessage)
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errorMessage }}</div>
    @endif

    @can('review', $fna)
        @if (in_array($fna->status, ['submitted_to_cfm', 'under_cfm_review']))
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="font-semibold text-slate-500">Trainee</dt><dd>{{ $fna->owner?->name ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Completeness</dt><dd>{{ $fna->completeness_score }}%</dd></div>
                <div><dt class="font-semibold text-slate-500">DIME</dt><dd>{{ $fna->dime_completed ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Protection Gap</dt><dd>{{ $fna->protection_gap ? '$'.number_format((float) $fna->protection_gap, 0) : '—' }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Annual Income</dt><dd>{{ $fna->incomeDetail?->annual_income ? '$'.number_format((float) $fna->incomeDetail->annual_income, 0) : '—' }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Total Debt</dt><dd>{{ $fna->debtDetail?->total_debt ? '$'.number_format((float) $fna->debtDetail->total_debt, 0) : '—' }}</dd></div>
            </dl>

            @if (count($missing))
                <p class="mt-3 text-xs text-amber-700"><strong>Missing:</strong> {{ implode(', ', $missing) }}</p>
            @endif

            <div class="mt-4">
                <label class="block text-sm font-semibold text-slate-700">Coaching / Review Comments</label>
                <textarea wire:model="comment" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="Required for revision requests (min 10 characters). Optional for approval."></textarea>
                @error('comment')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" wire:click="approve" wire:confirm="Approve this FNA for client presentation?" class="rounded-lg border border-emerald-600 bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve</button>
                <button type="button" wire:click="requestRevision" wire:confirm="Request revisions from the associate?" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">Request Revision</button>
            </div>
        @elseif ($fna->status === 'approved_by_cfm')
            <p class="mt-4 text-sm text-emerald-700">This FNA has been approved. The associate may schedule a client review.</p>
        @elseif ($fna->status === 'revision_requested')
            <p class="mt-4 text-sm text-amber-700">Revision requested — awaiting associate updates.</p>
        @else
            <p class="mt-4 text-sm text-slate-600">This FNA is not in a reviewable state.</p>
        @endif
    @else
        @if ($fna->cfm_feedback_summary)
            <div class="mt-4 rounded-lg border border-gw-accent/40 bg-gw-accent-soft px-4 py-3 text-sm">
                <p class="font-semibold text-gw-primary">CFM Feedback</p>
                <p class="mt-1 text-slate-700">{{ $fna->cfm_feedback_summary }}</p>
            </div>
        @endif
    @endcan

    @if ($fna->reviewComments->isNotEmpty())
        <div class="mt-6 border-t border-slate-100 pt-4">
            <h3 class="text-sm font-semibold text-slate-700">Review Comments</h3>
            <ul class="mt-3 space-y-2">
                @foreach ($fna->reviewComments as $reviewComment)
                    <li class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                        <span class="font-semibold">{{ $reviewComment->user?->name ?? 'User' }}</span>
                        <span class="text-xs text-slate-500"> · {{ str($reviewComment->comment_type)->replace('_', ' ')->title() }} · {{ $reviewComment->created_at?->format('M j, Y') }}</span>
                        <p class="mt-1 text-slate-700">{{ $reviewComment->body }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

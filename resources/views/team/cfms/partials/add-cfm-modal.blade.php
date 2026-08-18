<div
    x-show="showAddCfmModal"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center overflow-auto bg-slate-900/50 p-4 backdrop-blur-sm"
    @keydown.escape.window="showAddCfmModal = false"
>
    <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-xl" @click.stop>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-xl font-semibold text-gw-primary">Add Certified Field Mentor</h3>
                <p class="mt-1 text-xs text-slate-500">Nominate a team member for CFM certification and mentorship duties</p>
            </div>
            <button type="button" @click="showAddCfmModal = false" class="text-2xl leading-none text-slate-400 hover:text-gw-primary">&times;</button>
        </div>

        <form method="POST" action="{{ route('team.cfms.store') }}" class="space-y-3">
            @csrf

            <div>
                <label class="text-xs font-semibold text-slate-600">Select team member</label>
                <select name="user_id" x-model="addCfmForm.userId" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Choose member…</option>
                    <template x-for="candidate in cfmCandidates" :key="'cand-' + candidate.id">
                        <option :value="candidate.id" x-text="candidate.name + ' (' + candidate.rank + ')'"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Target CFM rank tier</label>
                <select name="target_rank" x-model="addCfmForm.targetRank" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="Associate Mentor">Associate Mentor</option>
                    <option value="CFM I">CFM I</option>
                    <option value="CFM II">CFM II</option>
                    <option value="Senior CFM">Senior CFM</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Nomination notes</label>
                <textarea name="notes" x-model="addCfmForm.notes" rows="3" placeholder="Why is this member ready for CFM status?" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">{{ old('notes') }}</textarea>
            </div>

            <div class="space-y-2 text-sm text-slate-700">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="require_approval" value="0">
                    <input type="checkbox" name="require_approval" value="1" x-model="addCfmForm.requireApproval" class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent">
                    Require admin approval before activation
                </label>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="notify_candidate" value="0">
                    <input type="checkbox" name="notify_candidate" value="1" x-model="addCfmForm.notifyCandidate" class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent">
                    Notify candidate by email
                </label>
            </div>

            <p x-show="cfmCandidates.length === 0" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">No eligible team members found in your accessible downline.</p>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="showAddCfmModal = false" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">Cancel</button>
                <button type="submit" class="flex-1 rounded-lg bg-gw-accent py-2.5 font-bold text-gw-primary transition hover:bg-gw-accent-hover disabled:cursor-not-allowed disabled:opacity-40">Submit Nomination</button>
            </div>
        </form>
    </div>
</div>

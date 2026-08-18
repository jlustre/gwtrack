<div
    x-show="showAssignModal"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-sm"
    @keydown.escape.window="showAssignModal = false"
>
    <div class="flex max-h-[90vh] w-full max-w-lg flex-col rounded-xl border border-slate-200 bg-white shadow-xl" @click.stop>
        <div class="shrink-0 border-b border-slate-200 px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h3 class="text-xl font-semibold text-gw-primary">Assign New Associate to CFM</h3>
                    <p class="mt-1 text-xs text-slate-500">Select the associate first — only CFMs licensed in that province or state will appear. External hierarchy assignments may require approval.</p>
                </div>
                <button type="button" @click="showAssignModal = false" class="shrink-0 text-2xl leading-none text-slate-400 hover:text-gw-primary">&times;</button>
            </div>

            <div x-show="selectedCfm" class="mt-4 rounded-lg border border-gw-accent/30 bg-gw-accent-soft p-3 text-xs text-slate-700">
                <p class="font-semibold text-gw-primary" x-text="selectedCfm?.name"></p>
                <p x-text="'Load: ' + selectedCfm?.activeApprentices + '/' + selectedCfm?.maxApprentices + ' · ' + selectedCfm?.recommendationBand"></p>
            </div>
        </div>

        <form method="POST" action="{{ route('team.cfms.assign') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Associate</label>
                    <select
                        name="associate_id"
                        x-model="assignAssociateId"
                        @change="onAssignAssociateChange()"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                        <option value="">Select new associate</option>
                        <template x-for="a in assignableAssociates" :key="'assign-a-' + a.id">
                            <option
                                :value="a.id"
                                x-text="a.locationLabel ? a.name + ' (' + a.rank + ' · ' + a.locationLabel + ')' : a.name + ' (' + a.rank + ')'"
                            ></option>
                        </template>
                    </select>
                </div>

                <p
                    x-show="assignAssociateId && ! selectedAssignAssociate?.jurisdictionKey"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
                >
                    This associate has no province/state on file. Update their profile before assigning a CFM.
                </p>

                <div x-show="assignAssociateId && selectedAssignAssociate?.jurisdictionKey">
                    <label class="text-xs font-semibold text-slate-600">CFM (licensed in associate jurisdiction)</label>
                    <select
                        name="cfm_id"
                        x-model="assignCfmId"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                        <option value="">Select CFM</option>
                        <template x-for="cfm in assignableCfmsForSelectedAssociate" :key="'assign-cfm-' + cfm.id">
                            <option :value="cfm.id" x-text="cfm.name + ' (' + cfm.activeApprentices + '/' + cfm.maxApprentices + ' · ' + (cfm.licensedJurisdictionsLabel || 'licensed') + ')'"></option>
                        </template>
                    </select>
                    <p
                        x-show="assignableCfmsForSelectedAssociate.length === 0"
                        class="mt-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
                    >
                        No accessible CFM is licensed in <span class="font-semibold" x-text="selectedAssignAssociate?.locationLabel"></span>. Update licensed jurisdictions on the CFM's profile (Licenses tab or CFM Management panel), or choose another associate.
                    </p>
                </div>

                <input type="text" name="reason" x-model="assignForm.reason" value="{{ old('reason') }}" placeholder="Assignment reason" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">

                <div class="grid grid-cols-2 gap-3">
                    <input type="date" name="start_date" x-model="assignForm.startDate" value="{{ old('start_date') }}" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <input type="date" name="end_date" x-model="assignForm.endDate" value="{{ old('end_date') }}" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                </div>

                <textarea name="notes" x-model="assignForm.notes" placeholder="Notes" rows="2" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">{{ old('notes') }}</textarea>

                <div class="flex flex-wrap gap-4 text-sm text-slate-700">
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="notify_cfm" value="0">
                        <input type="checkbox" name="notify_cfm" value="1" x-model="assignForm.notifyCfm" class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent" @checked(old('notify_cfm', true))>
                        Notify CFM
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="notify_associate" value="0">
                        <input type="checkbox" name="notify_associate" value="1" x-model="assignForm.notifyAssociate" class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent" @checked(old('notify_associate', true))>
                        Notify associate
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="require_cfm_approval" value="0">
                        <input type="checkbox" name="require_cfm_approval" value="1" x-model="assignForm.requireApproval" class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent" @checked(old('require_cfm_approval', true))>
                        Require CFM confirmation
                    </label>
                </div>

                <p class="text-xs text-slate-500">When CFM confirmation is required, the assignment stays pending until the CFM accepts. If you uncheck it below, the associate becomes an active trainee immediately.</p>

                <div x-show="assignCfmId" x-text="getWorkloadWarning()" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"></div>

                <div
                    x-show="assignCfmId && assignAssociateId && getLicenseMatchWarning()"
                    x-text="getLicenseMatchWarning()"
                    class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"
                ></div>
            </div>

            <div class="flex shrink-0 gap-3 border-t border-slate-200 px-6 py-4">
                <button type="button" @click="showAssignModal = false" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">Cancel</button>
                <button
                    type="submit"
                    class="flex-1 rounded-lg bg-gw-accent py-2.5 font-bold text-gw-primary transition hover:bg-gw-accent-hover disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="! canSubmitAssignment"
                >
                    Submit Assignment
                </button>
            </div>
        </form>
    </div>
</div>

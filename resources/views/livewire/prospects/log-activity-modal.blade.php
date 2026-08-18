<div>
    @if ($show && $prospectId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>
            <div class="relative z-10 w-full max-w-2xl rounded-lg border border-gw-accent/40 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gw-primary">Log Activity</h3>
                    <button type="button" wire:click="close" class="text-slate-500 hover:text-slate-700">&times;</button>
                </div>

                <livewire:prospects.prospect-activity-log-form
                    :prospect-id="$prospectId"
                    :initial-activity-type="$initialActivityType"
                    :embedded="true"
                    :key="'log-activity-form-'.$prospectId.'-'.$formKey"
                />
            </div>
        </div>
    @endif
</div>

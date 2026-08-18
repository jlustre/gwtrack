<div>
    <form wire:submit="save" class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-4 shadow-sm sm:p-5">
        @include('livewire.prospects.partials.form-fields', ['includeStatus' => true])

        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('team.prospects.records.show', $prospect) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Save Changes</button>
        </div>
    </form>
</div>

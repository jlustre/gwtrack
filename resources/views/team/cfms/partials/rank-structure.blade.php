<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-gw-primary">CFM Rank Structure</h3>
    <p class="mt-1 text-xs text-slate-500">Mentor tiers and typical apprentice capacity</p>
    <div class="mt-4 flex flex-wrap gap-3">
        <template x-for="tier in rankTiers" :key="tier.name">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-center">
                <div class="text-sm font-semibold text-gw-primary" x-text="tier.name"></div>
                <div class="text-xs text-gw-accent-dark" x-text="tier.capacity + ' apprentices'"></div>
            </div>
        </template>
    </div>
</div>

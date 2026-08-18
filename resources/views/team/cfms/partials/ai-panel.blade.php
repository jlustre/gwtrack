<div class="rounded-xl border border-gw-accent/30 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center gap-2">
        <h3 class="font-semibold text-gw-primary">Smart Recommendations</h3>
        <span class="rounded-full bg-gw-accent-soft px-2 py-0.5 text-xs font-semibold text-gw-accent-dark">Smart Match</span>
    </div>
    <p x-show="selectedRecommendationAssociate" class="mt-2 text-xs text-slate-600">
        Matches for <span class="font-medium text-gw-primary" x-text="selectedRecommendationAssociate?.name"></span>
        <span x-show="selectedRecommendationAssociate?.queueLabel" class="text-gw-accent-dark" x-text="' · ' + selectedRecommendationAssociate.queueLabel"></span>
        <button type="button" @click="openFapQueue()" class="ml-2 font-semibold text-gw-accent-dark underline hover:text-gw-accent">Change associate</button>
    </p>
    <p x-show="!selectedRecommendationAssociate && fapQueue.length === 0" class="mt-2 text-xs text-slate-500">
        No associates are waiting for CFM assignment.
    </p>

    <div x-show="aiSuggestions.filter(s => s.cfmName).length" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <template x-for="(item, idx) in aiSuggestions.filter(s => s.cfmName)" :key="'ai-' + idx">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 border-l-4" :class="aiBorderClass(item)">
                <div class="text-sm font-bold" :class="aiTitleClass(item)" x-text="item.statusLabel || item.label"></div>
                <div class="mt-1 text-sm text-gw-primary" x-text="item.cfmName"></div>
                <div class="mt-1 text-xs text-slate-500">
                    <span x-show="item.fitScore" x-text="item.fitScore + '/100 fit · '"></span>
                    <span x-text="item.detail"></span>
                </div>
                <button type="button" @click="selectAiSuggestion(item)" class="mt-2 text-xs font-semibold text-gw-accent-dark hover:text-gw-accent">Select</button>
            </div>
        </template>
    </div>

    <div x-show="!aiSuggestions.filter(s => s.cfmName).length && aiSuggestions.length" class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-center">
        <p class="text-sm text-slate-600" x-text="aiSuggestions[0]?.detail || 'Select an associate from the FAP queue to see personalized CFM matches.'"></p>
        <button type="button" x-show="fapQueue.length" @click="openFapQueue()" class="mt-3 text-xs font-semibold text-gw-accent-dark underline hover:text-gw-accent">Open FAP Queue</button>
    </div>

    <ul class="mt-4 space-y-1">
        <template x-for="(item, idx) in aiSuggestions.filter(s => !s.cfmName)" :key="'ai-hint-' + idx">
            <li class="text-xs text-slate-500" x-text="'· ' + (item.detail || item.label)"></li>
        </template>
    </ul>
</div>

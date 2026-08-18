<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:keydown.escape="close">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-300 bg-white shadow-xl">
                <div class="border-b border-slate-200 bg-gw-primary px-6 py-4 text-white">
                    <h3 class="text-lg font-semibold">Submit FNA to CFM</h3>
                    <p class="mt-1 text-sm text-slate-300">{{ $referenceCode }} · {{ $clientName }}</p>
                </div>

                <div class="space-y-4 p-6 text-sm">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p><strong>Completeness:</strong> {{ $completenessScore }}% (minimum {{ config('fna.completeness_threshold') }}%)</p>
                        <p class="mt-1"><strong>CFM Reviewer:</strong> {{ $cfmName ?? 'Not assigned — contact your mentor' }}</p>
                    </div>

                    @if (count($missingSections))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                            <strong>Missing sections:</strong> {{ implode(', ', $missingSections) }}
                        </div>
                    @endif

                    @if ($aiEnabled)
                        @include('livewire.fna.partials.fna-ai-completeness-hints', [
                            'suggestions' => $completenessSuggestions,
                            'complianceNotice' => $complianceNotice,
                        ])
                    @endif

                    @if ($errorMessage)
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">{{ $errorMessage }}</div>
                    @endif

                    <p class="text-slate-600">Your CFM will review this FNA and either approve it for client presentation or request revisions with coaching notes.</p>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="button" wire:click="submit" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Submit to CFM</button>
                </div>
            </div>
        </div>
    @endif
</div>

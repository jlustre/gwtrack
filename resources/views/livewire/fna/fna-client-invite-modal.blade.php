<div>
    @if ($show && ($prospect || $recipientMember))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-8" wire:keydown.escape.window="close">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl" role="dialog" aria-modal="true">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gw-primary">Send FNA Client Portal Link</h2>
                        @if ($recipientMember)
                            <p class="mt-1 text-sm text-slate-600">To GWTrack member: {{ $recipientMember->name }}</p>
                        @elseif ($prospect)
                            <p class="mt-1 text-sm text-slate-600">To prospect: {{ $prospect->displayName() }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="close" class="text-slate-500 hover:text-slate-800">&times;</button>
                </div>
                <div class="p-6">
                    @if ($recipientMember)
                        <livewire:fna.fna-client-invite-panel :recipientMember="$recipientMember" :key="'modal-invite-member-'.$recipientMember->id" />
                    @else
                        <livewire:fna.fna-client-invite-panel :prospect="$prospect" :key="'modal-invite-'.$prospect->id" />
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

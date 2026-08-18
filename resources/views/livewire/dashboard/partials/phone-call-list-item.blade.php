@php
    $mode = $mode ?? 'modal';
    $overdue = $overdue ?? false;
@endphp

<div wire:key="{{ $mode }}-phone-call-{{ $call->id }}" @class([
    'rounded-lg border px-4 py-3',
    'border-amber-300 bg-amber-50' => $overdue,
    'border-slate-200 bg-slate-50' => ! $overdue,
    'px-3' => $mode === 'panel',
])>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gw-primary">{{ $call->contact_name }}</p>
            <p class="mt-0.5 text-xs text-slate-500">
                {{ $call->personTypeLabel() }} · {{ $call->scheduled_at->format('M j, g:i A') }}
                @if ($overdue) · <span class="font-semibold text-amber-800">Overdue</span> @endif
                @if ($call->contact_phone) · {{ $call->contact_phone }} @endif
            </p>
            @if ($call->resultLabel())
                <p class="mt-1 text-xs text-slate-600">Last result: {{ $call->resultLabel() }}</p>
            @endif
            @if ($call->purpose)
                <p class="mt-1 text-xs text-slate-600">{{ $call->purpose }}</p>
            @endif
        </div>
        <div class="flex shrink-0 flex-wrap gap-1.5">
            @if ($call->contact_phone)
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $call->contact_phone) }}" @class([
                    'rounded-md px-2.5 py-1 text-[11px] font-semibold',
                    'border border-gw-accent bg-gw-accent-muted text-gw-primary' => $mode === 'panel',
                    'bg-gw-primary text-white hover:bg-gw-primary-hover' => $mode === 'modal',
                ])>Call now</a>
            @endif

            @if ($mode === 'modal')
                <button type="button" wire:click="viewCall({{ $call->id }})" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">View</button>
                <button type="button" wire:click="startReschedule({{ $call->id }})" class="rounded-md border border-[#1D4ED8]/40 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-[#1D4ED8] hover:bg-blue-100">Reschedule</button>
                <button type="button" wire:click="editCall({{ $call->id }})" class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-2.5 py-1 text-[11px] font-semibold text-gw-primary hover:bg-gw-accent-muted">Edit</button>
                <button type="button" wire:click="deleteCall({{ $call->id }})" wire:confirm="Delete this scheduled phone call?" class="rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 hover:bg-red-100">Delete</button>
            @else
                @if ($rescheduleCallId === $call->id)
                    <button type="button" wire:click="cancelReschedule" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700">Cancel</button>
                @elseif ($activeCallId === $call->id)
                    <button type="button" wire:click="cancelResultForm" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700">Cancel</button>
                @else
                    <button type="button" wire:click="openResultForm({{ $call->id }})" class="rounded-md bg-gw-primary px-2.5 py-1 text-[11px] font-semibold text-white">Log result</button>
                    <button type="button" wire:click="startReschedule({{ $call->id }})" class="rounded-md border border-[#1D4ED8]/40 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-[#1D4ED8] hover:bg-blue-100">Reschedule</button>
                    <button type="button" wire:click="deleteCall({{ $call->id }})" wire:confirm="Delete this scheduled phone call?" class="rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 hover:bg-red-100">Delete</button>
                    <button type="button" onclick="Livewire.dispatch('viewCallFromPanel', { callId: {{ $call->id }} })" class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700">Open</button>
                @endif
            @endif
        </div>
    </div>

    @if ($mode === 'panel' && $activeCallId === $call->id)
        <form wire:submit="saveResult" class="mt-3 space-y-3 border-t border-slate-200 pt-3">
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Result</label>
                <select wire:model.live="result" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Select result</option>
                    @foreach ($results as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('result') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($result === 'callback')
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Callback date and time</label>
                    <input type="datetime-local" wire:model="callbackAt" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @error('callbackAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Notes</label>
                <textarea wire:model="resultNotes" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="What happened on the call?"></textarea>
            </div>

            @if ($call->prospect_id && in_array($call->person_type, ['prospect', 'client'], true))
                <p class="text-xs text-slate-500">Logged to CRM. Only <strong>Completed</strong> removes this call from your list.</p>
            @else
                <p class="text-xs text-slate-500">Only <strong>Completed</strong> removes this call from your list.</p>
            @endif

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveResult" class="rounded-md bg-gw-accent px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover disabled:opacity-60">
                    Save result
                </button>
            </div>
        </form>
    @endif

    @if ($mode === 'panel' && ($rescheduleCallId ?? null) === $call->id)
        <form wire:submit="saveReschedule" class="mt-3 space-y-3 border-t border-slate-200 pt-3">
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">New date and time</label>
                <input type="datetime-local" wire:model="rescheduleAt" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                @error('rescheduleAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Notes</label>
                <textarea wire:model="rescheduleNotes" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Optional notes for this call"></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancelReschedule" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveReschedule" class="rounded-md bg-[#1D4ED8] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#1E40AF] disabled:opacity-60">
                    Save reschedule
                </button>
            </div>
        </form>
    @endif
</div>

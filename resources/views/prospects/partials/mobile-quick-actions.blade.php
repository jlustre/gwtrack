@php
    $prospectId = $prospectId ?? null;
    $phone = $phone ?? null;
    $email = $email ?? null;
    $showDesktop = $showDesktop ?? false;
    $inlineAdd = $inlineAdd ?? false;
@endphp

@if ($showDesktop && ($phone || $email))
    <div class="mt-4 hidden flex-wrap gap-2 border-t border-slate-200 pt-4 md:flex">
        @if ($phone)
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="inline-flex items-center gap-1 rounded-lg border border-gw-accent bg-gw-accent-muted px-3 py-1.5 text-xs font-semibold text-gw-primary">
                Call
            </a>
            <a href="sms:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">
                Text
            </a>
        @endif
        @if ($email)
            <a href="mailto:{{ $email }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">
                Email
            </a>
        @endif
        @if ($prospectId)
            <button type="button" onclick="Livewire.dispatch('open-log-activity-modal', { prospectId: '{{ $prospectId }}', activityType: 'phone_call' })" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">
                Log Call
            </button>
            <button type="button" onclick="Livewire.dispatch('open-log-activity-modal', { prospectId: '{{ $prospectId }}' })" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">
                Note
            </button>
        @endif
    </div>
@endif

<nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-gw-accent/40 bg-gw-primary px-2 py-2 md:hidden">
    <div class="mx-auto flex max-w-lg items-stretch justify-between gap-1">
        @if ($inlineAdd)
            <button type="button" x-on:click="showCreateProspect = true; $nextTick(() => $refs.prospectCreatePanel?.scrollIntoView({ behavior: 'smooth', block: 'start' }))" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-gw-accent hover:bg-white/10">
                <span class="text-base leading-none">+</span>
                Add
            </button>
        @else
            <a href="{{ route('team.prospects.create') }}" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-gw-accent hover:bg-white/10">
                <span class="text-base leading-none">+</span>
                Add
            </a>
        @endif

        @if ($prospectId)
            @if ($phone)
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Call
                </a>
                <button type="button" onclick="Livewire.dispatch('open-log-activity-modal', { prospectId: '{{ $prospectId }}', activityType: 'text_message' })" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Text
                </button>
            @else
                <button type="button" onclick="Livewire.dispatch('open-log-activity-modal', { prospectId: '{{ $prospectId }}', activityType: 'phone_call' })" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Log Call
                </button>
                <button type="button" onclick="Livewire.dispatch('open-log-activity-modal', { prospectId: '{{ $prospectId }}', activityType: 'text_message' })" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                    Text
                </button>
            @endif

            <a href="{{ route('team.prospects.appointments') }}" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Schedule
            </a>

            <button type="button" onclick="Livewire.dispatch('open-log-activity-modal', { prospectId: '{{ $prospectId }}' })" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Note
            </button>
        @else
            <a href="{{ route('team.prospects.follow-ups') }}" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                Follow-Up
            </a>
            <a href="{{ route('team.prospects.appointments') }}" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                Schedule
            </a>
            <a href="{{ route('team.prospects.ai-coach') }}" class="flex flex-1 flex-col items-center rounded-lg px-1 py-2 text-[10px] font-semibold text-white hover:bg-white/10">
                AI Coach
            </a>
        @endif
    </div>
</nav>

<div class="h-16 md:hidden" aria-hidden="true"></div>

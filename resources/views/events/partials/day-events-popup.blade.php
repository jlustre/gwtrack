<div
    x-show="dayPopupOpen"
    x-cloak
    x-transition.opacity
    x-on:click.self="dayPopupOpen = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="day-events-popup-title"
>
    <div class="w-full max-w-lg overflow-hidden rounded-xl border border-gw-accent/50 bg-white shadow-2xl">
        <div class="border-b border-slate-100 bg-gradient-to-r from-gw-primary via-[#132B4B] to-gw-primary px-5 py-4 text-white">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Day Schedule</p>
                    <h3 id="day-events-popup-title" class="mt-1 text-lg font-semibold" x-text="dayPopupLabel"></h3>
                    <p class="mt-1 text-xs text-slate-300">
                        <span x-text="dayPopupItems.length"></span>
                        <span x-text="dayPopupItems.length === 1 ? 'item' : 'items'"></span> scheduled
                    </p>
                </div>
                <button type="button" x-on:click="dayPopupOpen = false" class="gw-icon-btn-overlay" aria-label="Close day schedule popup">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="max-h-[22rem] space-y-2 overflow-y-auto px-5 py-4">
            <template x-if="dayPopupItems.length === 0">
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                    <p class="text-sm font-semibold text-gw-primary">No events scheduled</p>
                    <p class="mt-1 text-xs text-slate-500">This day is free. Add an event to get started.</p>
                </div>
            </template>

            <template x-for="(item, index) in dayPopupItems" :key="index">
                <button
                    type="button"
                    x-on:click="
                        if (item.type === 'event' && item.id && typeof $wire !== 'undefined' && typeof $wire.openEventDetail === 'function') {
                            dayPopupOpen = false;
                            $wire.openEventDetail(item.id);
                            return;
                        }
                        if (item.url) {
                            window.location = item.url;
                        }
                    "
                    class="flex w-full items-start gap-3 rounded-lg border border-slate-200 bg-white px-3 py-3 text-left transition hover:border-gw-accent/50 hover:bg-gw-accent-soft/60"
                    x-bind:class="(item.type === 'event' && item.id) || item.url ? 'cursor-pointer' : 'cursor-default'"
                >
                    <span
                        class="mt-1 h-8 w-1 shrink-0 rounded-full"
                        x-bind:style="'background-color: ' + (item.color || '#84BB41')"
                    ></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-semibold text-gw-primary" x-text="item.title"></p>
                            <span
                                class="rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase"
                                x-bind:class="item.type === 'block' ? 'bg-slate-100 text-slate-600' : 'bg-gw-accent-soft text-gw-accent-dark'"
                                x-text="item.type === 'block' ? 'Blocked' : 'Event'"
                            ></span>
                        </div>
                        <p class="mt-1 text-xs font-medium text-slate-500" x-text="item.time"></p>
                        <p class="mt-0.5 text-xs text-slate-400" x-show="item.meta" x-text="item.meta"></p>
                    </div>
                </button>
            </template>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
            <button
                type="button"
                x-on:click="
                    if (! dayPopupDate) { return; }
                    dayPopupOpen = false;
                    if (typeof $wire !== 'undefined' && typeof $wire.goToDate === 'function') {
                        $wire.goToDate(dayPopupDate, 'day');
                        return;
                    }
                    window.location = @js(route('calendar.day', array_merge(request()->query(), ['date' => '__DATE__']))).replace('__DATE__', dayPopupDate);
                "
                class="text-sm font-semibold text-gw-accent-dark hover:underline"
            >
                Open day view
            </button>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    x-on:click="dayPopupOpen = false"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white"
                >
                    Close
                </button>
                @if (auth()->user()?->can('create calendar events'))
                    <button
                        type="button"
                        x-on:click="openCreateForDay()"
                        class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover"
                    >
                        Add event
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

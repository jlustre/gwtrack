@php
    $welcome = $home['welcome'] ?? [];
    $quote = $home['daily_quote'] ?? null;
@endphp

<section class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-gw-primary via-gw-primary to-[#132d52] text-white shadow-sm">
    <div class="px-6 py-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-start gap-4">
                <x-user-avatar :user="$user" size="lg" class="border-white/20 bg-white/10" />

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Welcome Message</p>
                    <h1 class="mt-1 text-2xl font-semibold lg:text-3xl">{{ $welcome['headline'] ?? 'Welcome back.' }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">{{ $welcome['message'] ?? '' }}</p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (filled($welcome['rank'] ?? null))
                            <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold">{{ $welcome['rank'] }}</span>
                        @endif
                        @if (filled($welcome['team'] ?? null))
                            <span class="rounded-full border border-gw-accent/40 bg-gw-accent/10 px-3 py-1 text-xs font-semibold text-gw-accent-muted">{{ $welcome['team'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @if (filled($quote))
                <div
                    class="max-w-md rounded-lg border border-white/10 bg-white/5 p-4 lg:mt-0"
                    x-data="{
                        quotes: {{ \Illuminate\Support\Js::from($quote['quotes'] ?? [['quote' => $quote['quote'], 'author' => $quote['author'] ?? null]]) }},
                        index: {{ (int) ($quote['index'] ?? 0) }},
                        get current() {
                            return this.quotes[this.index] ?? this.quotes[0] ?? null;
                        },
                        prev() {
                            if (this.quotes.length < 2) return;
                            this.index = (this.index - 1 + this.quotes.length) % this.quotes.length;
                        },
                        next() {
                            if (this.quotes.length < 2) return;
                            this.index = (this.index + 1) % this.quotes.length;
                        },
                    }"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Daily Quote</p>
                        <template x-if="quotes.length > 1">
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    x-on:click="prev()"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white/20 bg-white/5 text-slate-200 transition hover:border-gw-accent/60 hover:bg-gw-accent/15 hover:text-white"
                                    aria-label="Previous quote"
                                    title="Previous quote"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <span class="min-w-[3rem] text-center text-[0.65rem] font-semibold tabular-nums text-slate-400" x-text="(index + 1) + ' / ' + quotes.length"></span>
                                <button
                                    type="button"
                                    x-on:click="next()"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white/20 bg-white/5 text-slate-200 transition hover:border-gw-accent/60 hover:bg-gw-accent/15 hover:text-white"
                                    aria-label="Next quote"
                                    title="Next quote"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <blockquote class="mt-2">
                        <p class="text-sm italic leading-6 text-slate-100">
                            &ldquo;<span x-text="current?.quote ?? ''"></span>&rdquo;
                        </p>
                        <footer
                            class="mt-3 text-xs font-semibold text-slate-300"
                            x-show="current?.author"
                            x-cloak
                        >
                            — <span x-text="current?.author ?? ''"></span>
                        </footer>
                    </blockquote>
                </div>
            @endif
        </div>
    </div>
</section>

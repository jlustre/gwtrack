<div class="grid gap-6 xl:grid-cols-5">
    <div class="space-y-6 xl:col-span-2">
        <form wire:submit="generate" class="space-y-4 rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Compile newsletter</h2>

            <div>
                <label class="block text-sm font-medium text-slate-700">Period</label>
                <select wire:model="period_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($periods as $key => $meta)
                        <option value="{{ $key }}">{{ $meta['label'] ?? ucfirst($key) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Custom start (optional)</label>
                    <input type="date" wire:model="custom_start" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Custom end (optional)</label>
                    <input type="date" wire:model="custom_end" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Intro paragraph (optional override)</label>
                <textarea wire:model="intro_override" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Leave blank to auto-generate an intro from compiled content."></textarea>
            </div>

            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#132a4d]">
                Generate newsletter
            </button>
        </form>

        <div class="space-y-4 rounded-2xl border border-gw-accent/30 bg-gw-accent-soft p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">AI draft assistant</h2>
            <p class="text-xs text-slate-600">Rule-based templates today; enable LLM in config when ready.</p>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Draft type</label>
                    <select wire:model="ai_draft_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($aiDraftTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Topic</label>
                    <input type="text" wire:model="ai_topic" placeholder="e.g. March licensing push" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
            </div>

            <button type="button" wire:click="generateAiDraft" class="rounded-lg border border-gw-primary/15 bg-white px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-white/80">
                Generate draft
            </button>

            @if ($aiDraft)
                <div class="rounded-lg border border-gw-primary/10 bg-white p-4 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Source: {{ $aiDraft['source'] }}</p>
                    <p class="mt-2 font-semibold text-gw-primary">{{ $aiDraft['title'] }}</p>
                    @if ($aiDraft['summary'])
                        <p class="mt-2 text-slate-600">{{ $aiDraft['summary'] }}</p>
                    @endif
                    <p class="mt-2 whitespace-pre-line text-slate-700">{{ $aiDraft['body'] }}</p>
                    <button type="button" wire:click="applyAiDraftToIntro" class="mt-3 text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">
                        Use as newsletter intro →
                    </button>
                </div>
            @endif
        </div>

        @if ($preview)
            <form wire:submit="sendNewsletter" class="space-y-4 rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Send email</h2>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Audience</label>
                    <select wire:model="audience_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($audienceTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                    Queue email send
                </button>
            </form>
        @endif
    </div>

    <div class="space-y-6 xl:col-span-3">
        @if ($preview)
            <div class="overflow-hidden rounded-2xl border border-gw-primary/10 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Preview</p>
                        <h3 class="font-semibold text-gw-primary">{{ $preview->title }}</h3>
                    </div>
                    <span class="rounded-full bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-accent-dark">{{ ucfirst($preview->status) }}</span>
                </div>
                <div class="max-h-[640px] overflow-auto p-4">
                    {!! $preview->html_body !!}
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                <p class="text-sm font-semibold text-gw-primary">Generate a newsletter to preview email-ready HTML</p>
                <p class="mt-2 text-sm text-slate-500">Content is compiled from announcements, recognition, events, and campaigns in the selected period.</p>
            </div>
        @endif

        <div class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Recent newsletters</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($recentNewsletters as $item)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-3">
                        <div>
                            <p class="font-semibold text-gw-primary">{{ $item->title }}</p>
                            <p class="text-xs text-slate-500">{{ $item->created_at?->format('M j, Y g:i A') }} · {{ ucfirst($item->period_type) }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="font-semibold text-gw-accent-dark">{{ ucfirst($item->status) }}</span>
                            @if ($item->sent_count)
                                <span class="text-slate-500">· {{ number_format($item->sent_count) }} sent</span>
                            @endif
                            <button type="button" wire:click="$set('previewNewsletterId', {{ $item->id }})" class="font-semibold text-gw-primary underline">
                                Preview
                            </button>
                        </div>
                    </li>
                @empty
                    <li class="text-slate-500">No newsletters generated yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

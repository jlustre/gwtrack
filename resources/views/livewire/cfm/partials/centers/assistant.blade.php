@php($center = $sectionCenter)
@php($context = $center['context'] ?? [])

<div class="space-y-4">
    <div class="rounded-xl border border-gw-primary/20 bg-gradient-to-br from-gw-primary to-[#102847] p-5 text-white shadow-lg sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Intelligent coaching</p>
                <h2 class="mt-1 text-xl font-semibold">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-300">{{ $center['description'] }}</p>
            </div>
            <form wire:submit="generateCoachingBrief" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div>
                    <label class="text-xs font-semibold text-slate-300">Brief focus</label>
                    <select wire:model="aiFocusArea" class="mt-1 rounded-md border-white/20 bg-white/10 text-sm text-white focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($center['focus_areas'] as $area)
                            <option value="{{ $area }}" class="text-gw-primary">{{ ucfirst(str_replace('_', ' ', $area)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">Generate brief</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Risk', 'value' => ucfirst($center['stats']['risk_level'] ?? 'low'), 'theme' => 'amber'],
            ['label' => 'Readiness', 'value' => ($center['stats']['readiness'] ?? 0).'%', 'theme' => 'emerald'],
            ['label' => 'Open tasks', 'value' => $center['stats']['open_tasks'] ?? 0, 'theme' => 'navy'],
            ['label' => 'Briefs saved', 'value' => $center['stats']['sessions'] ?? 0, 'theme' => 'gold'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Ask the assistant</h3>
            <form wire:submit="askAssistant" class="mt-4 space-y-3">
                <textarea wire:model="aiQuestion" rows="3" class="w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Is this trainee ready for promotion?"></textarea>
                @error('aiQuestion') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Get answer</button>
            </form>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($center['prompts'] as $prompt)
                    <button type="button" wire:click="useAssistantPrompt(@js($prompt))" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 hover:border-gw-accent">{{ $prompt }}</button>
                @endforeach
            </div>

            @if ($aiAnswer !== '')
                <div class="mt-4 rounded-lg border border-gw-accent/30 bg-gw-accent-soft p-4 text-sm leading-relaxed text-gw-primary">
                    {{ $aiAnswer }}
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Live trainee context</h3>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Onboarding</dt><dd class="font-semibold text-gw-primary">{{ $context['progress']['onboarding'] ?? 0 }}%</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">FAP</dt><dd class="font-semibold text-gw-primary">{{ $context['progress']['fap'] ?? 0 }}%</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Licensing</dt><dd class="font-semibold text-gw-primary">{{ $context['progress']['licensing'] ?? 0 }}%</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Training</dt><dd class="font-semibold text-gw-primary">{{ $context['progress']['training'] ?? 0 }}%</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Risk score</dt><dd class="font-semibold text-gw-primary">{{ $context['risk']['score'] ?? 0 }}/100</dd></div>
            </dl>
        </div>
    </div>

    @if ($center['brief'])
        <div class="rounded-xl border border-gw-accent/40 bg-gw-accent-soft p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">Latest coaching brief</h3>
                <span class="text-xs text-slate-500">{{ $center['brief']['session_at'] }}</span>
            </div>
            <p class="mt-3 text-sm leading-relaxed text-gw-primary">{{ $center['brief']['summary'] }}</p>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-emerald-800">Strengths</p>
                    <ul class="mt-2 list-inside list-disc text-sm text-slate-700">
                        @foreach ($center['brief']['strengths'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-amber-800">Gaps</p>
                    <ul class="mt-2 list-inside list-disc text-sm text-slate-700">
                        @foreach ($center['brief']['weaknesses'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-gw-accent-dark">Recommendations</p>
                    <ul class="mt-2 list-inside list-disc text-sm text-slate-700">
                        @foreach ($center['brief']['recommendations'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">SMS to trainee</h3>
                @if ($center['sms_enabled'])
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase text-emerald-800">SMS enabled</span>
                @else
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase text-slate-600">Log driver</span>
                @endif
            </div>
            <form wire:submit="sendSmsToTrainee" class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Template</label>
                    <select wire:model="smsTemplate" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($center['sms_templates'] as $template)
                            <option value="{{ $template['key'] }}">{{ $template['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Custom message (optional)</label>
                    <textarea wire:model="smsCustomBody" rows="3" maxlength="320" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Overrides template body when provided"></textarea>
                </div>
                <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Send SMS</button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Saved coaching briefs</h3>
            </div>
            @if (count($center['sessions']) === 0)
                <p class="p-6 text-sm text-slate-500">Generate your first AI coaching brief above.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['sessions'] as $session)
                        <li wire:key="cfm-ai-session-{{ $session['id'] }}" class="px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gw-primary">{{ ucfirst(str_replace('_', ' ', $session['focus_area'])) }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $session['summary'] }}</p>
                                </div>
                                <span class="shrink-0 text-xs text-slate-500">{{ $session['session_at'] }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

@php($center = $sectionCenter)
@php($preview = $center['preview'] ?? [])

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Performance documentation</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
            </div>
            <a href="{{ $center['member_profile_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">View profile</a>
            <a href="{{ $center['roster_export_url'] }}" class="inline-flex rounded-lg border border-gw-accent/50 bg-gw-accent-soft px-3 py-2 text-sm font-semibold text-gw-accent-dark hover:bg-gw-accent/20">Export roster CSV</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
        @foreach ([
            ['label' => 'Reports saved', 'value' => $center['stats']['reports_generated'] ?? 0, 'theme' => 'navy'],
            ['label' => 'Onboarding', 'value' => ($center['stats']['onboarding'] ?? 0).'%', 'theme' => 'gold'],
            ['label' => 'FAP', 'value' => ($center['stats']['fap'] ?? 0).'%', 'theme' => 'emerald'],
            ['label' => 'Licensing', 'value' => ($center['stats']['licensing'] ?? 0).'%', 'theme' => 'cyan'],
            ['label' => 'Training', 'value' => ($center['stats']['training'] ?? 0).'%', 'theme' => 'violet'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm xl:col-span-1">
            <h3 class="text-sm font-semibold text-gw-primary">Generate report</h3>
            <form wire:submit="generateReport" class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Report type</label>
                    <select wire:model="reportType" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($center['report_types'] as $type)
                            <option value="{{ $type }}">{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Audience</label>
                    <select wire:model="reportAudience" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($center['audiences'] as $audience)
                            <option value="{{ $audience }}">{{ ucfirst($audience) }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="reportNotifyTrainee" class="mt-0.5 rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                    <span>Notify trainee when generated</span>
                </label>
                @if ($reportNotifyTrainee)
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Notification channel</label>
                        <select wire:model="reportNotifyChannel" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="in_app">In-app</option>
                            <option value="sms">SMS</option>
                        </select>
                    </div>
                @endif
                <button type="submit" class="w-full rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Generate &amp; save</button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Live preview — {{ $preview['trainee']['name'] ?? 'Trainee' }}</h3>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach (['onboarding' => 'Onboarding', 'fap' => 'FAP', 'licensing' => 'Licensing', 'training' => 'Training'] as $key => $label)
                    <div class="rounded-lg bg-slate-50 p-3 text-center">
                        <p class="text-[0.65rem] font-semibold uppercase text-slate-500">{{ $label }}</p>
                        <p class="mt-1 text-xl font-bold text-gw-primary">{{ $preview['progress'][$key] ?? 0 }}%</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Open tasks</p>
                    <p class="mt-1 text-lg font-bold text-gw-primary">{{ $preview['open_tasks'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Risk level</p>
                    <p class="mt-1 text-lg font-bold capitalize text-gw-primary">{{ $preview['risk']['level'] ?? 'low' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Promotion ready</p>
                    <p class="mt-1 text-lg font-bold text-gw-primary">{{ ($preview['promotion_ready'] ?? false) ? 'Yes' : 'Not yet' }}</p>
                </div>
            </div>
            @if (! empty($preview['goals']))
                <h4 class="mt-5 text-xs font-semibold uppercase tracking-wide text-slate-500">Active goals</h4>
                <ul class="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-200">
                    @foreach ($preview['goals'] as $goal)
                        <li class="flex items-center justify-between px-3 py-2 text-sm">
                            <span class="font-medium text-gw-primary">{{ $goal['name'] }}</span>
                            <span class="text-slate-500">{{ $goal['progress'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Report history</h3>
            </div>
            @if (count($center['history']) === 0)
                <p class="p-6 text-sm text-slate-500">No reports generated yet for this trainee.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['history'] as $report)
                        <li wire:key="cfm-report-{{ $report['id'] }}" class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-gw-primary">{{ $report['type_label'] }}</p>
                                <p class="text-xs text-slate-500">{{ $report['generated_at'] }} · {{ ucfirst($report['audience']) }} · by {{ $report['generated_by'] }}</p>
                            </div>
                            <a href="{{ $report['download_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-gw-primary hover:border-gw-accent">Download PDF</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Trainee notifications sent</h3>
            </div>
            @if (empty($center['notifications']))
                <p class="p-6 text-sm text-slate-500">No in-app notifications sent to this trainee yet.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['notifications'] as $notification)
                        <li wire:key="cfm-notification-{{ $notification['id'] }}" class="px-5 py-4">
                            <p class="font-semibold text-gw-primary">{{ $notification['subject'] }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $notification['body'] }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $notification['sent_at'] }} · {{ $notification['channel'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

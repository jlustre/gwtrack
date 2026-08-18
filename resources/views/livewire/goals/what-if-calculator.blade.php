<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
        <h2 class="text-lg font-semibold text-gw-primary">What-If Calculator</h2>
        <p class="mt-1 text-sm text-slate-600">Simulate a target and see required production, FNAs, appointments, and daily activities.</p>
    </div>
    <div class="grid gap-6 p-6 lg:grid-cols-2">
        <form wire:submit="calculate" class="space-y-4">
            <div>
                <x-input-label value="Goal type" />
                <select wire:model.live="planningType" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @foreach ($planningTypes as $key => $type)
                        <option value="{{ $key }}">{{ $type['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Target value" />
                <x-text-input type="number" step="0.01" wire:model="targetValue" class="mt-1 block w-full" />
            </div>
            @if ($planningType === 'rank')
                <div>
                    <x-input-label value="Target rank" />
                    <select wire:model="targetRank" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($rankOptions as $rank)
                            <option value="{{ $rank }}">{{ $rank }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Run simulation</button>
        </form>

        @if ($results)
            <div class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($results['summary'] as $key => $value)
                        @if ($value > 0)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs uppercase text-slate-500">{{ str_replace('_', ' ', $key) }}</p>
                                <p class="text-lg font-bold text-gw-primary">{{ number_format($value, is_float($value) && $value < 10 ? 1 : 0) }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="max-h-80 overflow-y-auto rounded-lg border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr><th class="px-3 py-2">Stage</th><th class="px-3 py-2">Annual</th><th class="px-3 py-2">Monthly</th><th class="px-3 py-2">Daily</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($results['funnel'] as $stage)
                                <tr wire:key="sim-{{ $stage['key'] }}">
                                    <td class="px-3 py-2 font-medium text-gw-primary">{{ $stage['label'] }}</td>
                                    <td class="px-3 py-2">{{ number_format($stage['annual_target'] ?? 0, 0) }}</td>
                                    <td class="px-3 py-2">{{ number_format($stage['monthly_target'] ?? 0, 0) }}</td>
                                    <td class="px-3 py-2">{{ number_format($stage['daily_target'] ?? 0, 1) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

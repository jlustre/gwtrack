<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
        <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                <h1 class="mt-2 text-2xl font-semibold">Import Prospects</h1>
                <p class="mt-2 text-sm text-slate-200">Upload a CSV, map columns, review duplicates, and import into your private pipeline.</p>
            </div>
            <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 text-sm font-semibold">
        @foreach ([1 => 'Upload', 2 => 'Map Columns', 3 => 'Duplicates', 4 => 'Confirm'] as $number => $label)
            <span class="rounded-full px-3 py-1 {{ $step >= $number ? 'bg-gw-primary text-gw-accent' : 'bg-slate-100 text-slate-500' }}">
                {{ $number }}. {{ $label }}
            </span>
        @endforeach
    </div>

    @if ($step === 1)
        <form wire:submit="uploadCsv" class="rounded-lg border border-slate-400 bg-white p-6 shadow-sm">
            <label class="block text-sm font-semibold text-slate-700">
                CSV File (max 2MB)
                <input type="file" wire:model="csvFile" accept=".csv,text/csv" class="mt-2 block w-full text-sm">
            </label>
            @error('csvFile') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            <div wire:loading wire:target="csvFile" class="mt-2 text-xs text-slate-500">Uploading...</div>
            <button type="submit" class="mt-4 rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">
                Continue to Preview
            </button>
        </form>
    @endif

    @if ($step === 2)
        <div class="space-y-6">
            <div class="rounded-lg border border-slate-400 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Column Mapping</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $totalRows }} rows detected. Previewing first {{ count($previewRows) }}.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ($importFields as $field)
                        <label class="block text-sm">
                            <span class="font-semibold text-slate-700">{{ str($field)->replace('_', ' ')->title() }}</span>
                            <select wire:model="columnMap.{{ $field }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                <option value="">— Skip —</option>
                                @foreach ($headers as $header)
                                    <option value="{{ $header }}">{{ $header }}</option>
                                @endforeach
                            </select>
                            @error('columnMap.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-400 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            @foreach ($headers as $header)
                                <th class="px-4 py-3">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($previewRows as $row)
                            <tr>
                                @foreach ($headers as $header)
                                    <td class="px-4 py-3 text-slate-600">{{ $row[$header] ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="button" wire:click="proceedToDuplicates" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">
                Check Duplicates
            </button>
        </div>
    @endif

    @if ($step === 3)
        <div class="space-y-4">
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                {{ count($duplicates) }} duplicate{{ count($duplicates) === 1 ? '' : 's' }} found (matched by email or phone). Duplicates will be skipped on import.
            </div>

            @if (count($duplicates) > 0)
                <div class="overflow-x-auto rounded-lg border border-slate-400 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Row</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Matches</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($duplicates as $row)
                                <tr>
                                    <td class="px-4 py-3">{{ $row['row_number'] }}</td>
                                    <td class="px-4 py-3">{{ trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')) }}</td>
                                    <td class="px-4 py-3">{{ $row['email'] ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $row['phone'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('team.prospects.records.show', $row['matched_prospect_id']) }}" class="font-semibold text-gw-accent-dark">
                                            {{ $row['matched_prospect_name'] }}
                                        </a>
                                        <span class="text-slate-500">({{ $row['matched_on'] }})</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <button type="button" wire:click="proceedToConfirm" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">
                Continue to Confirm
            </button>
        </div>
    @endif

    @if ($step === 4)
        <div class="rounded-lg border border-slate-400 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Confirm Import</h2>
            <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                <div><dt class="font-semibold text-slate-500">Total rows</dt><dd class="text-gw-primary">{{ $totalRows }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Duplicates (skipped)</dt><dd class="text-gw-primary">{{ count($duplicates) }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Rows to import</dt><dd class="text-gw-primary">{{ max(0, $totalRows - count($duplicates)) }}</dd></div>
            </dl>
            <button type="button" wire:click="confirmImport" class="mt-6 rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">
                Import Prospects
            </button>
        </div>
    @endif
</div>

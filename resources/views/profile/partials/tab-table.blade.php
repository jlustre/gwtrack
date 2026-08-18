@props([
    'columns' => [],
    'rows' => [],
    'empty' => 'No records to display yet.',
])

<div class="overflow-x-auto rounded-lg border border-slate-200">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr>
                @foreach ($columns as $column)
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse ($rows as $row)
                <tr class="hover:bg-gw-accent-soft/40">
                    @foreach ($columns as $column)
                        @php
                            $value = $row[$column['key']] ?? '—';
                            $isStatus = ($column['key'] ?? '') === 'status';
                            $statusKey = $row['status_key'] ?? null;
                        @endphp
                        <td class="px-4 py-3 text-gw-primary">
                            @if ($isStatus && $statusKey)
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700' => $statusKey === 'completed',
                                    'bg-amber-100 text-amber-700' => $statusKey === 'pending_confirmation',
                                    'bg-red-100 text-red-700' => $statusKey === 'rejected',
                                    'bg-slate-100 text-slate-600' => ! in_array($statusKey, ['completed', 'pending_confirmation', 'rejected'], true),
                                ])>{{ $value }}</span>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-slate-500">{{ $empty }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

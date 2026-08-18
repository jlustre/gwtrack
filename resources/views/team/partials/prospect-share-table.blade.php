<div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-indigo-50 p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-gw-primary">{{ $title }}</h2>
    <div class="mt-5 overflow-x-auto rounded-lg border border-slate-300 bg-white/85">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Prospect</th>
                    <th class="px-4 py-3">Collaborator</th>
                    <th class="px-4 py-3">Permission</th>
                    <th class="px-4 py-3">Expires</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-gw-primary">{{ trim($row->first_name.' '.$row->last_name) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->collaborator_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->permission_name ?? 'View Only' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->expires_at ? \Illuminate\Support\Carbon::parse($row->expires_at)->format('M j, Y') : 'No expiration' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ str($row->status)->title() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No shared access records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

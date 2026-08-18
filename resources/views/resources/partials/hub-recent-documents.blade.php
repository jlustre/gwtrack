<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Recently updated</h2>
            <p class="mt-1 text-sm text-slate-600">Latest published document changes.</p>
        </div>
        <a href="{{ route('resources.documents') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">All documents</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">Document</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Format</th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($recentDocuments as $document)
                    @php
                        $categoryKey = $document->category ?: 'general';
                        $categoryMeta = $documentCategoryDefinitions[$categoryKey] ?? $documentCategoryDefinitions['general'];
                    @endphp
                    <tr class="transition hover:bg-gw-accent-soft/40">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-gw-primary">{{ $document->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ $document->description }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $categoryMeta['accent'] }}">
                                {{ $categoryMeta['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $document->resolvedFormat() }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('resources.documents', ['document' => $document->id]) }}" class="text-xs font-semibold text-gw-accent-dark hover:underline">
                                Open
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

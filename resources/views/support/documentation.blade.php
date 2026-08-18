<x-app-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('support.index') }}" class="text-sm font-semibold text-gw-accent-dark underline decoration-gw-accent underline-offset-2 hover:text-gw-primary">← Back to Help & Support</a>
            @if ($hasPdf ?? false)
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('support.documentation.pdf.view', $guide) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        View PDF
                    </a>
                    <a
                        href="{{ route('support.documentation.pdf', $guide) }}"
                        class="inline-flex items-center rounded-md bg-gw-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-[#13345f]"
                    >
                        Download PDF
                    </a>
                </div>
            @endif
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gw-primary px-6 py-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">User Guide</p>
                <h1 class="mt-1 text-2xl font-semibold">{{ $title }}</h1>
                @if ($summary)
                    <p class="mt-2 max-w-3xl text-sm text-slate-300">{{ $summary }}</p>
                @endif
            </div>
            <div class="documentation-prose prose prose-slate max-w-none px-6 py-8 prose-headings:scroll-mt-24 prose-headings:text-gw-primary prose-h2:border-l-4 prose-h2:border-gw-accent prose-h2:bg-slate-50 prose-h2:px-3 prose-h2:py-2 prose-h2:text-xl prose-table:text-sm prose-th:bg-gw-primary prose-th:text-white prose-td:align-top prose-a:font-semibold prose-a:text-gw-accent-dark prose-a:underline prose-a:decoration-gw-accent prose-a:underline-offset-2 hover:prose-a:text-gw-primary hover:prose-a:decoration-gw-accent-dark prose-pre:bg-slate-900 prose-pre:text-slate-100">
                {!! $content !!}
            </div>
        </section>
    </div>

    <style>
        .documentation-prose table {
            width: 100%;
            display: table;
        }

        .documentation-prose thead th {
            background: #2D3E50;
            color: #fff;
        }

        .documentation-prose tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .documentation-prose hr {
            margin: 2rem 0;
            border-color: #e2e8f0;
        }

        .documentation-prose pre {
            white-space: pre-wrap;
            border-radius: 0.5rem;
        }

        .documentation-prose ul.contains-task-list {
            list-style: none;
            padding-left: 0;
        }

        .documentation-prose li.task-list-item {
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
            margin: 0.4rem 0;
            padding: 0.55rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #f8fafc;
        }
    </style>
</x-app-layout>

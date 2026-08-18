<div class="fna-export-preview space-y-6">
    <div class="no-print flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">
            Preview of export for <span class="font-semibold text-gw-primary">{{ $exportData['reference_code'] }}</span>
            @if (! $exportData['can_view_financial'])
                <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Financial details masked</span>
            @endif
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()"
                class="inline-flex items-center justify-center rounded-lg border border-gw-primary bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-[#132d52]">
                Print
            </button>
            <a href="{{ route('team.fna.export.download', $fna) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                Download PDF
            </a>
        </div>
    </div>

    <div class="print-area rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
        <div class="mb-6 border-b border-gw-accent/40 pb-4">
            <h1 class="text-xl font-semibold text-gw-primary sm:text-2xl">Financial Needs Analysis</h1>
            <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                {{ $exportData['reference_code'] }} · {{ $exportData['status'] }} · {{ $exportData['completeness_score'] }}% complete
                · Generated {{ $exportData['generated_at']->format('M j, Y g:i A') }}
            </p>
        </div>

        <div class="export-body space-y-6 text-sm text-slate-800 [&_h2]:mb-3 [&_h2]:border-b [&_h2]:border-gw-accent [&_h2]:pb-1 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:uppercase [&_h2]:text-gw-primary [&_table]:w-full [&_table]:text-sm [&_td]:border-b [&_td]:border-slate-100 [&_td]:py-2 [&_td]:align-top [&_.label]:w-1/3 [&_.label]:font-semibold [&_.label]:text-gw-primary [&_.restricted]:rounded-lg [&_.restricted]:border [&_.restricted]:border-dashed [&_.restricted]:border-slate-300 [&_.restricted]:bg-slate-50 [&_.restricted]:p-3 [&_.restricted]:text-slate-500 [&_.comment]:mb-2 [&_.comment]:rounded-r-lg [&_.comment]:border-l-4 [&_.comment]:border-gw-accent [&_.comment]:bg-slate-50 [&_.comment]:px-3 [&_.comment]:py-2">
            @include('fna.partials.export-content', $exportData)
        </div>

        <div class="mt-8 rounded-lg border border-gw-accent/40 bg-gw-accent-soft p-3 text-xs text-gw-primary sm:text-sm">
            {{ $exportData['dime_disclaimer'] }}
        </div>
    </div>

    <style>
        @media print {
            .no-print { display: none !important; }
            .print-area { border: none !important; box-shadow: none !important; padding: 0 !important; }
            nav, header, footer { display: none !important; }
        }
    </style>
</div>

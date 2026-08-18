<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border {{ $record->isIssued() ? 'border-emerald-300' : 'border-gw-accent/30' }} bg-gradient-to-br {{ $record->isIssued() ? 'from-emerald-900 via-emerald-800 to-emerald-900' : 'from-gw-primary via-gw-primary-hover to-gw-primary' }} p-6 text-white shadow-lg">
            <a href="{{ route('training.certifications.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; My Certifications</a>
            <h1 class="mt-2 text-3xl font-semibold">{{ $record->certification?->name }}</h1>
            <p class="mt-2 text-sm text-slate-200">{{ str($record->status)->replace('_', ' ')->title() }}</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gw-primary">Certificate details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Certificate number</dt>
                        <dd class="font-semibold text-gw-primary">{{ $record->certificate_number ?? 'Pending' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Issued</dt>
                        <dd class="font-semibold text-gw-primary">{{ $record->issued_at?->format('M j, Y') ?? 'Not issued yet' }}</dd>
                    </div>
                    @if ($record->expires_at)
                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-2">
                            <dt class="text-slate-500">Expires</dt>
                            <dd class="font-semibold text-gw-primary">{{ $record->expires_at->format('M j, Y') }}</dd>
                        </div>
                    @endif
                    @if ($record->approvedBy)
                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-2">
                            <dt class="text-slate-500">Approved by</dt>
                            <dd class="font-semibold text-gw-primary">{{ $record->approvedBy->name }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($record->status === 'pending')
                    <p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Your certification is awaiting mentor approval.
                    </p>
                @elseif ($record->status === 'rejected')
                    <p class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                        This certification request was not approved. Contact your mentor for next steps.
                    </p>
                @endif
            </div>

            <div class="space-y-3">
                @if ($record->certification?->module)
                    <a href="{{ route('training.courses.show', $record->certification->module) }}" class="flex w-full items-center justify-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">
                        View course
                    </a>
                @endif
                <a href="{{ route('training.certifications.index') }}" class="flex w-full items-center justify-center rounded-md bg-gw-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gw-primary-hover">
                    All certifications
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

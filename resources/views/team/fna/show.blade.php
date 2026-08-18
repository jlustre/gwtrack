<x-app-layout>
    <section class="space-y-6">
        @if (session('fna_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('fna_status') }}</div>
        @endif

        @php
            $clientInvite = $fna->is_client_portal ? $fna->clientInvites()->latest('created_at')->first() : null;
        @endphp
        @if ($fna->is_client_portal && $clientInvite)
            <div class="rounded-lg border border-gw-accent/40 bg-gw-accent-soft px-4 py-3 text-sm text-gw-primary">
                <strong>Client portal invite</strong> — {{ $clientInvite->statusLabel() }}
                @if ($clientInvite->last_saved_at)
                    · last saved {{ $clientInvite->last_saved_at->format('M j, Y g:i A') }}
                @endif
                @if ($clientInvite->submitted_at)
                    · submitted {{ $clientInvite->submitted_at->format('M j, Y g:i A') }}
                @endif
            </div>
        @endif

        @include('team.fna.partials.page-shell', [
            'title' => $fna->reference_code.' — '.$fna->client_name,
            'description' => $fna->statusLabel().' · '.$fna->completeness_score.'% complete',
            'actions' => view('team.fna.partials.record-actions', ['fna' => $fna]),
        ])

        @if ($fna->status === 'revision_requested' && $fna->cfm_feedback_summary && (int) $fna->owner_user_id === auth()->id())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>CFM requested revisions:</strong> {{ $fna->cfm_feedback_summary }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="order-2 space-y-6 lg:order-1 lg:col-span-1">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gw-primary">Summary</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="font-semibold text-slate-500">Status</dt><dd>{{ $fna->statusLabel() }}</dd></div>
                        <div><dt class="font-semibold text-slate-500">Completeness</dt><dd>{{ $fna->completeness_score }}%</dd></div>
                        <div><dt class="font-semibold text-slate-500">CFM</dt><dd>{{ $fna->cfm?->name ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-slate-500">Client</dt><dd>{{ $fna->client_name }}</dd></div>
                        <div><dt class="font-semibold text-slate-500">DIME</dt><dd>{{ $fna->dime_completed ? 'Completed' : 'Not completed' }}</dd></div>
                        <div><dt class="font-semibold text-slate-500">Protection Gap</dt><dd>{{ $fna->protection_gap ? '$'.number_format((float) $fna->protection_gap, 0) : '—' }}</dd></div>
                        @if ($fna->submitted_at)
                            <div><dt class="font-semibold text-slate-500">Submitted</dt><dd>{{ $fna->submitted_at->format('M j, Y g:i A') }}</dd></div>
                        @endif
                        @if ($fna->approved_at)
                            <div><dt class="font-semibold text-slate-500">Approved</dt><dd>{{ $fna->approved_at->format('M j, Y g:i A') }}</dd></div>
                        @endif
                    </dl>

                    @can('submit', $fna)
                        @if (in_array($fna->status, ['draft', 'ready_for_review', 'revision_requested']))
                            <button type="button"
                                onclick="Livewire.dispatch('open-fna-submit-modal', { fnaId: '{{ $fna->id }}' })"
                                class="mt-4 w-full rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                                Submit to CFM
                            </button>
                        @endif
                    @endcan
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gw-primary">Status History</h2>
                    @if ($fna->statusHistories->isEmpty())
                        <p class="mt-4 text-sm text-slate-600">No status history yet.</p>
                    @else
                        <ul class="mt-4 space-y-2 text-sm">
                            @foreach ($fna->statusHistories as $history)
                                <li class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                    <span class="font-semibold">{{ config('fna.statuses')[$history->from_status] ?? $history->from_status ?? 'new' }} → {{ config('fna.statuses')[$history->to_status] ?? $history->to_status }}</span>
                                    <span class="text-slate-500"> · {{ $history->changedBy?->name ?? 'System' }} · {{ $history->created_at?->format('M j, g:i A') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="order-1 space-y-6 lg:order-2 lg:col-span-2">
                <livewire:fna.fna-review-panel :fna="$fna" :key="'fna-review-'.$fna->id" />

                <livewire:fna.fna-meeting-prep-panel :fna="$fna" :key="'fna-meeting-prep-'.$fna->id" />

                <livewire:fna.fna-notes-timeline :fna="$fna" :key="'fna-timeline-'.$fna->id" />

                <livewire:fna.fna-attachments-panel :fna="$fna" :key="'fna-attachments-'.$fna->id" />

                @if ($fna->prospect_id && auth()->user()->can('create', \App\Models\FnaClientInvite::class))
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gw-primary">Client Portal Invites</h2>
                        <div class="mt-4">
                            <livewire:fna.fna-client-invite-panel :fna="$fna" :key="'fna-client-invite-'.$fna->id" />
                        </div>
                    </div>
                @endif

                @if (in_array($fna->status, ['approved_by_cfm', 'scheduled_for_client_review', 'follow_up_needed'], true))
                    <div id="schedule" class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gw-primary">Client Meeting</h2>
                        <div class="mt-4">
                            <livewire:fna.fna-calendar-scheduler :fna="$fna" :key="'fna-scheduler-'.$fna->id" />
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <livewire:fna.fna-submit-for-review-modal />
    </section>
</x-app-layout>


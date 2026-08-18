@php

    $funnelLabel = $prospect->funnel?->name ?? (config('prospects.funnel_types')[$prospect->funnel_type ?? 'insurance'] ?? '—');

    $location = collect([$prospect->address_line_1, $prospect->city, $prospect->state_province, $prospect->postal_code, $prospect->country])->filter()->join(', ');

    $types = $prospect->types->pluck('name')->filter()->values();

    $interests = $prospect->interests->pluck('name')->filter()->values();

    $tags = $prospect->tags->pluck('name')->filter()->values();

@endphp



<div class="rounded-lg border border-slate-400 bg-white p-6 shadow-sm lg:col-span-1">

    <h2 class="text-lg font-semibold text-gw-primary">Contact & Qualification</h2>



    <dl class="mt-4 space-y-3 text-sm">

        <div><dt class="font-semibold text-slate-500">Name</dt><dd class="text-gw-primary">{{ $prospect->displayName() }}</dd></div>

        @if ($prospect->fullName() && $prospect->fullName() !== $prospect->displayName())

            <div><dt class="font-semibold text-slate-500">Legal Name</dt><dd class="text-gw-primary">{{ $prospect->fullName() }}</dd></div>

        @endif

        @if ($prospect->preferred_name && $prospect->preferred_name !== $prospect->displayName())

            <div><dt class="font-semibold text-slate-500">Preferred Name</dt><dd class="text-gw-primary">{{ $prospect->preferred_name }}</dd></div>

        @endif

        <div><dt class="font-semibold text-slate-500">Email</dt><dd class="text-gw-primary">@if ($prospect->email)<a href="mailto:{{ $prospect->email }}" class="text-gw-accent-dark hover:underline">{{ $prospect->email }}</a>@else—@endif</dd></div>

        <div><dt class="font-semibold text-slate-500">Mobile</dt><dd class="text-gw-primary">@if ($prospect->phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $prospect->phone) }}" class="text-gw-accent-dark hover:underline">{{ $prospect->phone }}</a>@else—@endif</dd></div>

        <div><dt class="font-semibold text-slate-500">Home</dt><dd class="text-gw-primary">{{ $prospect->home_phone ?? '—' }}</dd></div>

        <div><dt class="font-semibold text-slate-500">Work</dt><dd class="text-gw-primary">{{ $prospect->work_phone ?? '—' }}</dd></div>

        <div><dt class="font-semibold text-slate-500">Location</dt><dd class="text-gw-primary">{{ $location ?: '—' }}</dd></div>

        @if ($prospect->timezone)

            <div><dt class="font-semibold text-slate-500">Timezone</dt><dd class="text-gw-primary">{{ $prospect->timezone }}</dd></div>

        @endif

        @if ($prospect->preferred_language)

            <div><dt class="font-semibold text-slate-500">Language</dt><dd class="text-gw-primary">{{ $prospect->preferred_language }}</dd></div>

        @endif

        @if ($prospect->date_of_birth)

            <div><dt class="font-semibold text-slate-500">Date of Birth</dt><dd class="text-gw-primary">{{ $prospect->date_of_birth->format('M j, Y') }}</dd></div>

        @endif

        @if ($prospect->gender)

            <div><dt class="font-semibold text-slate-500">Gender</dt><dd class="text-gw-primary">{{ config('prospects.genders')[$prospect->gender] ?? $prospect->gender }}</dd></div>

        @endif

        @if ($prospect->marital_status)

            <div><dt class="font-semibold text-slate-500">Marital Status</dt><dd class="text-gw-primary">{{ config('prospects.marital_statuses')[$prospect->marital_status] ?? $prospect->marital_status }}</dd></div>

        @endif

        @if ($prospect->occupation)

            <div><dt class="font-semibold text-slate-500">Profession / Occupation</dt><dd class="text-gw-primary">{{ $prospect->occupation }}</dd></div>

        @endif

        @if ($prospect->employer_business)

            <div><dt class="font-semibold text-slate-500">Employer / Business</dt><dd class="text-gw-primary">{{ $prospect->employer_business }}</dd></div>

        @endif

        @if ($prospect->spouse_name)

            <div><dt class="font-semibold text-slate-500">Spouse Name</dt><dd class="text-gw-primary">{{ $prospect->spouse_name }}</dd></div>

        @endif

        @if ($prospect->spouse_occupation)

            <div><dt class="font-semibold text-slate-500">Spouse Profession</dt><dd class="text-gw-primary">{{ $prospect->spouse_occupation }}</dd></div>

        @endif

        @if ($prospect->spouse_date_of_birth)

            <div><dt class="font-semibold text-slate-500">Spouse Birthday</dt><dd class="text-gw-primary">{{ $prospect->spouse_date_of_birth->format('M j, Y') }}</dd></div>

        @endif

        @if (filled($prospect->dependents))

            <div>

                <dt class="font-semibold text-slate-500">Dependent Children</dt>

                <dd class="mt-1 space-y-1 text-gw-primary">

                    @foreach ($prospect->dependents as $dependent)

                        <div>

                            {{ $dependent['name'] ?? 'Unnamed' }}

                            @if (filled($dependent['age'] ?? null))

                                <span class="text-slate-500">— age {{ $dependent['age'] }}</span>

                            @endif

                        </div>

                    @endforeach

                </dd>

            </div>

        @endif

        @if ($prospect->qualification_notes)

            <div>

                <dt class="font-semibold text-slate-500">Qualification Notes</dt>

                <dd class="mt-1 whitespace-pre-line text-gw-primary">{{ $prospect->qualification_notes }}</dd>

            </div>

        @endif

    </dl>



    <div class="mt-6 border-t border-slate-200 pt-4">

        <h3 class="text-sm font-semibold text-gw-primary">Pipeline & Qualification</h3>

        <dl class="mt-3 space-y-3 text-sm">

            <div><dt class="font-semibold text-slate-500">Funnel</dt><dd class="text-gw-primary">{{ $funnelLabel }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Stage</dt><dd class="text-gw-primary">{{ $prospect->stage?->name ?? '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Status</dt><dd class="text-gw-primary">{{ str($prospect->status)->title() }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Priority</dt><dd class="text-gw-primary">{{ str($prospect->priority)->title() }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Interest Level</dt><dd class="text-gw-primary">{{ str($prospect->interest_level)->title() }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Interest Score</dt><dd class="text-gw-primary">{{ $prospect->interest_score ? $prospect->interest_score.'/10' : '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Engagement Score</dt><dd class="text-gw-primary">{{ $prospect->engagement_score > 0 ? number_format((float) $prospect->engagement_score, 1) : '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">FNA Status</dt><dd class="text-gw-primary">{{ config('prospects.fna_statuses')[$prospect->fna_status] ?? '—' }}</dd></div>

            @php

                $qualificationTraitLabels = \App\Support\ProspectQualificationTraits::labels($prospect->qualification_traits);

            @endphp

            @if ($qualificationTraitLabels !== [])

                <div>

                    <dt class="font-semibold text-slate-500">More Info</dt>

                    <dd class="mt-1 flex flex-wrap gap-1.5">

                        @foreach ($qualificationTraitLabels as $traitLabel)

                            <span class="rounded-full bg-gw-accent-soft px-2.5 py-0.5 text-xs font-semibold text-gw-accent-dark">{{ $traitLabel }}</span>

                        @endforeach

                    </dd>

                </div>

            @endif

        </dl>

    </div>



    <div class="mt-6 border-t border-slate-200 pt-4">

        <h3 class="text-sm font-semibold text-gw-primary">Source & Campaign</h3>

        <dl class="mt-3 space-y-3 text-sm">

            <div><dt class="font-semibold text-slate-500">Lead Source</dt><dd class="text-gw-primary">{{ $prospect->source?->name ?? '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Referral Source</dt><dd class="text-gw-primary">{{ $prospect->referral_source_name ?? '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Campaign</dt><dd class="text-gw-primary">{{ $prospect->campaign_name ?? '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Event</dt><dd class="text-gw-primary">{{ $prospect->event_name ?? '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Social Source</dt><dd class="text-gw-primary">{{ $prospect->social_source ?? '—' }}</dd></div>

        </dl>

    </div>



    @if ($types->isNotEmpty() || $interests->isNotEmpty() || $tags->isNotEmpty())

        <div class="mt-6 border-t border-slate-200 pt-4">

            <h3 class="text-sm font-semibold text-gw-primary">Classification</h3>

            <dl class="mt-3 space-y-3 text-sm">

                @if ($types->isNotEmpty())

                    <div>

                        <dt class="font-semibold text-slate-500">Types</dt>

                        <dd class="mt-1 flex flex-wrap gap-1.5">

                            @foreach ($types as $type)

                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">{{ $type }}</span>

                            @endforeach

                        </dd>

                    </div>

                @endif

                @if ($interests->isNotEmpty())

                    <div>

                        <dt class="font-semibold text-slate-500">Interests</dt>

                        <dd class="mt-1 flex flex-wrap gap-1.5">

                            @foreach ($interests as $interest)

                                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{{ $interest }}</span>

                            @endforeach

                        </dd>

                    </div>

                @endif

                @if ($tags->isNotEmpty())

                    <div>

                        <dt class="font-semibold text-slate-500">Tags</dt>

                        <dd class="mt-1 flex flex-wrap gap-1.5">

                            @foreach ($tags as $tag)

                                <span class="rounded-full bg-gw-accent-muted px-2.5 py-0.5 text-xs font-semibold text-gw-accent-dark">{{ $tag }}</span>

                            @endforeach

                        </dd>

                    </div>

                @endif

            </dl>

        </div>

    @endif



    <div class="mt-6 border-t border-slate-200 pt-4">

        <h3 class="text-sm font-semibold text-gw-primary">Activity</h3>

        <dl class="mt-3 space-y-3 text-sm">

            <div><dt class="font-semibold text-slate-500">Last Contact</dt><dd class="text-gw-primary">{{ $prospect->last_contacted_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Last Activity</dt><dd class="text-gw-primary">{{ $prospect->last_activity_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>

            <div><dt class="font-semibold text-slate-500">Next Follow-Up</dt><dd class="text-gw-primary">{{ $prospect->next_follow_up_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>

            @if ($prospect->follow_up_notes)

                <div>

                    <dt class="font-semibold text-slate-500">Follow-Up Notes</dt>

                    <dd class="mt-1 whitespace-pre-line text-gw-primary">{{ $prospect->follow_up_notes }}</dd>

                </div>

            @endif

            <div><dt class="font-semibold text-slate-500">Next Appointment</dt><dd class="text-gw-primary">{{ $prospect->appointment_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>

        </dl>

    </div>

</div>


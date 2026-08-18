<x-app-layout>
    @php
        $profile = $portal['profile'];
        $training = $portal['training'];
        $achievements = $portal['achievements'];
    @endphp

    <section
        x-data="{
            showEditProfileModal: @js($openEditProfileModal || $errors->any()),
            profileSaving: false,
            editCountryId: @js((string) old('country_id', $portal['editForm']['country_id'] ?? '')),
            editProvinceId: @js((string) old('state_province_id', $portal['editForm']['state_province_id'] ?? '')),
            editProvinces: @js($portal['locationOptions']['provincesByCountryId']),
            showTraineeChecklistModal: false,
            checklistModalLoading: false,
            checklistModalError: null,
            checklistModalData: null,
            checklistModalView: 'accomplished',
            get editProvinceOptions() {
                return this.editProvinces[String(this.editCountryId)] || {};
            },
            syncProvinceSelect(selectId = 'edit-state-province-id') {
                const next = window.rebuildProvinceSelectOptions(selectId, this.editProvinceOptions, this.editProvinceId);
                if (next !== this.editProvinceId) {
                    this.editProvinceId = next;
                }
            },
            onCountryChange() {
                this.syncProvinceSelect('edit-state-province-id');
            },
            submitProfileForm() {
                this.profileSaving = true;
            },
            closeTraineeChecklistModal() {
                this.showTraineeChecklistModal = false;
                this.checklistModalLoading = false;
                this.checklistModalError = null;
                this.checklistModalData = null;
                this.checklistModalView = 'accomplished';
            },
            async openTraineeChecklistModal(url) {
                this.showTraineeChecklistModal = true;
                this.checklistModalLoading = true;
                this.checklistModalError = null;
                this.checklistModalData = null;
                this.checklistModalView = 'accomplished';

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        throw new Error('Failed to load checklist');
                    }

                    this.checklistModalData = await response.json();
                } catch (error) {
                    this.checklistModalError = 'Could not load mentoring checklist. Please try again.';
                } finally {
                    this.checklistModalLoading = false;
                }
            },
            accomplishedPhases() {
                if (! this.checklistModalData?.phases) {
                    return [];
                }

                return this.checklistModalData.phases
                    .map((phase) => {
                        const sections = phase.sections
                            .map((section) => ({
                                ...section,
                                items: section.items.filter((item) => item.is_completed),
                            }))
                            .filter((section) => section.items.length > 0);

                        return { ...phase, sections };
                    })
                    .filter((phase) => phase.sections.length > 0);
            },
            remainingPhases() {
                if (! this.checklistModalData?.phases) {
                    return [];
                }

                return this.checklistModalData.phases
                    .map((phase) => {
                        const sections = phase.sections
                            .map((section) => ({
                                ...section,
                                items: section.items.filter((item) => ! item.is_completed),
                            }))
                            .filter((section) => section.items.length > 0);

                        return { ...phase, sections };
                    })
                    .filter((phase) => phase.sections.length > 0);
            },
        }"
        x-init="
            @if (session('profile_feedback'))
                $nextTick(() => document.getElementById('cfm-portal-profile-feedback')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
            @endif
        "
        class="cfm-portal-page space-y-6"
    >
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            @include('cfm-portal.partials.header')
        </div>

        @include('cfm-portal.partials.flash')
        @include('cfm-portal.partials.pending-assignments')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-tracker-stat-card
                label="Active Trainees"
                :value="$profile['activeApprentices'].'/'.$profile['maxApprentices']"
                theme="navy"
                :subtitle="$profile['pendingApprentices'].' pending approval'"
            />
            <x-tracker-stat-card
                label="FAP Completion Rate"
                :value="$profile['fapCompletionRate'].'%'"
                theme="emerald"
                :subtitle="$profile['completedApprentices'].' graduates'"
            />
            <x-tracker-stat-card
                label="Recommendation Score"
                :value="$profile['recommendationScore'].'/100'"
                theme="gold"
                :subtitle="$profile['recommendationBand']"
            />
            <x-tracker-stat-card
                label="Upcoming Sessions"
                :value="$profile['upcomingSessions']"
                theme="cyan"
                :subtitle="'Next: '.$profile['nextSlot']"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @include('cfm-portal.partials.profile')
            @include('cfm-portal.partials.achievements')
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @include('cfm-portal.partials.trainees')
            @include('cfm-portal.partials.training')
        </div>

        @include('cfm-portal.partials.rank-structure')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @include('cfm-portal.partials.calendar')
            @include('cfm-portal.partials.activity')
        </div>

        @include('cfm-portal.partials.edit-profile-modal')
        @include('cfm-portal.partials.trainee-checklist-modal')
    </section>
</x-app-layout>

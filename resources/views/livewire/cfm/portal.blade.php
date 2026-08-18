@php
    $profile = $portal['profile'];
    $training = $portal['training'];
    $openEditProfileModal = (bool) session('open_edit_profile_modal', false);
@endphp

<div
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
    class="cfm-portal-page space-y-4"
>
    @include('livewire.cfm.partials.hero', [
        'user' => $viewer,
        'portal' => $portal,
        'todayLabel' => $todayLabel,
    ])

    <div class="space-y-4">
        @include('cfm-portal.partials.flash')
        @include('cfm-portal.partials.pending-assignments')

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]">
            @include('livewire.cfm.partials.trainee-sidebar')

            <div class="min-w-0 w-full space-y-4">
                @include('livewire.cfm.partials.summary-cards')

                <div class="flex justify-end">
                    <a href="{{ $rosterExportUrl }}" class="inline-flex items-center rounded-lg border border-gw-accent/50 bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-accent-dark transition hover:bg-gw-accent/20">
                        Export full trainee roster (CSV)
                    </a>
                </div>

                @if ($selectedTraineeId && $trainee360)
                    @include('livewire.cfm.partials.trainee-nav')

                    @if ($activeSection === 'overview')
                        @include('livewire.cfm.partials.trainee-360')
                    @elseif ($sectionCenter)
                        @include('livewire.cfm.partials.centers.'.$activeSection)
                    @else
                        @include('livewire.cfm.partials.section-placeholder')
                    @endif
                @else
                    @include('livewire.cfm.partials.cfm-dashboard')
                @endif
            </div>
        </div>
    </div>

    @include('cfm-portal.partials.edit-profile-modal')
    @include('cfm-portal.partials.trainee-checklist-modal')
    @include('livewire.cfm.partials.trainee-quick-action-modals')
</div>

export default function cfmManagement(initial = {}) {
    const workloadColors = {
        available: 'green',
        moderate: 'amber',
        busy: 'orange',
        overloaded: 'red',
        unavailable: 'gray',
    };

    return {
        viewMode: 'table',
        showFilters: false,
        showProfilePanel: false,
        showAssignModal: false,
        showFapQueueModal: false,
        showAddCfmModal: false,
        showExportModal: false,
        selectedCfm: null,
        assignCfmId: '',
        assignAssociateId: '',
        assignSubmitting: false,
        assignError: null,
        assignSuccess: null,
        assignUrl: initial.assignUrl ?? '',
        assignForm: {
            reason: '',
            startDate: '',
            endDate: '',
            notes: '',
            notifyCfm: true,
            notifyAssociate: true,
            requireApproval: true,
        },
        compareCfms: ['', '', ''],
        hierarchyFilter: 'All Accessible',
        searchQuery: '',
        filterWorkload: '',
        filterCountry: '',
        filterRank: '',
        stats: initial.stats ?? {},
        assignableAssociates: initial.assignableAssociates ?? [],
        fapQueue: initial.fapQueue ?? [],
        cfmCandidates: initial.cfmCandidates ?? [],
        recommendationsByAssociate: initial.recommendationsByAssociate ?? {},
        selectedRecommendationAssociateId: initial.defaultRecommendationAssociateId ?? null,
        cfms: [],
        addCfmForm: {
            userId: '',
            targetRank: 'CFM I',
            notes: '',
            requireApproval: false,
            notifyCandidate: true,
        },
        addCfmSubmitting: false,
        addCfmError: null,
        addCfmSuccess: null,
        addCfmUrl: initial.addCfmUrl ?? '',
        exportForm: {
            scope: 'filtered',
            format: 'csv',
            includeStats: true,
            includeWorkload: true,
            includeCalendar: true,
        },
        locationOptions: initial.locationOptions ?? {},
        jurisdictionDisplayLabels: initial.locationOptions?.jurisdictionDisplayLabels ?? {},
        licensedJurisdictionsUrlTemplate: initial.licensedJurisdictionsUrlTemplate ?? '',
        licensedUpdateUrl: '',
        showLicensedEdit: false,
        licensedSaving: false,
        licensedDraft: [],

        init() {
            this.cfms = (initial.cfms ?? []).map((cfm) => this.normalizeCfm(cfm));

            if (initial.focusCfmId) {
                const focused = this.cfms.find((cfm) => cfm.id === Number(initial.focusCfmId));
                if (focused) {
                    this.selectedCfm = focused;
                    this.showProfilePanel = Boolean(initial.openCfmProfilePanel);
                    this.syncLicensedUpdateUrl();
                    if (initial.openCfmLicensedEdit) {
                        this.licensedDraft = [...(initial.oldLicensedJurisdictions ?? focused.licensedJurisdictions ?? [])];
                        this.syncLicensedUpdateUrl();
                        this.showLicensedEdit = true;
                    }
                }
            } else if (this.cfms.length) {
                this.selectedCfm = this.cfms[0];
                this.syncLicensedUpdateUrl();
            }

            if (initial.openAssignModal) {
                this.openAssign();
                if (initial.oldAssignAssociateId) {
                    this.assignAssociateId = String(initial.oldAssignAssociateId);
                }
                if (initial.oldAssignCfmId) {
                    this.assignCfmId = String(initial.oldAssignCfmId);
                }
            }

            if (initial.openAddCfmModal) {
                this.openAddCfm();
            }
        },

        normalizeCfm(cfm) {
            return {
                ...cfm,
                hierarchy: cfm.hierarchySource,
                completionRate: Math.round(cfm.fapCompletionRate ?? 0),
                score: cfm.recommendationScore ?? 0,
                nextAvailable: cfm.nextSlot ?? '—',
                statusColor: workloadColors[cfm.workloadKey] ?? 'amber',
                statusText: cfm.workloadStatus,
                location: [cfm.city, cfm.province].filter((v) => v && v !== '—').join(', ') || cfm.country,
                loadPercent: cfm.loadPercent ?? 0,
            };
        },

        get filteredCfms() {
            return this.cfms.filter((cfm) => {
                if (this.searchQuery) {
                    const q = this.searchQuery.toLowerCase();
                    const haystack = `${cfm.name} ${cfm.email} ${cfm.phone}`.toLowerCase();
                    if (!haystack.includes(q)) {
                        return false;
                    }
                }
                if (this.hierarchyFilter === 'My Hierarchy' && !cfm.inMyHierarchy) {
                    return false;
                }
                if (this.hierarchyFilter === 'Other Hierarchy' && cfm.inMyHierarchy) {
                    return false;
                }
                if (this.filterWorkload && cfm.workloadKey !== this.filterWorkload) {
                    return false;
                }
                if (this.filterCountry && cfm.country !== this.filterCountry) {
                    return false;
                }
                if (this.filterRank && cfm.rank !== this.filterRank) {
                    return false;
                }

                return true;
            });
        },

        get busyOverloadedCount() {
            return (this.stats.busy ?? 0) + (this.stats.overloaded ?? 0);
        },

        get exportRows() {
            return this.exportForm.scope === 'filtered' ? this.filteredCfms : this.cfms;
        },

        closeAllModals() {
            this.showAssignModal = false;
            this.showFapQueueModal = false;
            this.showAddCfmModal = false;
            this.showExportModal = false;
        },

        get licensedCountries() {
            return this.locationOptions.countries ?? [];
        },

        provincesForCountry(country) {
            const provinces = this.locationOptions.provincesByCountry?.[country] ?? {};

            return Object.entries(provinces).map(([value, label]) => ({
                key: `${country}|${value}`,
                label: this.jurisdictionDisplayLabels[`${country}|${value}`] ?? label,
            }));
        },

        jurisdictionKey(country, province) {
            return `${country}|${province}`;
        },

        syncLicensedUpdateUrl() {
            if (! this.selectedCfm?.id || ! this.licensedJurisdictionsUrlTemplate) {
                this.licensedUpdateUrl = '';

                return;
            }

            this.licensedUpdateUrl = this.licensedJurisdictionsUrlTemplate.replace(
                '__CFM__',
                String(this.selectedCfm.id)
            );
        },

        openLicensedEdit() {
            this.licensedDraft = [...(this.selectedCfm?.licensedJurisdictions ?? [])];
            this.syncLicensedUpdateUrl();
            this.showLicensedEdit = true;
        },

        closeLicensedEdit() {
            this.showLicensedEdit = false;
            this.licensedDraft = [];
        },

        toggleLicensedJurisdiction(key, checked) {
            if (checked) {
                if (! this.licensedDraft.includes(key)) {
                    this.licensedDraft.push(key);
                }

                return;
            }

            this.licensedDraft = this.licensedDraft.filter((item) => item !== key);
        },

        cfmCoversAssociateJurisdiction(cfm, associate) {
            if (! associate?.jurisdictionKey) {
                return null;
            }

            const licensed = cfm?.licensedJurisdictions ?? [];

            if (! licensed.length) {
                return false;
            }

            return licensed.includes(associate.jurisdictionKey);
        },

        get selectedAssignAssociate() {
            return this.assignableAssociates.find(
                (row) => String(row.id) === String(this.assignAssociateId)
            );
        },

        get selectedRecommendationAssociate() {
            if (! this.selectedRecommendationAssociateId) {
                return null;
            }

            return this.fapQueue.find(
                (row) => String(row.id) === String(this.selectedRecommendationAssociateId)
            ) ?? null;
        },

        get aiSuggestions() {
            if (! this.selectedRecommendationAssociateId) {
                return [{
                    type: 'hint',
                    detail: 'Select an associate from the FAP queue to see personalized CFM matches.',
                }];
            }

            return this.recommendationsByAssociate[String(this.selectedRecommendationAssociateId)]
                ?? this.recommendationsByAssociate[this.selectedRecommendationAssociateId]
                ?? [];
        },

        get assignableCfmsForSelectedAssociate() {
            const associate = this.selectedAssignAssociate;

            if (! associate?.jurisdictionKey) {
                return [];
            }

            return this.cfms.filter(
                (cfm) => this.cfmCoversAssociateJurisdiction(cfm, associate) === true
            );
        },

        cfmIsTraineeUplineForAssociate(cfm, associate) {
            const uplineIds = (associate?.uplineCfmIds ?? []).map((id) => String(id));

            return uplineIds.includes(String(cfm.id));
        },

        get canSubmitAssignment() {
            if (! this.assignAssociateId || ! this.assignCfmId) {
                return false;
            }

            const associate = this.selectedAssignAssociate;
            const cfm = this.cfms.find((row) => String(row.id) === String(this.assignCfmId));

            if (! associate?.jurisdictionKey || ! cfm) {
                return false;
            }

            return this.cfmCoversAssociateJurisdiction(cfm, associate) === true;
        },

        onAssignAssociateChange() {
            const allowedIds = new Set(
                this.assignableCfmsForSelectedAssociate.map((cfm) => String(cfm.id))
            );

            if (this.assignCfmId && ! allowedIds.has(String(this.assignCfmId))) {
                this.assignCfmId = '';
            }
        },

        getLicenseMatchWarning() {
            const associate = this.selectedAssignAssociate;
            const cfm = this.cfms.find((row) => String(row.id) === String(this.assignCfmId));

            if (! associate || ! cfm || ! associate.jurisdictionKey) {
                return '';
            }

            if (this.cfmCoversAssociateJurisdiction(cfm, associate) === true) {
                if (this.cfmIsTraineeUplineForAssociate(cfm, associate)) {
                    return `${cfm.name} is licensed in ${associate.locationLabel || 'the associate\'s jurisdiction'} and is upline in the trainee's hierarchy.`;
                }

                return `${cfm.name} is licensed in ${associate.locationLabel || 'the associate\'s jurisdiction'}.`;
            }

            return '';
        },

        selectCfm(cfm) {
            this.selectedCfm = cfm;
            this.showProfilePanel = true;
            this.closeLicensedEdit();
            this.syncLicensedUpdateUrl();
        },

        openAssign(cfm = null) {
            this.assignError = null;
            this.assignSuccess = null;
            if (cfm) {
                this.selectedCfm = cfm;
                this.assignCfmId = '';
            } else if (this.selectedCfm) {
                this.assignCfmId = '';
            } else {
                this.assignCfmId = '';
            }
            this.closeAllModals();
            this.showAssignModal = true;
            this.$nextTick?.(() => this.onAssignAssociateChange());
        },

        async submitAssignment() {
            this.assignError = null;
            this.assignSuccess = null;

            if (! this.assignAssociateId || ! this.assignCfmId) {
                this.assignError = 'Please select both an associate and a CFM.';
                return;
            }

            if (! this.canSubmitAssignment) {
                this.assignError = 'Select a CFM licensed in the associate\'s province or state.';
                return;
            }

            if (! this.assignUrl) {
                this.assignError = 'Assignment endpoint is not configured.';
                return;
            }

            this.assignSubmitting = true;

            try {
                const response = await fetch(this.assignUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        associate_id: Number(this.assignAssociateId),
                        cfm_id: Number(this.assignCfmId),
                        reason: this.assignForm.reason || null,
                        start_date: this.assignForm.startDate || null,
                        end_date: this.assignForm.endDate || null,
                        notes: this.assignForm.notes || null,
                        notify_cfm: this.assignForm.notifyCfm,
                        notify_associate: this.assignForm.notifyAssociate,
                        require_cfm_approval: this.assignForm.requireApproval,
                    }),
                });

                const data = await response.json();

                if (! response.ok) {
                    const firstError = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : (data.message ?? 'Assignment could not be completed.');
                    throw new Error(firstError);
                }

                this.assignSuccess = data.message;
                window.setTimeout(() => window.location.reload(), 800);
            } catch (error) {
                this.assignError = error.message ?? 'Assignment could not be completed.';
            } finally {
                this.assignSubmitting = false;
            }
        },

        openFapQueue() {
            this.closeAllModals();
            this.showFapQueueModal = true;
        },

        openAddCfm() {
            this.addCfmError = null;
            this.addCfmSuccess = null;
            this.closeAllModals();
            this.showAddCfmModal = true;
        },

        openExport() {
            this.closeAllModals();
            this.showExportModal = true;
        },

        selectAssociateForRecommendations(associate) {
            this.selectedRecommendationAssociateId = associate.id;
        },

        assignFromQueue(associate) {
            this.selectAssociateForRecommendations(associate);
            this.assignAssociateId = String(associate.id);
            this.openAssign();
        },

        async submitAddCfm() {
            this.addCfmError = null;
            this.addCfmSuccess = null;

            if (! this.addCfmForm.userId) {
                this.addCfmError = 'Please select a team member to nominate.';
                return;
            }

            if (! this.addCfmUrl) {
                this.addCfmError = 'Add CFM endpoint is not configured.';
                return;
            }

            this.addCfmSubmitting = true;

            try {
                const response = await fetch(this.addCfmUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        user_id: Number(this.addCfmForm.userId),
                        target_rank: this.addCfmForm.targetRank,
                        notes: this.addCfmForm.notes || null,
                        require_approval: this.addCfmForm.requireApproval,
                        notify_candidate: this.addCfmForm.notifyCandidate,
                    }),
                });

                const data = await response.json();

                if (! response.ok) {
                    const firstError = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : (data.message ?? 'CFM nomination could not be completed.');
                    throw new Error(firstError);
                }

                this.addCfmSuccess = data.message;
                window.setTimeout(() => window.location.reload(), 800);
            } catch (error) {
                this.addCfmError = error.message ?? 'CFM nomination could not be completed.';
            } finally {
                this.addCfmSubmitting = false;
            }
        },

        exportReport() {
            const rows = this.exportRows;
            if (! rows.length) {
                return;
            }

            const headers = ['Name', 'Email', 'Rank', 'Hierarchy', 'Active Apprentices', 'Max', 'Completion %', 'Score', 'Workload', 'Overdue Tasks', 'Next Available', 'Timezone'];
            const lines = [headers.join(',')];

            rows.forEach((cfm) => {
                lines.push([
                    this.csvEscape(cfm.name),
                    this.csvEscape(cfm.email),
                    this.csvEscape(cfm.rank),
                    this.csvEscape(cfm.hierarchy),
                    cfm.activeApprentices,
                    cfm.maxApprentices,
                    cfm.completionRate,
                    cfm.score,
                    this.csvEscape(cfm.statusText),
                    cfm.overdueTasks,
                    this.csvEscape(cfm.nextAvailable),
                    this.csvEscape(cfm.timezone),
                ].join(','));
            });

            if (this.exportForm.includeStats) {
                lines.push('');
                lines.push('Summary Stats');
                lines.push(`Total CFMs,${this.stats.total ?? 0}`);
                lines.push(`Available,${this.stats.available ?? 0}`);
                lines.push(`Pending FAP,${this.stats.pendingFap ?? 0}`);
                lines.push(`FAP Completion Rate,${this.stats.fapCompletionRate ?? 0}%`);
            }

            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `cfm-management-report-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
            URL.revokeObjectURL(url);
            this.showExportModal = false;
        },

        csvEscape(value) {
            const text = String(value ?? '');
            if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                return `"${text.replace(/"/g, '""')}"`;
            }
            return text;
        },

        clearFilters() {
            this.searchQuery = '';
            this.hierarchyFilter = 'All Accessible';
            this.filterWorkload = '';
            this.filterCountry = '';
            this.filterRank = '';
        },

        loadWidth(cfm) {
            const max = cfm.maxApprentices || 6;
            return Math.min(100, Math.round((cfm.activeApprentices / max) * 100));
        },

        scoreClass(score) {
            if (score >= 80) {
                return 'bg-emerald-50 text-emerald-700';
            }
            if (score >= 60) {
                return 'bg-gw-accent-soft text-gw-accent-dark';
            }
            return 'bg-red-50 text-red-700';
        },

        statusDotClass(color) {
            const map = {
                green: 'bg-green-500',
                amber: 'bg-amber-500',
                orange: 'bg-orange-500',
                red: 'bg-red-500',
                gray: 'bg-gray-500',
            };
            return map[color] ?? 'bg-amber-500';
        },

        statusBadgeClass(color) {
            const map = {
                green: 'bg-emerald-50 text-emerald-700',
                amber: 'bg-amber-50 text-amber-800',
                orange: 'bg-orange-50 text-orange-700',
                red: 'bg-red-50 text-red-700',
                gray: 'bg-slate-100 text-slate-600',
            };
            return map[color] ?? 'bg-amber-50 text-amber-800';
        },

        hierarchyBadgeClass(cfm) {
            return cfm.inMyHierarchy
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-sky-50 text-sky-700';
        },

        getWorkloadWarning() {
            const c = this.cfms.find((row) => String(row.id) === String(this.assignCfmId));
            if (!c) {
                return '';
            }
            if (c.activeApprentices >= c.maxApprentices) {
                return '⚠️ Warning: This CFM is at or over capacity. Assignment may cause overload.';
            }
            if (c.overdueTasks > 2) {
                return '⚠️ This CFM has overdue mentor tasks. Review before assigning.';
            }
            if (c.recommendationBand === 'Not Recommended') {
                return '⚠️ Assignment readiness is low for this CFM this week.';
            }
            return '✅ CFM has capacity and good availability. Recommended for assignment.';
        },

        getCompareDetails(index) {
            const id = this.compareCfms[index];
            if (!id) {
                return '';
            }
            const c = this.cfms.find((row) => String(row.id) === String(id));
            if (!c) {
                return '';
            }
            return `Active: ${c.activeApprentices}/${c.maxApprentices} · Completion: ${c.completionRate}% · Score: ${c.score} · Overdue: ${c.overdueTasks} · Next: ${c.nextAvailable}`;
        },

        selectAiSuggestion(item) {
            const match = item.cfmId
                ? this.cfms.find((c) => c.id === item.cfmId)
                : this.cfms.find((c) => c.name === item.cfmName);

            if (match) {
                this.selectCfm(match);
            }
        },

        aiBorderClass(item) {
            if (item.statusLabel === 'Recommended') {
                return 'border-l-emerald-500';
            }
            if (item.statusLabel === 'Use Caution') {
                return 'border-l-amber-500';
            }
            if (item.statusLabel === 'Not Recommended') {
                return 'border-l-red-500';
            }
            return 'border-l-slate-400';
        },

        aiTitleClass(item) {
            if (item.statusLabel === 'Recommended') {
                return 'text-emerald-700';
            }
            if (item.statusLabel === 'Use Caution') {
                return 'text-amber-700';
            }
            if (item.statusLabel === 'Not Recommended') {
                return 'text-red-700';
            }
            return 'text-slate-600';
        },
    };
}

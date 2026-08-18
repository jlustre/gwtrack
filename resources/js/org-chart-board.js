function progressLabel(entry) {
    if (entry && typeof entry === 'object') {
        return entry.started ? `${entry.percent ?? 0}%` : 'Not started';
    }

    return `${entry ?? 0}%`;
}

function progressWidth(entry) {
    if (entry && typeof entry === 'object') {
        return entry.started ? (entry.percent ?? 0) : 0;
    }

    return entry ?? 0;
}

export default function orgChartBoard(leaders = [], leaderExpandedDefaults = {}, rootProfile = null) {
    const filterFields = [
        { key: 'rank', label: 'Rank', allLabel: 'All ranks', dynamic: true },
        { key: 'role_label', label: 'Role', allLabel: 'All roles', dynamic: true },
        { key: 'country', label: 'Country', allLabel: 'All countries', dynamic: true },
        {
            key: 'status',
            label: 'Status',
            allLabel: 'All statuses',
            options: [
                { value: '', label: 'All statuses' },
                { value: 'Active', label: 'Active' },
                { value: 'Inactive', label: 'Inactive' },
            ],
        },
    ];

    const searchKeys = [
        'name',
        'email',
        'rank',
        'rank_name',
        'role_label',
        'country',
        'mentor',
        'city',
        'status',
    ];

    const initialFilters = Object.fromEntries(filterFields.map((field) => [field.key, '']));

    return {
        rows: leaders,
        searchKeys,
        filterFields,
        searchQuery: '',
        filters: initialFilters,
        rootExpanded: false,
        leaderExpanded: { ...leaderExpandedDefaults },
        rootProfile,
        profileModalOpen: false,
        selectedProfile: null,

        progressLabel,
        progressWidth,

        get dynamicFilterOptions() {
            const options = {};

            for (const field of this.filterFields) {
                if (! field.dynamic) {
                    continue;
                }

                const values = [...new Set(
                    this.rows
                        .map((row) => row[field.key])
                        .filter((value) => value && value !== '—')
                )].sort((a, b) => String(a).localeCompare(String(b)));

                options[field.key] = [
                    { value: '', label: field.allLabel ?? 'All' },
                    ...values.map((value) => ({ value, label: value })),
                ];
            }

            return options;
        },

        optionsForField(field) {
            if (field.dynamic) {
                return this.dynamicFilterOptions[field.key] ?? [{ value: '', label: field.allLabel ?? 'All' }];
            }

            return field.options ?? [{ value: '', label: field.allLabel ?? 'All' }];
        },

        get filteredRows() {
            let result = this.rows;

            const query = this.searchQuery.trim().toLowerCase();
            if (query !== '') {
                result = result.filter((row) =>
                    this.searchKeys.some((key) =>
                        String(row[key] ?? '').toLowerCase().includes(query)
                    )
                );
            }

            for (const field of this.filterFields) {
                const value = this.filters[field.key];
                if (! value) {
                    continue;
                }

                result = result.filter((row) => String(row[field.key] ?? '') === value);
            }

            return result;
        },

        get filteredCount() {
            return this.filteredRows.length;
        },

        get totalCount() {
            return this.rows.length;
        },

        get hasActiveFilters() {
            if (this.searchQuery.trim() !== '') {
                return true;
            }

            return Object.values(this.filters).some((value) => value !== '');
        },

        clearFilters() {
            this.searchQuery = '';
            for (const field of this.filterFields) {
                this.filters[field.key] = '';
            }
        },

        expandAll() {
            this.rootExpanded = true;
            this.filteredRows.forEach((leader) => {
                this.leaderExpanded[String(leader.id)] = true;
            });
        },

        collapseAll() {
            this.rootExpanded = false;
            Object.keys(this.leaderExpanded).forEach((id) => {
                this.leaderExpanded[id] = false;
            });
        },

        toggleLeader(id) {
            const key = String(id);
            this.leaderExpanded[key] = ! this.leaderExpanded[key];
        },

        isLeaderExpanded(id) {
            return !! this.leaderExpanded[String(id)];
        },

        openProfile(profile) {
            this.selectedProfile = profile;
            this.profileModalOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        openRootProfile() {
            if (this.rootProfile) {
                this.openProfile(this.rootProfile);
            }
        },

        closeProfile() {
            this.profileModalOpen = false;
            this.selectedProfile = null;
            document.body.classList.remove('overflow-hidden');
        },
    };
}

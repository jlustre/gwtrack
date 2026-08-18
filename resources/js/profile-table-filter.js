export default function profileTableFilter(rows = [], options = {}) {
    const searchKeys = options.searchKeys ?? [];
    const filterFields = options.filterFields ?? [];
    const sumKey = options.sumKey ?? null;

    const initialFilters = {};
    for (const field of filterFields) {
        initialFilters[field.key] = '';
    }

    return {
        rows,
        searchKeys,
        filterFields,
        sumKey,
        searchQuery: '',
        filters: initialFilters,

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

        get filteredSum() {
            if (! this.sumKey) {
                return 0;
            }

            return this.filteredRows.reduce(
                (total, row) => total + (Number(row[this.sumKey]) || 0),
                0
            );
        },

        clearFilters() {
            this.searchQuery = '';
            for (const field of this.filterFields) {
                this.filters[field.key] = '';
            }
        },
    };
}

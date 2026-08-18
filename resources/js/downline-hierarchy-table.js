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

export default function downlineHierarchyTable(config = {}) {
    const rows = config.rows ?? [];
    const searchMembers = config.searchMembers ?? rows;
    const rootId = config.rootId ?? null;

    const parentById = Object.fromEntries(
        rows.map((row) => [row.id, row.parent_id ?? null]),
    );

    const defaultExpanded = {};
    rows.forEach((row) => {
        if (row.has_children) {
            defaultExpanded[row.id] = row.depth <= 1;
        }
    });
    if (rootId !== null) {
        defaultExpanded[rootId] = true;
    }

    return {
        rows,
        searchMembers,
        rootId,
        expanded: defaultExpanded,
        memberSearch: '',
        memberSearchOpen: false,
        memberSearchHighlight: -1,
        profileModalOpen: false,
        selectedProfile: null,

        progressLabel,
        progressWidth,

        openProfile(row) {
            this.selectedProfile = row.profile ?? null;
            this.profileModalOpen = this.selectedProfile !== null;
        },

        closeProfile() {
            this.profileModalOpen = false;
            this.selectedProfile = null;
        },

        memberSearchMatches() {
            const term = this.memberSearch.trim().toLowerCase();
            if (term.length < 3) {
                return [];
            }

            return this.searchMembers.filter((member) => {
                const name = member.name.toLowerCase();

                return name.startsWith(term) || name.includes(term);
            });
        },

        onMemberSearchInput() {
            this.memberSearchOpen = this.memberSearchMatches().length > 0;
            this.memberSearchHighlight = -1;
        },

        closeMemberSearch() {
            this.memberSearchOpen = false;
            this.memberSearchHighlight = -1;
        },

        selectMember(row) {
            if (! row?.hierarchy_top_url) {
                return;
            }

            window.location.assign(row.hierarchy_top_url);
        },

        highlightNextMatch() {
            const matches = this.memberSearchMatches();
            if (matches.length === 0) {
                return;
            }

            this.memberSearchOpen = true;
            this.memberSearchHighlight = (this.memberSearchHighlight + 1) % matches.length;
        },

        highlightPreviousMatch() {
            const matches = this.memberSearchMatches();
            if (matches.length === 0) {
                return;
            }

            this.memberSearchOpen = true;
            this.memberSearchHighlight = this.memberSearchHighlight <= 0
                ? matches.length - 1
                : this.memberSearchHighlight - 1;
        },

        selectHighlightedMember() {
            const matches = this.memberSearchMatches();
            const row = matches[this.memberSearchHighlight];

            if (row) {
                this.selectMember(row);
            }
        },

        isVisible(row) {
            let parentId = row.parent_id;

            while (parentId !== null && parentId !== undefined) {
                if (! this.expanded[parentId]) {
                    return false;
                }
                parentId = parentById[parentId] ?? null;
            }

            return true;
        },

        isExpanded(id) {
            return Boolean(this.expanded[id]);
        },

        toggle(id) {
            this.expanded[id] = ! this.isExpanded(id);
        },

        expandAll() {
            this.rows.forEach((row) => {
                if (row.has_children) {
                    this.expanded[row.id] = true;
                }
            });
        },

        collapseAll() {
            this.rows.forEach((row) => {
                if (row.id !== this.rootId && row.has_children) {
                    this.expanded[row.id] = false;
                }
            });
        },
    };
}

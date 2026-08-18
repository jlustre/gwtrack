const INTERACTIVE_SELECTOR = 'a, button, input, select, textarea, label, [role="button"]';

export default function genealogyTreePan(config = {}) {
    const searchUrl = config.searchUrl ?? null;

    return {
        searchUrl,
        memberSearch: '',
        memberSearchOpen: false,
        memberSearchHighlight: -1,
        memberSearchResults: [],
        memberSearchLoading: false,
        memberSearchDebounce: null,
        zoom: 1,
        compact: false,
        panning: false,
        panStartX: 0,
        panScrollLeft: 0,

        memberSearchMatches() {
            return this.memberSearchResults;
        },

        onMemberSearchInput() {
            const term = this.memberSearch.trim();

            if (term.length < 3) {
                this.clearMemberSearchResults();

                return;
            }

            window.clearTimeout(this.memberSearchDebounce);
            this.memberSearchDebounce = window.setTimeout(() => {
                this.fetchMemberSearch(term);
            }, 250);
        },

        async fetchMemberSearch(term) {
            if (! this.searchUrl) {
                return;
            }

            const activeTerm = this.memberSearch.trim();

            if (activeTerm.length < 3 || activeTerm !== term) {
                return;
            }

            this.memberSearchLoading = true;

            try {
                const url = new URL(this.searchUrl, window.location.origin);
                url.searchParams.set('q', term);

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    throw new Error('Search request failed.');
                }

                const data = await response.json();

                if (this.memberSearch.trim() !== term) {
                    return;
                }

                this.memberSearchResults = data.members ?? [];
                this.memberSearchOpen = this.memberSearchResults.length > 0;
                this.memberSearchHighlight = -1;
            } catch {
                if (this.memberSearch.trim() === term) {
                    this.memberSearchResults = [];
                    this.memberSearchOpen = false;
                    this.memberSearchHighlight = -1;
                }
            } finally {
                if (this.memberSearch.trim() === term) {
                    this.memberSearchLoading = false;
                }
            }
        },

        clearMemberSearchResults() {
            window.clearTimeout(this.memberSearchDebounce);
            this.memberSearchResults = [];
            this.memberSearchOpen = false;
            this.memberSearchHighlight = -1;
            this.memberSearchLoading = false;
        },

        closeMemberSearch() {
            this.memberSearchOpen = false;
            this.memberSearchHighlight = -1;
        },

        selectMember(member) {
            if (!member?.tree_top_url) {
                return;
            }

            window.location.assign(member.tree_top_url);
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
            const member = matches[this.memberSearchHighlight];

            if (member) {
                this.selectMember(member);
            }
        },

        isInteractiveTarget(target) {
            return target instanceof Element && Boolean(target.closest(INTERACTIVE_SELECTOR));
        },

        startPan(event) {
            if (event.button !== 0 || this.isInteractiveTarget(event.target)) {
                return;
            }

            this.panning = true;
            this.panStartX = event.clientX;
            this.panScrollLeft = this.$refs.surface.scrollLeft;
            this.setPanCursor(true);
        },

        startPanTouch(event) {
            if (event.touches.length !== 1 || this.isInteractiveTarget(event.target)) {
                return;
            }

            this.panning = true;
            this.panStartX = event.touches[0].clientX;
            this.panScrollLeft = this.$refs.surface.scrollLeft;
            this.setPanCursor(true);
        },

        pan(event) {
            if (!this.panning) {
                return;
            }

            const clientX = event.touches ? event.touches[0].clientX : event.clientX;

            if (event.cancelable) {
                event.preventDefault();
            }

            this.$refs.surface.scrollLeft = this.panScrollLeft - (clientX - this.panStartX);
        },

        endPan() {
            if (!this.panning) {
                return;
            }

            this.panning = false;
            this.setPanCursor(false);
        },

        setPanCursor(active) {
            const surface = this.$refs.surface;

            if (!surface) {
                return;
            }

            surface.classList.toggle('cursor-grabbing', active);
            surface.classList.toggle('cursor-grab', !active);
            surface.classList.toggle('select-none', active);
        },

        zoomIn() {
            this.zoom = Math.min(1.4, Math.round((this.zoom + 0.1) * 10) / 10);
        },

        zoomOut() {
            this.zoom = Math.max(0.7, Math.round((this.zoom - 0.1) * 10) / 10);
        },
    };
}

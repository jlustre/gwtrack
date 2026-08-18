export default function dashboardStats(initial = {}) {
    const detailsUrlTemplate = initial.detailsUrlTemplate ?? '';

    const scopeLabels = {
        full_downline: 'Your full downline',
        direct_downline: 'Your direct downline',
        self: 'Your team scope',
        personal: 'Your records',
    };

    return {
        modalOpen: false,
        loading: false,
        error: null,
        modalTitle: '',
        modalScopeLabel: '',
        modalSummary: '',
        display: 'progress',
        members: [],
        items: [],

        detailsUrl(type, context = 'team') {
            const pathTemplate = detailsUrlTemplate.replace('__TYPE__', encodeURIComponent(type));
            const resolved = new URL(pathTemplate, window.location.href);

            resolved.searchParams.set('context', context);

            return `${resolved.pathname}${resolved.search}`;
        },

        async openModal(type, label, context = 'team') {
            this.modalOpen = true;
            this.loading = true;
            this.error = null;
            this.members = [];
            this.items = [];
            this.modalTitle = label;
            this.modalScopeLabel = 'Loading scope...';
            this.modalSummary = '';
            this.display = 'progress';

            try {
                const response = await fetch(this.detailsUrl(type, context), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    const statusText = `${response.status} ${response.statusText}`.trim();
                    throw new Error(`Unable to load stat details (${statusText}).`);
                }

                const payload = await response.json();
                this.modalTitle = payload.title ?? label;
                this.modalScopeLabel = scopeLabels[payload.scope] ?? 'Members in scope';
                this.modalSummary = payload.summary ?? '';
                this.display = payload.display ?? 'progress';
                this.members = payload.members ?? [];
                this.items = payload.items ?? [];
            } catch (error) {
                if (error instanceof SyntaxError) {
                    this.error = 'Unable to load stat details (session may have expired). Refresh the page and try again.';
                } else {
                    this.error = error instanceof Error ? error.message : 'Unable to load stat details right now.';
                }
            } finally {
                this.loading = false;
            }
        },

        closeModal() {
            this.modalOpen = false;
            this.loading = false;
            this.error = null;
            this.members = [];
            this.items = [];
            this.modalSummary = '';
        },
    };
}

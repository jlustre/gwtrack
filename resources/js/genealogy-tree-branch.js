export default function genealogyTreeBranch({ childrenUrl }) {
    return {
        childrenUrl,
        childrenHtml: '',
        expanded: false,
        loaded: false,
        loading: false,
        error: '',

        async toggleBranch() {
            if (this.expanded) {
                this.expanded = false;

                return;
            }

            if (this.loaded) {
                this.expanded = true;

                return;
            }

            await this.loadBranch();
        },

        async loadBranch() {
            if (this.loading) {
                return;
            }

            this.loading = true;
            this.error = '';

            try {
                const response = await fetch(this.childrenUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    throw new Error('Unable to load this branch. Please try again.');
                }

                const data = await response.json();
                if (typeof data.html !== 'string') {
                    throw new Error('The branch response was invalid. Please try again.');
                }

                this.childrenHtml = data.html;
                this.loaded = true;
                this.expanded = true;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'Unable to load this branch. Please try again.';
            } finally {
                this.loading = false;
            }
        },
    };
}

export default function globalSearch(suggestUrl) {
    return {
        query: '',
        open: false,
        loading: false,
        results: [],

        init() {
            const input = this.$refs.searchInput;
            if (input?.value) {
                this.query = input.value;
            }
        },

        onInput() {
            const minLength = 2;

            if (this.query.trim().length < minLength) {
                this.results = [];
                this.open = false;

                return;
            }

            this.loading = true;
            this.open = true;

            window.clearTimeout(this._debounce);
            this._debounce = window.setTimeout(async () => {
                try {
                    const url = new URL(suggestUrl, window.location.origin);
                    url.searchParams.set('q', this.query.trim());

                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        throw new Error('Search suggest failed');
                    }

                    const payload = await response.json();
                    this.results = payload.results ?? [];
                } catch (error) {
                    this.results = [];
                } finally {
                    this.loading = false;
                }
            }, 250);
        },

        closeSuggestions() {
            window.setTimeout(() => {
                this.open = false;
            }, 150);
        },
    };
}

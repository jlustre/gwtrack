const ALLOWED_PHOTO_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
const ALLOWED_PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
const MAX_PHOTO_BYTES = 2 * 1024 * 1024;

function hasAllowedExtension(filename) {
    const extension = filename.split('.').pop()?.toLowerCase();

    return !! extension && ALLOWED_PHOTO_EXTENSIONS.includes(extension);
}

function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(reader.error);
        reader.readAsDataURL(file);
    });
}

export default function profilePhotoUpload(currentUrl = null, initials = 'EF', persistKey = null) {
    return {
        previewUrl: currentUrl,
        currentUrl,
        initials,
        imageFailed: false,
        persistedSelection: false,
        clientError: null,

        init() {
            if (! persistKey) {
                return;
            }

            const stored = sessionStorage.getItem(persistKey);

            if (stored && ! this.currentUrl) {
                this.previewUrl = stored;
                this.currentUrl = stored;
                this.persistedSelection = true;
            } else if (stored) {
                this.persistedSelection = true;
            }
        },

        async onFileSelected(event) {
            const file = event.target.files?.[0];

            if (! file) {
                this.clientError = null;
                this.resetToCurrent();
                return;
            }

            const typeAllowed = ALLOWED_PHOTO_TYPES.includes(file.type) || hasAllowedExtension(file.name);

            if (! typeAllowed || file.size > MAX_PHOTO_BYTES) {
                if (this.$refs.photoInput) {
                    this.$refs.photoInput.value = '';
                }

                this.clientError = ! typeAllowed
                    ? 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).'
                    : 'Profile photos must be 2 MB or smaller.';
                this.resetToCurrent();
                return;
            }

            this.clientError = null;
            this.revokeBlobUrl();
            this.previewUrl = URL.createObjectURL(file);
            this.imageFailed = false;
            this.persistedSelection = true;

            if (persistKey) {
                try {
                    sessionStorage.setItem(persistKey, await readFileAsDataUrl(file));
                } catch {
                    // Ignore storage failures; the server may still stage the upload.
                }
            }
        },

        clearPreview() {
            this.revokeBlobUrl();
            this.resetToCurrent();
            this.persistedSelection = false;
            this.clientError = null;
            this.clearPersistedPhoto();

            if (this.$refs.photoInput) {
                this.$refs.photoInput.value = '';
            }
        },

        clearPersistedPhoto() {
            if (persistKey) {
                sessionStorage.removeItem(persistKey);
            }
        },

        onImageError() {
            if (this.previewUrl?.startsWith('blob:') || this.previewUrl?.startsWith('data:')) {
                return;
            }

            if (persistKey) {
                const stored = sessionStorage.getItem(persistKey);

                if (stored) {
                    this.previewUrl = stored;
                    this.currentUrl = stored;
                    this.imageFailed = false;
                    this.persistedSelection = true;

                    return;
                }
            }

            this.imageFailed = true;
        },

        resetToCurrent() {
            this.previewUrl = this.currentUrl;
            this.imageFailed = false;
        },

        revokeBlobUrl() {
            if (this.previewUrl?.startsWith('blob:')) {
                URL.revokeObjectURL(this.previewUrl);
            }
        },
    };
}

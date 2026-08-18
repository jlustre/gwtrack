@if (config('tinymce.api_key'))
    <script src="https://cdn.tiny.cloud/1/{{ config('tinymce.api_key') }}/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        (function () {
            const editorDefaults = {
                menubar: false,
                statusbar: false,
                plugins: 'lists advlist link autoresize code',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
                min_height: 280,
                max_height: 520,
                autoresize_bottom_margin: 16,
                branding: false,
                promotion: false,
                convert_urls: false,
                relative_urls: false,
                content_style: 'body { font-family: Figtree, sans-serif; font-size: 14px; line-height: 1.6; color: #172033; }',
                setup(editor) {
                    editor.on('change input', () => editor.save());
                },
            };

            const bindFormSync = (textarea) => {
                const form = textarea.closest('form');

                if (form && ! form.dataset.richTextSyncBound) {
                    form.dataset.richTextSyncBound = 'true';
                    form.addEventListener('submit', () => tinymce.triggerSave());
                }
            };

            window.gwInitRichText = function (textareaId) {
                if (typeof tinymce === 'undefined') {
                    return;
                }

                const textarea = document.getElementById(textareaId);

                if (! textarea || tinymce.get(textareaId)) {
                    return;
                }

                tinymce.init({
                    ...editorDefaults,
                    target: textarea,
                });

                bindFormSync(textarea);
            };

            const initVisibleEditors = function () {
                if (typeof tinymce === 'undefined') {
                    return;
                }

                document.querySelectorAll('textarea[data-rich-text]').forEach((textarea) => {
                    if (! textarea.id || tinymce.get(textarea.id)) {
                        return;
                    }

                    if (textarea.offsetParent === null) {
                        return;
                    }

                    tinymce.init({
                        ...editorDefaults,
                        target: textarea,
                    });

                    bindFormSync(textarea);
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initVisibleEditors);
            } else {
                initVisibleEditors();
            }
        })();
    </script>
@endif

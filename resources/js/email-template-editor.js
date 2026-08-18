import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/link';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/code';
import 'tinymce/skins/ui/oxide/skin.min.css';

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

const initEmailTemplateEditor = () => {
    const textareas = document.querySelectorAll('textarea[data-rich-text]');

    if (textareas.length === 0) {
        return;
    }

    textareas.forEach((textarea) => {
        tinymce.init({
            target: textarea,
            ...(tinymceApiKey ? { api_key: tinymceApiKey } : { license_key: 'gpl' }),
            menubar: false,
            statusbar: false,
            plugins: 'lists advlist link autoresize code',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
            min_height: 320,
            max_height: 600,
            autoresize_bottom_margin: 16,
            branding: false,
            promotion: false,
            content_style: 'body { font-family: Figtree, sans-serif; font-size: 14px; line-height: 1.6; color: #172033; }',
        });

        const form = textarea.closest('form');

        if (form && ! form.dataset.richTextSyncBound) {
            form.dataset.richTextSyncBound = 'true';
            form.addEventListener('submit', () => tinymce.triggerSave());
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmailTemplateEditor);
} else {
    initEmailTemplateEditor();
}

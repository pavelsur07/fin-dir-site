(() => {
    let picker = null;
    let field = null;
    document.querySelectorAll('[data-doc-picker]').forEach(link => {
        link.addEventListener('click', event => {
            field = document.querySelector(link.dataset.docPicker === 'related' ? '[data-doc-related]' : '[data-doc-body]');
            picker = window.open(link.href, 'doc-link-picker');
            if (picker) event.preventDefault();
        });
    });
    window.addEventListener('message', event => {
        if (event.origin !== location.origin || event.source !== picker || !field || event.data?.type !== 'doc-link' || typeof event.data.markdown !== 'string') return;
        if (event.data.markdown.length > 2000) return;
        field.focus();
        if (field.matches('[data-doc-related]')) field.value += (field.value.endsWith('\n') || !field.value ? '' : '\n') + event.data.markdown + '\n';
        else field.setRangeText(event.data.markdown, field.selectionStart, field.selectionEnd, 'end');
        field.dispatchEvent(new Event('input', {bubbles: true}));
    });
    document.querySelectorAll('[data-doc-insert]').forEach(button => {
        button.addEventListener('click', () => {
            if (window.opener) window.opener.postMessage({type: 'doc-link', markdown: button.dataset.docInsert}, location.origin);
        });
    });
})();

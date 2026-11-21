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

(() => {
    const panel = document.querySelector('[data-doc-images]');
    const body = document.querySelector('[data-doc-body]');
    if (!panel || !body) return;

    const upload = panel.querySelector('[data-doc-image-upload]');
    const files = upload.querySelector('input[type="file"]');
    const uploadButton = upload.querySelector('button[type="submit"]');
    const statuses = panel.querySelector('[data-doc-image-status]');
    const altForm = panel.querySelector('[data-doc-image-alt]');
    const altInput = altForm.elements.alt;
    let busy = false;
    let selectedImage = null;
    let galleryUrl = panel.dataset.docImages;

    panel.hidden = false;
    files.multiple = true;

    async function refreshGallery(target) {
        const url = new URL(target, location.href);
        url.searchParams.set('embed', '1');
        const response = await fetch(url, {redirect: 'error'});
        if (!response.ok) throw new Error('Не удалось открыть список снимков.');
        const document = new DOMParser().parseFromString(await response.text(), 'text/html');
        const gallery = document.querySelector('[data-doc-image-gallery]');
        if (!gallery) throw new Error('Список снимков недоступен.');
        panel.querySelector('[data-doc-image-gallery]').replaceWith(gallery);
        galleryUrl = target;
    }

    async function send(form, data) {
        const response = await fetch(form.action, {method: 'POST', body: data, headers: {Accept: 'application/json'}, redirect: 'error'});
        if (response.ok) return;
        if (response.headers.get('Content-Type')?.includes('application/json')) {
            const error = await response.json();
            throw new Error(error.message || 'Операция не выполнена.');
        }
        throw new Error('Операция не выполнена. Проверьте подключение и вход в админку.');
    }

    function showError(error) {
        const status = document.createElement('li');
        status.className = 'text-error';
        status.textContent = error.message;
        statuses.append(status);
    }

    async function uploadFiles(selected) {
        if (busy) return;
        busy = true;
        uploadButton.disabled = true;
        statuses.querySelectorAll('[data-doc-image-retry]').forEach(button => { button.disabled = true; });
        try {
            for (const file of selected) {
                const status = document.createElement('li');
                status.textContent = file.name + ' — загружается…';
                statuses.append(status);
                const data = new FormData(upload);
                data.set('image', file);
                try {
                    await send(upload, data);
                    status.textContent = file.name + ' — загружен';
                } catch (error) {
                    status.className = 'text-error';
                    status.textContent = file.name + ' — ' + error.message + ' ';
                    const retry = document.createElement('button');
                    retry.type = 'button';
                    retry.className = 'min-h-control-touch underline';
                    retry.textContent = 'Повторить';
                    retry.dataset.docImageRetry = '';
                    retry.disabled = true;
                    retry.addEventListener('click', () => {
                        if (busy) return;
                        status.remove();
                        uploadFiles([file]);
                    });
                    status.append(retry);
                }
            }
            await refreshGallery(panel.dataset.docImages);
        } catch (error) {
            showError(error);
        } finally {
            busy = false;
            uploadButton.disabled = false;
            statuses.querySelectorAll('[data-doc-image-retry]').forEach(button => { button.disabled = false; });
        }
    }

    upload.addEventListener('submit', event => {
        event.preventDefault();
        if (busy || !files.files.length) return;
        const selected = Array.from(files.files);
        upload.reset();
        uploadFiles(selected);
    });

    panel.addEventListener('click', event => {
        const insert = event.target.closest('[data-doc-image-insert]');
        if (insert) {
            selectedImage = insert.dataset.docImageInsert;
            altForm.hidden = false;
            altInput.value = '';
            altInput.focus();
            return;
        }
        const link = event.target.closest('[data-doc-image-pages] a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        refreshGallery(link.href).catch(showError);
    });

    altForm.addEventListener('submit', event => {
        event.preventDefault();
        const alt = altInput.value.trim();
        if (!selectedImage || !alt) {
            altInput.focus();
            return;
        }
        const escaped = alt.replaceAll('\\', '\\\\').replaceAll('[', '\\[').replaceAll(']', '\\]');
        const start = body.selectionStart;
        const end = body.selectionEnd;
        const before = start > 0 && !body.value.slice(0, start).endsWith('\n\n') ? '\n\n' : '';
        const after = body.value.length > end && !body.value.slice(end).startsWith('\n\n') ? '\n\n' : '';
        body.setRangeText(before + '![' + escaped + '](doc-image:' + selectedImage + ')' + after, start, end, 'end');
        body.dispatchEvent(new Event('input', {bubbles: true}));
        selectedImage = null;
        altForm.hidden = true;
        body.focus();
    });

    panel.addEventListener('submit', async event => {
        const form = event.target.closest('[data-doc-image-delete]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            await send(form, new FormData(form));
            await refreshGallery(galleryUrl).catch(() => refreshGallery(panel.dataset.docImages));
        } catch (error) {
            button.disabled = false;
            showError(error);
        }
    });
})();

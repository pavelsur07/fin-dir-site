(() => {
    const dialog = document.querySelector('[data-doc-image-dialog]');
    if (typeof dialog?.showModal !== 'function') {
        return;
    }

    const image = dialog.querySelector('[data-doc-image-display]');
    const original = dialog.querySelector('[data-doc-image-original]');
    const viewport = dialog.querySelector('[data-doc-image-scroll]');
    const sizeButton = dialog.querySelector('[data-doc-image-size]');
    const closeButton = dialog.querySelector('[data-doc-image-close]');
    const root = document.documentElement;
    const lockClass = 'overflow-hidden';
    let trigger = null;
    let readingPosition = null;
    let ownsScrollLock = false;

    function setOriginalSize(fullSize) {
        sizeButton.setAttribute('aria-pressed', String(fullSize));
        image.classList.toggle('size-full', !fullSize);
        image.classList.toggle('object-scale-down', !fullSize);
        image.classList.toggle('max-w-none', fullSize);
        viewport.tabIndex = fullSize ? 0 : -1;
        viewport.scrollTo(0, 0);
    }

    document.querySelectorAll('a[data-doc-image-preview]').forEach(link => {
        const alt = link.dataset.docImagePreview;
        link.setAttribute('aria-haspopup', 'dialog');
        link.setAttribute('aria-controls', dialog.id);
        link.setAttribute('aria-label', 'Увеличить снимок. ' + alt);

        link.addEventListener('click', event => {
            if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || dialog.open) {
                return;
            }

            trigger = link;
            readingPosition = {left: window.scrollX, top: window.scrollY};
            image.alt = alt;
            image.src = link.href;
            original.href = link.href;
            setOriginalSize(false);
            ownsScrollLock = !root.classList.contains(lockClass);
            root.classList.add(lockClass);
            dialog.showModal();
            event.preventDefault();
            closeButton.focus({preventScroll: true});
        });
    });

    sizeButton.addEventListener('click', () => {
        setOriginalSize(sizeButton.getAttribute('aria-pressed') !== 'true');
    });

    dialog.addEventListener('click', event => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    dialog.addEventListener('close', () => {
        if (ownsScrollLock) {
            root.classList.remove(lockClass);
        }
        if (trigger?.isConnected) {
            trigger.focus({preventScroll: true});
        }
        if (readingPosition) {
            window.scrollTo({...readingPosition, behavior: 'instant'});
        }
        image.removeAttribute('src');
        trigger = null;
        readingPosition = null;
    });
})();

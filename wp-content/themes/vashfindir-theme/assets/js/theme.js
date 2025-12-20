document.addEventListener('DOMContentLoaded', () => {
    const yearEl = document.getElementById('vfd-year');
    if (yearEl) {
        yearEl.textContent = new Date().getFullYear();
    }
});

async function initPreview(root) {
    if (root.dataset.initialized === 'true') {
        return;
    }

    root.dataset.initialized = 'true';

    const { initCanvasPdfReader, loadPdfDocument, isSafariBrowser } = await import('./pdf-reader.js');

    if (isSafariBrowser()) {
        const { loadLegacyPdfDocument } = await import('./pdf-reader-legacy.js');
        initCanvasPdfReader(root, loadLegacyPdfDocument);
        return;
    }

    initCanvasPdfReader(root, loadPdfDocument);
}

window.addEventListener('open-modal', (event) => {
    const modalName = event.detail;
    const root = document.querySelector(`[data-pdf-preview][data-modal-name="${modalName}"]`);

    if (root) {
        initPreview(root);
    }
});

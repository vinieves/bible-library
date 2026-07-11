import { initCanvasPdfReader, loadPdfDocument, isSafariBrowser } from './pdf-reader.js';
import { loadLegacyPdfDocument } from './pdf-reader-legacy.js';

function initPreview(root) {
    if (root.dataset.initialized === 'true') {
        return;
    }

    root.dataset.initialized = 'true';

    if (isSafariBrowser()) {
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

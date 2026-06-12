import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

function initPdfReader(root) {
    const pdfUrl = root.dataset.pdfUrl;
    const saveUrl = root.dataset.saveUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const canvas = root.querySelector('[data-pdf-canvas]');
    const pageCurrent = root.querySelector('[data-page-current]');
    const pageTotal = root.querySelector('[data-page-total]');
    const prevBtn = root.querySelector('[data-page-prev]');
    const nextBtn = root.querySelector('[data-page-next]');
    const loadingEl = root.querySelector('[data-pdf-loading]');
    const errorEl = root.querySelector('[data-pdf-error]');

    let pdfDoc = null;
    let currentPage = Math.max(1, parseInt(root.dataset.initialPage || '1', 10));
    let maxPageRead = Math.max(0, parseInt(root.dataset.maxPageRead || '0', 10));
    let totalPages = parseInt(root.dataset.totalPages || '0', 10) || 0;
    let renderTask = null;
    let saveTimer = null;
    let lastSavedPage = currentPage;

    const showError = () => {
        loadingEl?.classList.add('hidden');
        errorEl?.classList.remove('hidden');
        if (pageTotal) {
            pageTotal.textContent = '—';
        }
    };

    const updateUi = () => {
        if (pageCurrent) {
            pageCurrent.textContent = String(currentPage);
        }
        if (pageTotal) {
            pageTotal.textContent = String(totalPages || '—');
        }
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }
        if (nextBtn) {
            nextBtn.disabled = totalPages === 0 || currentPage >= totalPages;
        }
    };

    const saveProgress = (immediate = false) => {
        if (!saveUrl || !totalPages || currentPage < 1 || lastSavedPage === currentPage) {
            return;
        }

        const persist = async () => {
            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        page: currentPage,
                        total_pages: totalPages,
                    }),
                });

                if (response.ok) {
                    lastSavedPage = currentPage;
                    maxPageRead = Math.max(maxPageRead, currentPage);
                }
            } catch {
                // Silencioso: o progresso será salvo na próxima navegação.
            }
        };

        clearTimeout(saveTimer);

        if (immediate) {
            persist();
            return;
        }

        saveTimer = setTimeout(persist, 400);
    };

    const renderPage = async (pageNumber) => {
        if (!pdfDoc || !canvas) {
            return;
        }

        if (renderTask) {
            renderTask.cancel();
        }

        const page = await pdfDoc.getPage(pageNumber);
        const container = canvas.parentElement;
        const containerWidth = Math.max(container.clientWidth - 16, 280);
        const viewport = page.getViewport({ scale: 1 });
        const scale = Math.min(containerWidth / viewport.width, 2);
        const scaledViewport = page.getViewport({ scale });

        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;

        renderTask = page.render({
            canvas,
            canvasContext: canvas.getContext('2d'),
            viewport: scaledViewport,
        });

        await renderTask.promise;
        renderTask = null;
        loadingEl?.classList.add('hidden');
    };

    const goToPage = async (pageNumber) => {
        if (!pdfDoc || pageNumber < 1 || pageNumber > totalPages) {
            return;
        }

        currentPage = pageNumber;
        maxPageRead = Math.max(maxPageRead, currentPage);
        updateUi();

        try {
            await renderPage(currentPage);
            saveProgress();
        } catch {
            showError();
        }
    };

    prevBtn?.addEventListener('click', () => goToPage(currentPage - 1));
    nextBtn?.addEventListener('click', () => goToPage(currentPage + 1));

    window.addEventListener('resize', () => {
        clearTimeout(window.__pdfReaderResizeTimer);
        window.__pdfReaderResizeTimer = setTimeout(() => {
            if (pdfDoc) {
                renderPage(currentPage).catch(() => {});
            }
        }, 150);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            saveProgress(true);
        }
    });

    window.addEventListener('pagehide', () => {
        saveProgress(true);
    });

    updateUi();

    fetch(pdfUrl, {
        credentials: 'same-origin',
        headers: { Accept: 'application/pdf' },
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return response.arrayBuffer();
        })
        .then((data) => pdfjsLib.getDocument({ data }).promise)
        .then(async (doc) => {
            pdfDoc = doc;
            totalPages = doc.numPages;

            if (totalPages > 0 && currentPage > totalPages) {
                currentPage = totalPages;
            }

            maxPageRead = Math.max(maxPageRead, currentPage);
            updateUi();
            await renderPage(currentPage);
            saveProgress();
        })
        .catch(() => {
            showError();
        });
}

document.querySelectorAll('[data-pdf-reader]').forEach(initPdfReader);

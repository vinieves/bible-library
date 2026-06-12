import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

function prefersNativePdfViewer() {
    const ua = navigator.userAgent || '';

    if (/iPhone|iPad|iPod|Android|Mobile/i.test(ua)) {
        return true;
    }

    return window.matchMedia('(pointer: coarse)').matches && window.innerWidth < 1024;
}

function initNativePdfViewer(root) {
    const pdfUrl = root.dataset.pdfUrl;
    const saveUrl = root.dataset.saveUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const canvasWrap = root.querySelector('[data-pdf-canvas-wrap]');
    const canvas = root.querySelector('[data-pdf-canvas]');
    const fallback = root.querySelector('[data-pdf-fallback]');
    const pageControls = root.querySelector('[data-pdf-page-controls]');
    const loadingEl = root.querySelector('[data-pdf-loading]');
    const errorEl = root.querySelector('[data-pdf-error]');
    const pageTotal = root.querySelector('[data-page-total]');

    canvasWrap?.classList.add('hidden');
    canvas?.classList.add('hidden');
    pageControls?.classList.add('hidden');
    loadingEl?.classList.remove('hidden');
    errorEl?.classList.add('hidden');

    if (!fallback || !pdfUrl) {
        showReaderError(root);
        return;
    }

    const markOpened = async () => {
        if (!saveUrl) {
            return;
        }

        const totalPages = Math.max(1, parseInt(root.dataset.totalPages || '1', 10));

        try {
            await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    page: 1,
                    total_pages: totalPages,
                }),
            });
        } catch {
            // Progresso opcional no modo nativo.
        }
    };

    fallback.addEventListener('load', () => {
        loadingEl?.classList.add('hidden');
        if (pageTotal) {
            pageTotal.textContent = 'PDF';
        }
        markOpened();
    });

    fallback.addEventListener('error', () => {
        showReaderError(root);
    });

    fallback.src = pdfUrl;
    fallback.classList.remove('hidden');

    // iOS nem sempre dispara "load" em iframes de PDF.
    setTimeout(() => {
        loadingEl?.classList.add('hidden');
    }, 1200);
}

function showReaderError(root) {
    const loadingEl = root.querySelector('[data-pdf-loading]');
    const errorEl = root.querySelector('[data-pdf-error]');
    const pageTotal = root.querySelector('[data-page-total]');

    loadingEl?.classList.add('hidden');
    errorEl?.classList.remove('hidden');

    if (pageTotal) {
        pageTotal.textContent = '—';
    }
}

function initPdfJsReader(root) {
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
                        Accept: 'application/json',
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
        const containerWidth = Math.max(container?.clientWidth ? container.clientWidth - 16 : 280, 280);
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
            showReaderError(root);
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

    pdfjsLib.getDocument({
        url: pdfUrl,
        withCredentials: true,
        rangeChunkSize: 65536,
    }).promise
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
            initNativePdfViewer(root);
        });
}

function initPdfReader(root) {
    if (prefersNativePdfViewer()) {
        initNativePdfViewer(root);
        return;
    }

    initPdfJsReader(root);
}

document.querySelectorAll('[data-pdf-reader]').forEach(initPdfReader);

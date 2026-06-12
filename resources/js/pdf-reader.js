import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

function isMobileReader() {
    return window.matchMedia('(max-width: 768px)').matches
        || window.matchMedia('(pointer: coarse)').matches;
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

async function loadPdfDocument(pdfUrl) {
    const baseOptions = {
        url: pdfUrl,
        withCredentials: true,
        rangeChunkSize: 65536,
        disableFontFace: isMobileReader(),
        useSystemFonts: true,
        isEvalSupported: false,
    };

    try {
        return await pdfjsLib.getDocument(baseOptions).promise;
    } catch {
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            `https://cdn.jsdelivr.net/npm/pdfjs-dist@${pdfjsLib.version}/build/pdf.worker.min.mjs`;

        return pdfjsLib.getDocument(baseOptions).promise;
    }
}

function initPdfReader(root) {
    const pdfUrl = root.dataset.pdfUrl;
    const saveUrl = root.dataset.saveUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const canvasWrap = root.querySelector('[data-pdf-canvas-wrap]');
    const canvas = root.querySelector('[data-pdf-canvas]');
    const pageCurrent = root.querySelector('[data-page-current]');
    const pageTotal = root.querySelector('[data-page-total]');
    const prevBtn = root.querySelector('[data-page-prev]');
    const nextBtn = root.querySelector('[data-page-next]');
    const loadingEl = root.querySelector('[data-pdf-loading]');
    const errorEl = root.querySelector('[data-pdf-error]');
    const mobile = isMobileReader();

    let pdfDoc = null;
    let currentPage = Math.max(1, parseInt(root.dataset.initialPage || '1', 10));
    let maxPageRead = Math.max(0, parseInt(root.dataset.maxPageRead || '0', 10));
    let totalPages = parseInt(root.dataset.totalPages || '0', 10) || 0;
    let renderTask = null;
    let saveTimer = null;
    let lastSavedPage = currentPage;
    let touchStartX = 0;
    let touchStartY = 0;

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

    const getAvailableWidth = () => {
        const wrapWidth = canvasWrap?.clientWidth || 0;
        const rootWidth = root.clientWidth || 0;

        return Math.max(wrapWidth, rootWidth, window.innerWidth) - (mobile ? 0 : 16);
    };

    const renderPage = async (pageNumber) => {
        if (!pdfDoc || !canvas) {
            return;
        }

        if (renderTask) {
            renderTask.cancel();
        }

        const page = await pdfDoc.getPage(pageNumber);
        const availableWidth = Math.max(getAvailableWidth(), 280);
        const viewport = page.getViewport({ scale: 1 });
        const maxScale = mobile ? 2 : 2.5;
        const scale = Math.min(availableWidth / viewport.width, maxScale);
        const scaledViewport = page.getViewport({ scale });
        const outputScale = mobile ? 1 : Math.min(window.devicePixelRatio || 1, 2);

        canvas.width = Math.floor(scaledViewport.width * outputScale);
        canvas.height = Math.floor(scaledViewport.height * outputScale);
        canvas.style.width = '100%';
        canvas.style.height = 'auto';
        canvas.style.display = 'block';

        const context = canvas.getContext('2d');
        context.setTransform(outputScale, 0, 0, outputScale, 0, 0);

        renderTask = page.render({
            canvas,
            canvasContext: context,
            viewport: scaledViewport,
        });

        await renderTask.promise;
        renderTask = null;
        loadingEl?.classList.add('hidden');
        canvasWrap?.scrollTo({ top: 0, behavior: 'smooth' });
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

    canvasWrap?.addEventListener('touchstart', (event) => {
        const touch = event.changedTouches[0];
        touchStartX = touch.screenX;
        touchStartY = touch.screenY;
    }, { passive: true });

    canvasWrap?.addEventListener('touchend', (event) => {
        const touch = event.changedTouches[0];
        const deltaX = touch.screenX - touchStartX;
        const deltaY = touch.screenY - touchStartY;

        if (Math.abs(deltaX) < 48 || Math.abs(deltaX) < Math.abs(deltaY)) {
            return;
        }

        if (deltaX < 0) {
            goToPage(currentPage + 1);
        } else {
            goToPage(currentPage - 1);
        }
    }, { passive: true });

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

    loadPdfDocument(pdfUrl)
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
            showReaderError(root);
        });
}

document.querySelectorAll('[data-pdf-reader]').forEach(initPdfReader);

import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

function isSafariBrowser() {
    const ua = navigator.userAgent || '';

    if (/CriOS|FxiOS|EdgiOS|OPiOS|mercury/i.test(ua)) {
        return false;
    }

    const isIos = /iPad|iPhone|iPod/.test(ua)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    const isSafari = /Safari/i.test(ua) && !/Chrome|Chromium|Android/i.test(ua);

    return isIos || isSafari;
}

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

function createSharedReaderState(root) {
    const saveUrl = root.dataset.saveUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const pageCurrent = root.querySelector('[data-page-current]');
    const pageTotal = root.querySelector('[data-page-total]');
    const prevBtn = root.querySelector('[data-page-prev]');
    const nextBtn = root.querySelector('[data-page-next]');
    const loadingEl = root.querySelector('[data-pdf-loading]');

    let currentPage = Math.max(1, parseInt(root.dataset.initialPage || '1', 10));
    let totalPages = Math.max(0, parseInt(root.dataset.totalPages || '0', 10));
    let lastSavedPage = 0;
    let saveTimer = null;

    const updateUi = () => {
        if (pageCurrent) {
            pageCurrent.textContent = String(currentPage);
        }
        if (pageTotal) {
            pageTotal.textContent = totalPages > 0 ? String(totalPages) : '—';
        }
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }
        if (nextBtn) {
            nextBtn.disabled = totalPages > 0 && currentPage >= totalPages;
        }
    };

    const saveProgress = (immediate = false) => {
        if (!saveUrl || currentPage < 1 || lastSavedPage === currentPage) {
            return;
        }

        const knownTotal = totalPages > 0 ? totalPages : Math.max(currentPage, 1);

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
                        total_pages: knownTotal,
                    }),
                });

                if (response.ok) {
                    lastSavedPage = currentPage;
                    const data = await response.json();
                    if (data.total_pages && ! totalPages) {
                        totalPages = data.total_pages;
                        updateUi();
                    }
                }
            } catch {
                // Silencioso.
            }
        };

        clearTimeout(saveTimer);

        if (immediate) {
            persist();
            return;
        }

        saveTimer = setTimeout(persist, 400);
    };

    const bindNavigation = (goToPage) => {
        prevBtn?.addEventListener('click', () => goToPage(currentPage - 1));
        nextBtn?.addEventListener('click', () => goToPage(currentPage + 1));

        let touchStartX = 0;
        let touchStartY = 0;
        const touchArea = root.querySelector('[data-pdf-touch-area]');

        touchArea?.addEventListener('touchstart', (event) => {
            const touch = event.changedTouches[0];
            touchStartX = touch.screenX;
            touchStartY = touch.screenY;
        }, { passive: true });

        touchArea?.addEventListener('touchend', (event) => {
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
    };

    const hideLoading = () => {
        loadingEl?.classList.add('hidden');
    };

    updateUi();

    return {
        get currentPage() {
            return currentPage;
        },
        set currentPage(value) {
            currentPage = value;
        },
        get totalPages() {
            return totalPages;
        },
        set totalPages(value) {
            totalPages = value;
        },
        updateUi,
        saveProgress,
        bindNavigation,
        hideLoading,
    };
}

function buildSafariPdfUrl(baseUrl, page) {
    const separator = baseUrl.includes('#') ? '&' : '#';

    return `${baseUrl}${separator}page=${page}&view=FitH&zoom=page-width`;
}

function initSafariPdfReader(root) {
    const pdfUrl = root.dataset.pdfUrl;
    const canvasWrap = root.querySelector('[data-pdf-canvas-wrap]');
    const embed = root.querySelector('[data-pdf-embed]');
    const state = createSharedReaderState(root);

    canvasWrap?.classList.add('hidden');
    embed.classList.remove('hidden');

    if (!embed || !pdfUrl) {
        showReaderError(root);
        return;
    }

    const goToPage = (pageNumber) => {
        if (pageNumber < 1) {
            return;
        }

        if (state.totalPages > 0 && pageNumber > state.totalPages) {
            return;
        }

        state.currentPage = pageNumber;
        embed.src = buildSafariPdfUrl(pdfUrl, pageNumber);
        state.updateUi();
        state.saveProgress();
        state.hideLoading();
    };

    state.bindNavigation(goToPage);

    embed.addEventListener('load', () => {
        state.hideLoading();
    });

    embed.addEventListener('error', () => {
        showReaderError(root);
    });

    goToPage(state.currentPage);

    setTimeout(() => {
        state.hideLoading();
    }, 1500);
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

function initPdfJsReader(root) {
    const pdfUrl = root.dataset.pdfUrl;
    const canvasWrap = root.querySelector('[data-pdf-canvas-wrap]');
    const embed = root.querySelector('[data-pdf-embed]');
    const canvas = root.querySelector('[data-pdf-canvas]');
    const mobile = isMobileReader();
    const state = createSharedReaderState(root);

    embed?.classList.add('hidden');

    let pdfDoc = null;
    let renderTask = null;

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
        state.hideLoading();
        canvasWrap?.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const goToPage = async (pageNumber) => {
        if (!pdfDoc || pageNumber < 1 || pageNumber > state.totalPages) {
            return;
        }

        state.currentPage = pageNumber;
        state.updateUi();

        try {
            await renderPage(state.currentPage);
            state.saveProgress();
        } catch {
            showReaderError(root);
        }
    };

    state.bindNavigation(goToPage);

    window.addEventListener('resize', () => {
        clearTimeout(window.__pdfReaderResizeTimer);
        window.__pdfReaderResizeTimer = setTimeout(() => {
            if (pdfDoc) {
                renderPage(state.currentPage).catch(() => {});
            }
        }, 150);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            state.saveProgress(true);
        }
    });

    window.addEventListener('pagehide', () => {
        state.saveProgress(true);
    });

    loadPdfDocument(pdfUrl)
        .then(async (doc) => {
            pdfDoc = doc;
            state.totalPages = doc.numPages;

            if (state.totalPages > 0 && state.currentPage > state.totalPages) {
                state.currentPage = state.totalPages;
            }

            state.updateUi();
            await renderPage(state.currentPage);
            state.saveProgress();
        })
        .catch(() => {
            initSafariPdfReader(root);
        });
}

function initPdfReader(root) {
    if (isSafariBrowser()) {
        initSafariPdfReader(root);
        return;
    }

    initPdfJsReader(root);
}

document.querySelectorAll('[data-pdf-reader]').forEach(initPdfReader);

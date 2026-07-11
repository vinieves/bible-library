import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { loadLegacyPdfDocument } from './pdf-reader-legacy.js';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

export function isSafariBrowser() {
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
        hideLoading,
    };
}

export async function loadPdfDocument(pdfUrl) {
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

export function initCanvasPdfReader(root, loadDocument) {
    const pdfUrl = root.dataset.pdfUrl;
    const canvasWrap = root.querySelector('[data-pdf-canvas-wrap]');
    const embed = root.querySelector('[data-pdf-embed]');
    const canvas = root.querySelector('[data-pdf-canvas]');
    const touchArea = root.querySelector('[data-pdf-touch-area]') || canvasWrap;
    const mobile = isMobileReader();
    const dpr = Math.min(window.devicePixelRatio || 1, 3);
    const maxScale = mobile ? 2 : 3;
    const state = createSharedReaderState(root);

    embed?.classList.add('hidden');
    canvasWrap?.classList.remove('hidden');

    if (!canvas) {
        return;
    }

    let pdfDoc = null;
    let renderTask = null;
    let busy = false;

    // Cache leve de páginas já renderizadas (canvas fora da tela), pra trocar de
    // página sem demora — sem nenhuma animação/arraste, só desenha na hora.
    const cache = new Map(); // pageNumber -> { canvas, width, height }
    const MAX_CACHED = 4;

    const getAvailableWidth = () => {
        const wrapWidth = canvasWrap?.clientWidth || 0;
        const rootWidth = root.clientWidth || 0;

        return Math.max(wrapWidth, rootWidth, window.innerWidth);
    };

    const renderPageOffscreen = async (pageNumber) => {
        const page = await pdfDoc.getPage(pageNumber);
        const availableWidth = Math.max(getAvailableWidth(), 280);
        const viewport = page.getViewport({ scale: 1 });
        const cssScale = Math.min(availableWidth / viewport.width, maxScale);
        const scaledViewport = page.getViewport({ scale: cssScale * dpr });

        const offscreen = document.createElement('canvas');
        offscreen.width = Math.floor(scaledViewport.width);
        offscreen.height = Math.floor(scaledViewport.height);

        const task = page.render({
            canvas: offscreen,
            canvasContext: offscreen.getContext('2d'),
            viewport: scaledViewport,
        });

        await task.promise;

        return offscreen;
    };

    const cachePage = (pageNumber, offscreen) => {
        cache.set(pageNumber, offscreen);

        if (cache.size > MAX_CACHED) {
            const oldestKey = cache.keys().next().value;
            if (oldestKey !== undefined) {
                cache.delete(oldestKey);
            }
        }
    };

    // ── Zoom (pinça) e pan, restritos à página do PDF — nunca afeta o app/viewport. ──
    const MIN_ZOOM = 1;
    const MAX_ZOOM = 4;
    let zoomScale = 1;
    let panX = 0;
    let panY = 0;

    const clampNum = (value, min, max) => Math.min(max, Math.max(min, value));

    const clampPan = () => {
        const wrapW = canvasWrap?.clientWidth || 0;
        const wrapH = canvasWrap?.clientHeight || 0;
        const visW = canvas.clientWidth * zoomScale;
        const visH = canvas.clientHeight * zoomScale;

        panX = clampNum(panX, Math.min(0, wrapW - visW), 0);
        panY = clampNum(panY, Math.min(0, wrapH - visH), 0);
    };

    const applyZoomTransform = () => {
        canvas.style.transformOrigin = '0 0';
        canvas.style.transform = zoomScale === 1 ? '' : `translate(${panX}px, ${panY}px) scale(${zoomScale})`;

        if (canvasWrap) {
            canvasWrap.style.overflowY = zoomScale === 1 ? 'auto' : 'hidden';
            canvasWrap.style.touchAction = zoomScale === 1 ? 'pan-y' : 'none';
        }
    };

    const resetZoom = () => {
        zoomScale = 1;
        panX = 0;
        panY = 0;
        applyZoomTransform();
    };

    const drawToVisibleCanvas = (offscreen) => {
        canvas.width = offscreen.width;
        canvas.height = offscreen.height;
        canvas.style.width = '100%';
        canvas.style.height = 'auto';
        canvas.style.display = 'block';
        canvas.getContext('2d').drawImage(offscreen, 0, 0);
        resetZoom();
    };

    const renderCurrentPage = async () => {
        if (!pdfDoc) {
            return;
        }

        if (renderTask) {
            try {
                renderTask.cancel?.();
            } catch {
                // Ignorado.
            }
        }

        const pageNumber = state.currentPage;
        const cached = cache.get(pageNumber);

        if (cached) {
            drawToVisibleCanvas(cached);
            state.hideLoading();
            canvasWrap?.scrollTo({ top: 0, behavior: 'auto' });
        } else {
            const promise = renderPageOffscreen(pageNumber);
            renderTask = promise;
            const offscreen = await promise;
            cachePage(pageNumber, offscreen);
            drawToVisibleCanvas(offscreen);
            state.hideLoading();
            canvasWrap?.scrollTo({ top: 0, behavior: 'auto' });
        }

        // Pré-renderiza vizinhas em segundo plano, sem bloquear nem animar nada.
        [pageNumber - 1, pageNumber + 1].forEach((neighbor) => {
            if (neighbor < 1 || (state.totalPages > 0 && neighbor > state.totalPages)) {
                return;
            }
            if (cache.has(neighbor)) {
                return;
            }
            renderPageOffscreen(neighbor).then((offscreen) => cachePage(neighbor, offscreen)).catch(() => {});
        });
    };

    const goToPage = async (targetPage) => {
        if (busy || !pdfDoc || targetPage === state.currentPage) {
            return;
        }

        if (targetPage < 1 || (state.totalPages > 0 && targetPage > state.totalPages)) {
            return;
        }

        busy = true;
        state.currentPage = targetPage;
        state.updateUi();

        try {
            await renderCurrentPage();
            state.saveProgress();
        } catch {
            showReaderError(root);
        } finally {
            busy = false;
        }
    };

    const touchDistance = (t1, t2) => Math.hypot(t1.clientX - t2.clientX, t1.clientY - t2.clientY);

    let touchStartX = 0;
    let touchStartY = 0;
    let pinchStartDistance = 0;
    let pinchStartScale = 1;
    let panning = false;
    let panStartX = 0;
    let panStartY = 0;
    let panOriginX = 0;
    let panOriginY = 0;

    touchArea?.addEventListener('touchstart', (event) => {
        if (event.touches.length === 2) {
            pinchStartDistance = touchDistance(event.touches[0], event.touches[1]);
            pinchStartScale = zoomScale;
            panning = false;
            return;
        }

        const touch = event.changedTouches[0];
        touchStartX = touch.screenX;
        touchStartY = touch.screenY;

        if (zoomScale > 1.01) {
            panning = true;
            panStartX = touch.clientX;
            panStartY = touch.clientY;
            panOriginX = panX;
            panOriginY = panY;
        }
    }, { passive: true });

    touchArea?.addEventListener('touchmove', (event) => {
        if (event.touches.length === 2) {
            event.preventDefault();

            if (pinchStartDistance > 0) {
                const distance = touchDistance(event.touches[0], event.touches[1]);
                zoomScale = clampNum(pinchStartScale * (distance / pinchStartDistance), MIN_ZOOM, MAX_ZOOM);
                clampPan();
                applyZoomTransform();
            }
            return;
        }

        if (panning) {
            event.preventDefault();
            const touch = event.touches[0];
            panX = panOriginX + (touch.clientX - panStartX);
            panY = panOriginY + (touch.clientY - panStartY);
            clampPan();
            applyZoomTransform();
        }
    }, { passive: false });

    touchArea?.addEventListener('touchend', (event) => {
        panning = false;

        if (zoomScale <= 1.01) {
            resetZoom();
        }

        // Ainda tem dedo na tela (pinça terminando) ou a página está com zoom: não troca de página.
        if (event.touches.length > 0 || zoomScale > 1.01) {
            return;
        }

        const touch = event.changedTouches[0];
        const deltaX = touch.screenX - touchStartX;
        const deltaY = touch.screenY - touchStartY;

        if (Math.abs(deltaX) < 40 || Math.abs(deltaX) < Math.abs(deltaY)) {
            return;
        }

        if (deltaX < 0) {
            goToPage(state.currentPage + 1);
        } else {
            goToPage(state.currentPage - 1);
        }
    }, { passive: true });

    root.querySelector('[data-page-prev]')?.addEventListener('click', () => {
        goToPage(state.currentPage - 1);
    });
    root.querySelector('[data-page-next]')?.addEventListener('click', () => {
        goToPage(state.currentPage + 1);
    });

    window.addEventListener('resize', () => {
        clearTimeout(window.__pdfReaderResizeTimer);
        window.__pdfReaderResizeTimer = setTimeout(() => {
            if (pdfDoc && !busy) {
                // O tamanho mudou: o que já tinha em cache pode estar na resolução errada agora.
                cache.clear();
                renderCurrentPage().catch(() => {});
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

    loadDocument(pdfUrl)
        .then(async (doc) => {
            pdfDoc = doc;
            const maxPages = parseInt(root.dataset.maxPages || '0', 10);
            state.totalPages = maxPages > 0 ? Math.min(doc.numPages, maxPages) : doc.numPages;

            if (state.totalPages > 0 && state.currentPage > state.totalPages) {
                state.currentPage = state.totalPages;
            }

            state.updateUi();
            await renderCurrentPage();
            state.saveProgress();
        })
        .catch(() => {
            showReaderError(root);
        });
}

function initPdfReader(root) {
    if (isSafariBrowser()) {
        initCanvasPdfReader(root, loadLegacyPdfDocument);
        return;
    }

    initCanvasPdfReader(root, loadPdfDocument);
}

document.querySelectorAll('[data-pdf-reader]').forEach(initPdfReader);

import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { loadLegacyPdfDocument } from './pdf-reader-legacy.js';

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

const SLIDE_TRANSITION = 'transform 220ms ease-out';

function initCanvasPdfReader(root, loadDocument) {
    const pdfUrl = root.dataset.pdfUrl;
    const canvasWrap = root.querySelector('[data-pdf-canvas-wrap]');
    const embed = root.querySelector('[data-pdf-embed]');
    const originalCanvas = root.querySelector('[data-pdf-canvas]');
    const touchArea = root.querySelector('[data-pdf-touch-area]') || canvasWrap;
    const mobile = isMobileReader();
    const dpr = Math.min(window.devicePixelRatio || 1, 3);
    const maxScale = mobile ? 2 : 3;
    const state = createSharedReaderState(root);

    embed?.classList.add('hidden');
    canvasWrap?.classList.remove('hidden');

    if (!canvasWrap) {
        return;
    }

    canvasWrap.style.overflowX = 'hidden';

    // Trilho de 3 posições: [anterior][atual][seguinte] — permite arrastar como um carrossel.
    originalCanvas?.remove();
    const track = document.createElement('div');
    track.style.display = 'flex';
    track.style.width = '300%';
    track.style.willChange = 'transform';
    track.style.transform = 'translateX(-33.3333%)';

    const slotEls = [0, 1, 2].map(() => {
        const slot = document.createElement('div');
        slot.style.flex = '0 0 33.3333%';
        slot.style.display = 'flex';
        slot.style.justifyContent = 'center';
        track.appendChild(slot);
        return slot;
    });

    canvasWrap.appendChild(track);

    // Cada "pane" guarda seu próprio <canvas> e qual página ele tem desenhada agora.
    // Ao trocar de página, em vez de redesenhar as 3 do zero, a gente RODA os panes
    // entre as posições — a vizinha que já estava pronta (ex.: a "seguinte" virando
    // a "atual") só é movida de lugar, sem novo render. Só sobra 1 página de fato
    // nova pra renderizar em segundo plano (a que ficou mais distante).
    const panes = [0, 1, 2].map(() => {
        const canvas = document.createElement('canvas');
        canvas.style.width = '100%';
        canvas.style.height = 'auto';
        canvas.style.display = 'block';
        return { canvas, page: null };
    });

    panes.forEach((pane, index) => slotEls[index].appendChild(pane.canvas));

    let pdfDoc = null;
    let animating = false;
    const renderTasks = new Map();

    const getAvailableWidth = () => {
        const wrapWidth = canvasWrap?.clientWidth || 0;
        const rootWidth = root.clientWidth || 0;

        return Math.max(wrapWidth, rootWidth, window.innerWidth);
    };

    const renderPageToCanvas = async (pageNumber, canvas) => {
        if (renderTasks.has(canvas)) {
            try {
                renderTasks.get(canvas).cancel();
            } catch {
                // Ignorado: a renderização anterior já tinha terminado/cancelado.
            }
            renderTasks.delete(canvas);
        }

        const page = await pdfDoc.getPage(pageNumber);
        const availableWidth = Math.max(getAvailableWidth(), 280);
        const viewport = page.getViewport({ scale: 1 });
        const cssScale = Math.min(availableWidth / viewport.width, maxScale);
        const scaledViewport = page.getViewport({ scale: cssScale * dpr });

        canvas.width = Math.floor(scaledViewport.width);
        canvas.height = Math.floor(scaledViewport.height);

        const context = canvas.getContext('2d');
        context.setTransform(1, 0, 0, 1, 0, 0);

        const task = page.render({
            canvas,
            canvasContext: context,
            viewport: scaledViewport,
        });
        renderTasks.set(canvas, task);

        try {
            await task.promise;
        } finally {
            renderTasks.delete(canvas);
        }
    };

    // Só renderiza de fato se o pane ainda não tiver essa página desenhada.
    const ensurePane = async (pane, pageNumber) => {
        if (pageNumber < 1 || (state.totalPages > 0 && pageNumber > state.totalPages)) {
            pane.page = null;
            pane.canvas.width = 0;
            pane.canvas.height = 0;
            return;
        }

        if (pane.page === pageNumber) {
            return;
        }

        pane.page = pageNumber;
        await renderPageToCanvas(pageNumber, pane.canvas);
    };

    const refreshSlots = async () => {
        const current = state.currentPage;

        await ensurePane(panes[1], current);
        state.hideLoading();
        canvasWrap?.scrollTo({ top: 0, behavior: 'auto' });

        ensurePane(panes[0], current - 1).catch(() => {});
        ensurePane(panes[2], current + 1).catch(() => {});
    };

    // Reatribui qual pane ocupa qual posição visual sem tocar no canvas/bitmap já pronto.
    const rotatePanes = (direction) => {
        if (direction === -1) {
            // Avançou: [prev,cur,next] -> [cur,next,prev]
            panes.push(panes.shift());
        } else {
            // Voltou: [prev,cur,next] -> [next,prev,cur]
            panes.unshift(panes.pop());
        }

        panes.forEach((pane, index) => slotEls[index].appendChild(pane.canvas));
    };

    const setTrackTransform = (offsetPx, withTransition) => {
        track.style.transition = withTransition ? SLIDE_TRANSITION : 'none';
        track.style.transform = `translateX(calc(-33.3333% + ${offsetPx}px))`;
    };

    const waitForTransition = () => new Promise((resolve) => {
        const onEnd = () => {
            track.removeEventListener('transitionend', onEnd);
            resolve();
        };
        track.addEventListener('transitionend', onEnd, { once: true });
    });

    const goToPageAnimated = async (targetPage) => {
        if (animating || !pdfDoc || targetPage === state.currentPage) {
            return;
        }

        if (targetPage < 1 || (state.totalPages > 0 && targetPage > state.totalPages)) {
            return;
        }

        const direction = targetPage > state.currentPage ? -1 : 1; // -1 = avança (desliza p/ esquerda)
        const containerWidth = getAvailableWidth();

        animating = true;
        setTrackTransform(direction * -containerWidth, true);

        try {
            await waitForTransition();

            const isAdjacent = Math.abs(targetPage - state.currentPage) === 1;
            state.currentPage = targetPage;
            state.updateUi();
            setTrackTransform(0, false);

            if (isAdjacent) {
                rotatePanes(direction);
            }

            await refreshSlots();
            state.saveProgress();
        } catch {
            showReaderError(root);
        } finally {
            animating = false;
        }
    };

    let dragging = false;
    let startX = 0;
    let startY = 0;
    let lastDeltaX = 0;
    let axisLocked = null;

    touchArea?.addEventListener('touchstart', (event) => {
        if (animating) {
            return;
        }

        const touch = event.changedTouches[0];
        dragging = true;
        startX = touch.screenX;
        startY = touch.screenY;
        lastDeltaX = 0;
        axisLocked = null;
        track.style.transition = 'none';
    }, { passive: true });

    touchArea?.addEventListener('touchmove', (event) => {
        if (!dragging || animating) {
            return;
        }

        const touch = event.changedTouches[0];
        const deltaX = touch.screenX - startX;
        const deltaY = touch.screenY - startY;

        if (axisLocked === null) {
            if (Math.abs(deltaX) < 8 && Math.abs(deltaY) < 8) {
                return;
            }
            axisLocked = Math.abs(deltaX) > Math.abs(deltaY) ? 'x' : 'y';
        }

        if (axisLocked === 'y') {
            return;
        }

        event.preventDefault();

        const containerWidth = getAvailableWidth();
        lastDeltaX = Math.max(-containerWidth, Math.min(containerWidth, deltaX));
        setTrackTransform(lastDeltaX, false);
    }, { passive: false });

    touchArea?.addEventListener('touchend', () => {
        if (!dragging) {
            return;
        }

        dragging = false;

        if (axisLocked !== 'x') {
            return;
        }

        const containerWidth = getAvailableWidth();
        const threshold = containerWidth * 0.25;

        if (lastDeltaX <= -threshold) {
            goToPageAnimated(state.currentPage + 1);
        } else if (lastDeltaX >= threshold) {
            goToPageAnimated(state.currentPage - 1);
        } else {
            setTrackTransform(0, true);
        }
    }, { passive: true });

    root.querySelector('[data-page-prev]')?.addEventListener('click', () => {
        goToPageAnimated(state.currentPage - 1);
    });
    root.querySelector('[data-page-next]')?.addEventListener('click', () => {
        goToPageAnimated(state.currentPage + 1);
    });

    window.addEventListener('resize', () => {
        clearTimeout(window.__pdfReaderResizeTimer);
        window.__pdfReaderResizeTimer = setTimeout(() => {
            if (pdfDoc && !animating) {
                // O tamanho mudou: o que já tinha renderizado pode estar na resolução errada agora.
                panes.forEach((pane) => { pane.page = null; });
                refreshSlots().catch(() => {});
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
            state.totalPages = doc.numPages;

            if (state.totalPages > 0 && state.currentPage > state.totalPages) {
                state.currentPage = state.totalPages;
            }

            state.updateUi();
            await refreshSlots();
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

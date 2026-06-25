function isIos() {
    const ua = window.navigator.userAgent;
    const isAppleMobile = /iphone|ipad|ipod/i.test(ua);
    const isModerniPad = window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1;

    return isAppleMobile || isModerniPad;
}

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function setupPwaInstall() {
    const buttons = document.querySelectorAll('[data-pwa-install]');
    const modal = document.querySelector('[data-pwa-install-modal]');

    if (buttons.length === 0) {
        return;
    }

    const closeButtons = modal ? modal.querySelectorAll('[data-pwa-install-close]') : [];
    const iosInstructions = modal ? modal.querySelector('[data-pwa-modal-ios]') : null;
    const genericInstructions = modal ? modal.querySelector('[data-pwa-modal-generic]') : null;

    let deferredPrompt = null;

    // Visibility is toggled by swapping the `hidden`/`flex` classes themselves,
    // never by the native `hidden` attribute: Tailwind's [hidden] preflight rule
    // uses :where() (zero specificity), so a static `flex` class would always
    // win and "hiding" the element again would have no visible effect.
    const hideButtons = () => {
        buttons.forEach((button) => {
            button.classList.remove('flex');
            button.classList.add('hidden');
        });
    };

    const showButtons = () => {
        buttons.forEach((button) => {
            button.classList.remove('hidden');
            button.classList.add('flex');
        });
    };

    const openModal = () => {
        if (!modal) {
            return;
        }

        if (iosInstructions && genericInstructions) {
            iosInstructions.hidden = !isIos();
            genericInstructions.hidden = isIos();
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    hideButtons();

    if (!isStandalone() && isIos()) {
        showButtons();
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        showButtons();
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        hideButtons();
        closeModal();
    });

    window.matchMedia('(display-mode: standalone)').addEventListener('change', (event) => {
        if (event.matches) {
            hideButtons();
        }
    });

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (deferredPrompt) {
                try {
                    deferredPrompt.prompt();
                    await deferredPrompt.userChoice;
                } catch (error) {
                    console.error('Error al mostrar el prompt de instalación:', error);
                }

                deferredPrompt = null;

                return;
            }

            openModal();
        });
    });

    closeButtons.forEach((closeButton) => {
        closeButton.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', async () => {
        try {
            await navigator.serviceWorker.register('/sw.js');
        } catch (error) {
            console.error('Erro ao registrar o service worker:', error);
        }
    });
}

document.addEventListener('DOMContentLoaded', setupPwaInstall);
registerServiceWorker();

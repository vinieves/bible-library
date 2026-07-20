// resources/js/app-onboarding.js
//
// Pop-up de onboarding mostrado a cada abertura do app (home /mi-biblioteca)
// enquanto os 2 passos não estiverem concluídos:
//   1. Agregar a pantalla de inicio (instalação PWA)
//   2. Activar notificaciones (Notification.requestPermission — só permissão)
//
// Módulo autocontido: NÃO depende de pwa-install.js e não o altera. Coexiste com
// ele (ambos podem ouvir `beforeinstallprompt`).

const A2HS_DONE_KEY = 'onboarding_a2hs_done';

function isIos() {
    const ua = window.navigator.userAgent;
    const isAppleMobile = /iphone|ipad|ipod/i.test(ua);
    const isModerniPad = window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1;

    return isAppleMobile || isModerniPad;
}

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function supportsNotifications() {
    return 'Notification' in window;
}

function computeA2hsDone() {
    return isStandalone() || localStorage.getItem(A2HS_DONE_KEY) === '1';
}

function computeNotifDone() {
    return supportsNotifications() && Notification.permission === 'granted';
}

function setupAppOnboarding() {
    const modal = document.querySelector('[data-onboarding-modal]');

    if (!modal) {
        return;
    }

    const closeButton = modal.querySelector('[data-onboarding-close]');
    const a2hsButton = modal.querySelector('[data-onboarding-a2hs]');
    const a2hsCheck = modal.querySelector('[data-onboarding-a2hs-check]');
    const iosInstructions = modal.querySelector('[data-onboarding-ios]');
    const genericInstructions = modal.querySelector('[data-onboarding-generic]');
    const notifyButton = modal.querySelector('[data-onboarding-notify]');
    const notifyCheck = modal.querySelector('[data-onboarding-notify-check]');
    const notifySub = modal.querySelector('[data-onboarding-notify-sub]');

    let deferredPrompt = null;

    // Visibilidade via troca de classes hidden/flex (ver nota no .blade.php).
    const showModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const hideModal = () => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const renderA2hsState = () => {
        if (!a2hsCheck) {
            return;
        }

        a2hsCheck.hidden = !computeA2hsDone();
    };

    const renderNotifyState = () => {
        if (!supportsNotifications()) {
            if (notifyButton) {
                notifyButton.disabled = true;
                notifyButton.classList.add('opacity-60');
            }
            if (notifySub) {
                notifySub.textContent = 'No disponible en este navegador';
            }
            return;
        }

        const permission = Notification.permission;

        if (notifyCheck) {
            notifyCheck.hidden = permission !== 'granted';
        }

        if (permission === 'granted') {
            if (notifyButton) {
                notifyButton.disabled = true;
                notifyButton.classList.add('opacity-70');
            }
            if (notifySub) {
                notifySub.textContent = 'Notificaciones activadas';
            }
        } else if (permission === 'denied') {
            if (notifyButton) {
                notifyButton.disabled = true;
                notifyButton.classList.add('opacity-60');
            }
            if (notifySub) {
                notifySub.textContent = 'Bloqueadas — actívalas en tu navegador';
            }
        }
    };

    // Esconde o pop-up automaticamente quando ambos os passos ficam concluídos.
    const hideIfComplete = () => {
        if (computeA2hsDone() && computeNotifDone()) {
            hideModal();
        }
    };

    // Estado inicial: só mostra se faltar algum passo.
    if (computeA2hsDone() && computeNotifDone()) {
        return;
    }

    renderA2hsState();
    renderNotifyState();
    showModal();

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        localStorage.setItem(A2HS_DONE_KEY, '1');
        renderA2hsState();
        hideIfComplete();
    });

    // Passo 1 — nunca fecha o pop-up (a pessoa sai do app para concluir).
    if (a2hsButton) {
        a2hsButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                try {
                    deferredPrompt.prompt();
                    const choice = await deferredPrompt.userChoice;

                    if (choice && choice.outcome === 'accepted') {
                        localStorage.setItem(A2HS_DONE_KEY, '1');
                        renderA2hsState();
                        hideIfComplete();
                    }
                } catch (error) {
                    console.error('Error al mostrar el prompt de instalación:', error);
                }

                deferredPrompt = null;

                return;
            }

            // Sem prompt nativo: mostra instruções inline (iOS ou desktop).
            if (isIos()) {
                if (iosInstructions) {
                    iosInstructions.hidden = false;
                }
            } else if (genericInstructions) {
                genericInstructions.hidden = false;
            }
        });
    }

    // Passo 2 — pede permissão de notificações.
    if (notifyButton) {
        notifyButton.addEventListener('click', async () => {
            if (!supportsNotifications() || Notification.permission !== 'default') {
                return;
            }

            try {
                await Notification.requestPermission();
            } catch (error) {
                console.error('Error al solicitar permiso de notificaciones:', error);
            }

            renderNotifyState();
            hideIfComplete();
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', hideModal);
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            hideModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            hideModal();
        }
    });
}

document.addEventListener('DOMContentLoaded', setupAppOnboarding);

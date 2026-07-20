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

function getVapidPublicKey() {
    const meta = document.querySelector('meta[name="vapid-public-key"]');
    const key = meta ? meta.getAttribute('content') : '';

    return key ? key.trim() : '';
}

// Converte a chave pública VAPID (base64url) para o Uint8Array que a
// PushManager.subscribe() exige em applicationServerKey.
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const output = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i += 1) {
        output[i] = rawData.charCodeAt(i);
    }

    return output;
}

// Garante que este dispositivo esteja inscrito no push e registrado no backend.
// Idempotente: reaproveita a subscription existente do navegador se houver.
async function ensurePushSubscription() {
    if (!supportsNotifications() || Notification.permission !== 'granted') {
        return;
    }

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
    }

    const vapidPublicKey = getVapidPublicKey();

    if (!vapidPublicKey) {
        return; // Chaves VAPID ainda não configuradas no admin.
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });
        }

        const csrf = document.querySelector('meta[name="csrf-token"]');

        await fetch('/mi-biblioteca/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(subscription.toJSON()),
        });
    } catch (error) {
        console.error('Error al registrar la suscripción push:', error);
    }
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
                // En iOS la API de notificaciones solo existe cuando la app está
                // instalada en la pantalla de inicio y abierta como app (standalone).
                notifySub.textContent = (isIos() && !isStandalone())
                    ? 'Primero agrega a inicio y abre la app'
                    : 'No disponible en este navegador';
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

            if (Notification.permission === 'granted') {
                await ensurePushSubscription();
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

// Reinscreve quem já concedeu permissão antes desta feature (ou em outra sessão),
// garantindo que o endpoint esteja registrado no backend.
document.addEventListener('DOMContentLoaded', () => {
    ensurePushSubscription();
});

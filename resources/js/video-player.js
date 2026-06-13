function formatTime(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '0:00';
    }

    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);

    return `${mins}:${String(secs).padStart(2, '0')}`;
}

function initVideoPlayer(root) {
    const streamUrl = root.dataset.streamUrl;
    const saveUrl = root.dataset.saveUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const video = root.querySelector('[data-video-element]');
    const playBtn = root.querySelector('[data-video-play]');
    const playCenterBtn = root.querySelector('[data-video-play-center]');
    const overlay = root.querySelector('[data-video-overlay]');
    const backBtn = root.querySelector('[data-video-back]');
    const forwardBtn = root.querySelector('[data-video-forward]');
    const currentEl = root.querySelector('[data-video-current]');
    const durationEl = root.querySelector('[data-video-duration]');
    const fillEl = root.querySelector('[data-video-progress-fill]');
    const progressTrack = root.querySelector('[data-video-progress-track]');
    const iconPlay = root.querySelector('[data-icon-play]');
    const iconPause = root.querySelector('[data-icon-pause]');
    const iconPlaySm = root.querySelector('[data-icon-play-sm]');
    const iconPauseSm = root.querySelector('[data-icon-pause-sm]');

    if (!video || !streamUrl) {
        return;
    }

    let saveTimer = null;
    const initialSeconds = parseInt(root.dataset.initialSeconds || '0', 10);
    const knownDuration = parseInt(root.dataset.durationSeconds || '0', 10);

    video.playsInline = true;
    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');

    if (!video.getAttribute('src')) {
        video.src = streamUrl;
    }

    video.load();

    const updateProgressUi = () => {
        const duration = video.duration && Number.isFinite(video.duration) ? video.duration : knownDuration;
        const current = video.currentTime || 0;

        if (currentEl) {
            currentEl.textContent = formatTime(current);
        }

        if (durationEl && duration) {
            durationEl.textContent = formatTime(duration);
        }

        if (fillEl && duration > 0) {
            const percent = Math.min(100, (current / duration) * 100);
            fillEl.style.width = `${percent}%`;
        }
    };

    const setPlayingState = (playing) => {
        iconPlay?.classList.toggle('hidden', playing);
        iconPause?.classList.toggle('hidden', !playing);
        iconPlaySm?.classList.toggle('hidden', playing);
        iconPauseSm?.classList.toggle('hidden', !playing);
        overlay?.classList.toggle('opacity-0', playing);
        overlay?.classList.toggle('pointer-events-none', playing);
    };

    const saveProgress = () => {
        if (!saveUrl) {
            return;
        }

        clearTimeout(saveTimer);
        saveTimer = setTimeout(async () => {
            try {
                await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        progress_seconds: Math.floor(video.currentTime || 0),
                    }),
                });
            } catch {
                // Silencioso
            }
        }, 800);
    };

    const togglePlay = () => {
        if (video.paused) {
            video.play().catch(() => {});
        } else {
            video.pause();
        }
    };

    const bindTap = (element, handler) => {
        if (!element) {
            return;
        }

        let lastTouch = 0;

        element.addEventListener('touchend', (event) => {
            event.preventDefault();
            lastTouch = Date.now();
            handler();
        }, { passive: false });

        element.addEventListener('click', () => {
            if (Date.now() - lastTouch < 400) {
                return;
            }

            handler();
        });
    };

    bindTap(playBtn, togglePlay);
    bindTap(playCenterBtn, togglePlay);

    backBtn?.addEventListener('click', () => {
        video.currentTime = Math.max(0, video.currentTime - 10);
        updateProgressUi();
        saveProgress();
    });

    forwardBtn?.addEventListener('click', () => {
        const max = video.duration && Number.isFinite(video.duration) ? video.duration : knownDuration;
        video.currentTime = max ? Math.min(max, video.currentTime + 10) : video.currentTime + 10;
        updateProgressUi();
        saveProgress();
    });

    progressTrack?.addEventListener('click', (event) => {
        const rect = progressTrack.getBoundingClientRect();
        const ratio = Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width));
        const duration = video.duration && Number.isFinite(video.duration) ? video.duration : knownDuration;

        if (duration > 0) {
            video.currentTime = duration * ratio;
            updateProgressUi();
            saveProgress();
        }
    });

    video.addEventListener('loadedmetadata', () => {
        if (initialSeconds > 0 && initialSeconds < (video.duration || Infinity)) {
            video.currentTime = initialSeconds;
        }
        updateProgressUi();
    });

    video.addEventListener('canplay', updateProgressUi);
    video.addEventListener('play', () => setPlayingState(true));
    video.addEventListener('pause', () => setPlayingState(false));
    video.addEventListener('timeupdate', () => {
        updateProgressUi();
        saveProgress();
    });
    video.addEventListener('ended', saveProgress);

    setPlayingState(false);
}

document.querySelectorAll('[data-video-player]').forEach(initVideoPlayer);

function formatTime(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '0:00';
    }

    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);

    return `${mins}:${String(secs).padStart(2, '0')}`;
}

function initAudioPlayer(root) {
    const streamUrl = root.dataset.streamUrl;
    const saveUrl = root.dataset.saveUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const audio = root.querySelector('[data-audio-element]');
    const playBtn = root.querySelector('[data-audio-play]');
    const iconPlay = root.querySelector('[data-icon-play]');
    const iconPause = root.querySelector('[data-icon-pause]');
    const backBtn = root.querySelector('[data-audio-back]');
    const forwardBtn = root.querySelector('[data-audio-forward]');
    const currentEl = root.querySelector('[data-audio-current]');
    const durationEl = root.querySelector('[data-audio-duration]');
    const fillEl = root.querySelector('[data-audio-progress-fill]');

    if (!audio || !streamUrl) {
        return;
    }

    let saveTimer = null;
    const initialSeconds = parseInt(root.dataset.initialSeconds || '0', 10);
    const knownDuration = parseInt(root.dataset.durationSeconds || '0', 10);

    audio.src = streamUrl;

    const updateProgressUi = () => {
        const duration = audio.duration && Number.isFinite(audio.duration) ? audio.duration : knownDuration;
        const current = audio.currentTime || 0;

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
                        progress_seconds: Math.floor(audio.currentTime || 0),
                    }),
                });
            } catch {
                // Silencioso
            }
        }, 800);
    };

    playBtn?.addEventListener('click', () => {
        if (audio.paused) {
            audio.play().catch(() => {});
        } else {
            audio.pause();
        }
    });

    backBtn?.addEventListener('click', () => {
        audio.currentTime = Math.max(0, audio.currentTime - 15);
        updateProgressUi();
        saveProgress();
    });

    forwardBtn?.addEventListener('click', () => {
        const max = audio.duration && Number.isFinite(audio.duration) ? audio.duration : knownDuration;
        audio.currentTime = Math.min(max || audio.currentTime + 15, audio.currentTime + 15);
        updateProgressUi();
        saveProgress();
    });

    audio.addEventListener('loadedmetadata', () => {
        if (initialSeconds > 0) {
            audio.currentTime = initialSeconds;
        }
        updateProgressUi();
    });

    audio.addEventListener('play', () => setPlayingState(true));
    audio.addEventListener('pause', () => setPlayingState(false));
    audio.addEventListener('timeupdate', () => {
        updateProgressUi();
        saveProgress();
    });
    audio.addEventListener('ended', saveProgress);

    setPlayingState(false);
}

document.querySelectorAll('[data-audio-player]').forEach(initAudioPlayer);

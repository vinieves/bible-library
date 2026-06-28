function initForumReaction(button) {
    const reactUrl = button.dataset.reactUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const countEl = button.querySelector('[data-forum-reaction-count]');

    if (!reactUrl) {
        return;
    }

    let pending = false;

    button.addEventListener('click', async () => {
        if (pending) {
            return;
        }

        pending = true;
        button.disabled = true;

        try {
            const response = await fetch(reactUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const data = await response.json();

            button.dataset.reacted = data.reacted ? '1' : '0';
            button.setAttribute('aria-pressed', data.reacted ? 'true' : 'false');
            button.classList.toggle('border-gold/40', data.reacted);
            button.classList.toggle('bg-gold/10', data.reacted);
            button.classList.toggle('text-gold', data.reacted);
            button.classList.toggle('border-line', !data.reacted);
            button.classList.toggle('text-muted', !data.reacted);

            if (countEl) {
                countEl.textContent = data.count;
            }
        } catch {
            // Silencioso
        } finally {
            pending = false;
            button.disabled = false;
        }
    });
}

document.querySelectorAll('[data-forum-reaction]').forEach(initForumReaction);

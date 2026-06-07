// Tiny progressive-enhancement helpers for the panel. No framework — just two
// delegated listeners that power the error surfaces (the global error modal and
// the API-unavailable page). Server-rendered HTML works without JS; this only
// adds the copy + dismiss conveniences on top.

// Copy-to-clipboard for any [data-copy] trigger (e.g. the support reference
// code). Briefly swaps the trigger's label to its [data-copied-label] on success.
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-copy]');
    if (!trigger) return;

    const value = trigger.getAttribute('data-copy');
    if (!value || !navigator.clipboard?.writeText) return;

    navigator.clipboard.writeText(value).then(() => {
        const label = trigger.querySelector('[data-copy-label]') ?? trigger;
        const copied = trigger.getAttribute('data-copied-label');
        if (!copied) return;

        const original = label.textContent;
        label.textContent = copied;
        setTimeout(() => {
            label.textContent = original;
        }, 2000);
    }).catch(() => {
        // Clipboard blocked (insecure context / permissions) — the code is still
        // visible for the user to copy by hand, so fail silently.
    });
});

// Dismiss the global error modal via its backdrop, the Dismiss button, or Esc.
// It's a notification, not a blocker.
const dismissErrorModal = () => document.getElementById('app-error-modal')?.remove();

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-dismiss-modal]')) dismissErrorModal();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') dismissErrorModal();
});

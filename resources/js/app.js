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

// Country combobox: progressively enhance any [data-country-select] (a styled
// native <select> whose options carry data-flag/data-name/data-dial) into a
// searchable, flag-rich listbox. The native <select> stays in the DOM as the
// submitted source of truth and the no-JS fallback — we just hide it visually
// and mirror the chosen value back onto it, so the form posts the same code.
function enhanceCountrySelects() {
    document.querySelectorAll('[data-country-select]').forEach((root) => {
        const select = root.querySelector('[data-country-native]');
        if (!select || root.dataset.enhanced) return;
        root.dataset.enhanced = 'true';

        const searchLabel = root.getAttribute('data-search-label') || 'Search…';
        const emptyLabel = root.getAttribute('data-empty-label') || 'No results';
        const placeholder = select.querySelector('[data-placeholder]')?.textContent.trim() || 'Select…';

        const options = Array.from(select.options)
            .filter((o) => o.value !== '')
            .map((o) => ({
                value: o.value,
                flag: o.getAttribute('data-flag') || '',
                name: o.getAttribute('data-name') || o.textContent.trim(),
                dial: o.getAttribute('data-dial') || '',
            }));

        // Hide the native control but leave it submitting (CSS display never
        // suppresses form submission — only `disabled` would).
        select.classList.add('sr-only');
        select.setAttribute('tabindex', '-1');
        select.setAttribute('aria-hidden', 'true');

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white py-2.5 pl-3 pr-3 text-left text-sm text-gray-900 shadow-sm transition hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');
        if (select.id) button.setAttribute('aria-labelledby', select.id);

        const buttonLabel = document.createElement('span');
        buttonLabel.className = 'flex min-w-0 flex-1 items-center gap-2';
        const chevron = document.createElement('span');
        chevron.className = 'shrink-0 text-gray-400';
        chevron.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>';
        button.append(buttonLabel, chevron);

        const panel = document.createElement('div');
        panel.className = 'absolute z-20 mt-1 hidden w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg';

        const searchWrap = document.createElement('div');
        searchWrap.className = 'border-b border-gray-100 p-2';
        const search = document.createElement('input');
        search.type = 'text';
        search.placeholder = searchLabel;
        search.className = 'block w-full rounded-md border border-gray-200 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40';
        searchWrap.append(search);

        const list = document.createElement('ul');
        list.className = 'max-h-60 overflow-y-auto py-1';
        list.setAttribute('role', 'listbox');

        const empty = document.createElement('li');
        empty.className = 'px-3 py-2 text-sm text-gray-400';
        empty.textContent = emptyLabel;
        empty.hidden = true;

        panel.append(searchWrap, list, empty);
        root.append(button, panel);

        const renderButton = () => {
            const opt = options.find((o) => o.value === select.value);
            buttonLabel.textContent = '';
            if (!opt) {
                const ph = document.createElement('span');
                ph.className = 'truncate text-gray-400';
                ph.textContent = placeholder;
                buttonLabel.append(ph);
                return;
            }
            if (opt.flag) {
                const img = document.createElement('img');
                img.src = opt.flag;
                img.alt = '';
                img.className = 'h-4 w-6 shrink-0 rounded-sm object-cover';
                buttonLabel.append(img);
            }
            const name = document.createElement('span');
            name.className = 'truncate';
            name.textContent = opt.name;
            buttonLabel.append(name);
            if (opt.dial) {
                const dial = document.createElement('span');
                dial.className = 'shrink-0 text-gray-400';
                dial.textContent = opt.dial;
                buttonLabel.append(dial);
            }
        };

        // Options are catalog data (not user input) but we still build each row
        // with DOM nodes + textContent rather than innerHTML — no string HTML.
        options.forEach((o) => {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.dataset.value = o.value;
            li.dataset.search = `${o.name} ${o.dial} ${o.value}`.toLowerCase();
            li.className = 'flex cursor-pointer items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-indigo-50';
            if (o.flag) {
                const img = document.createElement('img');
                img.src = o.flag;
                img.alt = '';
                img.className = 'h-4 w-6 shrink-0 rounded-sm object-cover';
                li.append(img);
            }
            const name = document.createElement('span');
            name.className = 'truncate';
            name.textContent = o.name;
            li.append(name);
            if (o.dial) {
                const dial = document.createElement('span');
                dial.className = 'ml-auto shrink-0 text-gray-400';
                dial.textContent = o.dial;
                li.append(dial);
            }
            list.append(li);
        });

        const filter = (query) => {
            const needle = query.trim().toLowerCase();
            let visible = 0;
            list.querySelectorAll('[role=option]').forEach((li) => {
                const match = !needle || li.dataset.search.includes(needle);
                li.hidden = !match;
                if (match) visible++;
            });
            empty.hidden = visible > 0;
        };
        const open = () => {
            panel.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
            search.value = '';
            filter('');
            search.focus();
        };
        const close = () => {
            panel.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        };

        button.addEventListener('click', () => (panel.classList.contains('hidden') ? open() : close()));
        search.addEventListener('input', () => filter(search.value));
        list.addEventListener('click', (event) => {
            const li = event.target.closest('[role=option]');
            if (!li) return;
            select.value = li.dataset.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            renderButton();
            close();
            button.focus();
        });
        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) close();
        });
        root.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
                button.focus();
            }
        });

        renderButton();
    });
}

document.addEventListener('DOMContentLoaded', enhanceCountrySelects);

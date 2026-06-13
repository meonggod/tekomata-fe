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

// Business hours configurator on the company settings page.
// Handles mode radio (always-active / scheduled), per-day checkboxes, and
// add / remove time-slot rows inside each day section.
function initBusinessHours() {
    const form = document.getElementById('assistant-form');
    if (!form) return;

    const scheduledSection = document.getElementById('bh-scheduled');
    const oohSection       = document.getElementById('bh-ooh');

    function applyMode() {
        const mode = form.querySelector('input[name="business_hours_mode"]:checked')?.value;
        const show = mode === 'scheduled';
        scheduledSection?.classList.toggle('hidden', !show);
        oohSection?.classList.toggle('hidden', !show);
    }

    form.querySelectorAll('input[name="business_hours_mode"]').forEach((r) =>
        r.addEventListener('change', applyMode)
    );
    applyMode();

    // Day toggles — show/hide the slots container.
    form.querySelectorAll('[data-bh-day]').forEach((dayEl) => {
        const toggle    = dayEl.querySelector('[data-bh-toggle]');
        const slotsWrap = dayEl.querySelector('[data-bh-slots]');
        if (!toggle || !slotsWrap) return;

        toggle.addEventListener('change', () =>
            slotsWrap.classList.toggle('hidden', !toggle.checked)
        );
    });

    // Add / remove slot buttons (delegated so newly-created rows work too).
    form.addEventListener('click', (e) => {
        const addBtn = e.target.closest('[data-bh-add]');
        if (addBtn) {
            const day  = addBtn.getAttribute('data-bh-add');
            const wrap = form.querySelector(`[data-bh-day="${day}"] [data-bh-slots]`);
            if (!wrap) return;

            const idx           = parseInt(wrap.dataset.bhNextIdx ?? wrap.querySelectorAll('[data-bh-slot]').length);
            wrap.dataset.bhNextIdx = idx + 1;

            // Insert the new slot before the "Add slot" button.
            wrap.insertBefore(createBhSlot(day, idx), addBtn);
            return;
        }

        const removeBtn = e.target.closest('[data-bh-remove]');
        if (removeBtn) removeBtn.closest('[data-bh-slot]')?.remove();
    });
}

function createBhSlot(day, idx) {
    const slotClass  = 'w-28 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40';
    const rmClass    = 'rounded p-1 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500';

    const wrap = document.createElement('div');
    wrap.setAttribute('data-bh-slot', '');
    wrap.className = 'flex items-center gap-2';

    const open = document.createElement('input');
    open.type  = 'time';
    open.name  = `bh[${day}][${idx}][open]`;
    open.className = slotClass;

    const sep = document.createElement('span');
    sep.textContent = '–';
    sep.className   = 'shrink-0 text-xs text-gray-400';

    const close = document.createElement('input');
    close.type  = 'time';
    close.name  = `bh[${day}][${idx}][close]`;
    close.className = slotClass;

    const rm = document.createElement('button');
    rm.type = 'button';
    rm.setAttribute('data-bh-remove', '');
    rm.className = rmClass;
    rm.setAttribute('aria-label', 'Remove slot');
    rm.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>';

    wrap.append(open, sep, close, rm);
    return wrap;
}

document.addEventListener('DOMContentLoaded', initBusinessHours);

// Mobile sidebar toggle
(function () {
    const sidebar  = document.getElementById('app-sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const openBtn  = document.getElementById('sidebar-open');
    if (!sidebar || !overlay || !openBtn) return;

    const open  = () => { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); };
    const close = () => { sidebar.classList.add('-translate-x-full');    overlay.classList.add('hidden'); };

    openBtn.addEventListener('click', open);
    overlay.addEventListener('click', close);
})();

// ---------------------------------------------------------------------------
// Inbox — split-pane conversation list + thread view
// ---------------------------------------------------------------------------
// Progressively enhances the server-rendered inbox page. Conversation clicks
// load the thread via fetch() without a full page reload; reply, assign,
// status, and note mutations use JSON endpoints.
function initInbox() {
    const root = document.querySelector('[data-inbox]');
    if (!root) return;

    const listPanel   = document.getElementById('inbox-list-panel');
    const threadPanel = document.getElementById('inbox-thread-panel');
    const toastContainer = document.getElementById('inbox-toast');

    const csrf       = root.dataset.csrf;
    const loginUrl   = root.dataset.loginUrl;
    const tplThread   = root.dataset.threadUrlTemplate;
    const tplReply    = root.dataset.replyUrlTemplate;
    const tplAssign   = root.dataset.assignUrlTemplate;
    const tplStatus   = root.dataset.statusUrlTemplate;
    const tplNotes    = root.dataset.notesUrlTemplate;
    const tplTakeover = root.dataset.takeoverUrlTemplate;
    const tplHandback = root.dataset.handbackUrlTemplate;
    // Base paths for history/navigation (carry the app URL prefix from Blade).
    const indexUrl = root.dataset.indexUrl || '/app/inbox';
    const tplShow  = root.dataset.showUrlTemplate || (indexUrl + '/{id}');

    // i18n strings from data attributes
    const i18n = {};
    Object.keys(root.dataset).forEach((k) => {
        if (k.startsWith('i18n')) {
            const key = k.replace('i18n', '').replace(/^[A-Z]/, (c) => c.toLowerCase())
                .replace(/[A-Z]/g, (c) => '_' + c.toLowerCase());
            i18n[key] = root.dataset[k];
        }
    });

    let activeConvId = null;

    // --- Helpers ---

    function url(template, id) {
        return template.replace('{id}', id);
    }

    function showToast(message, isError) {
        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto rounded-lg px-4 py-2.5 text-sm font-medium shadow-lg transition-opacity '
            + (isError ? 'bg-red-600 text-white' : 'bg-gray-900 text-white');
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    async function apiFetch(fetchUrl, options) {
        const res = await fetch(fetchUrl, {
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            ...options,
        });
        if (res.status === 401) {
            window.location.href = loginUrl;
            return null;
        }
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err?.error?.message || 'Request failed');
        }
        return res.json();
    }

    // --- Status chip colors ---
    const statusColors = {
        open:     'bg-green-100 text-green-700',
        pending:  'bg-yellow-100 text-yellow-700',
        resolved: 'bg-blue-100 text-blue-700',
        closed:   'bg-gray-100 text-gray-600',
    };

    const statusLabels = {
        open:     i18n.status_open || 'Open',
        pending:  i18n.status_pending || 'Pending',
        resolved: i18n.status_resolved || 'Resolved',
        closed:   i18n.status_closed || 'Closed',
    };

    // --- Mobile panel toggling ---
    function showThread() {
        if (window.innerWidth < 1024) {
            listPanel.classList.add('hidden');
            threadPanel.classList.remove('hidden');
        }
        threadPanel.classList.add('flex');
    }

    function showList() {
        if (window.innerWidth < 1024) {
            listPanel.classList.remove('hidden');
            threadPanel.classList.add('hidden');
        }
    }

    // --- Build a message DOM node ---
    function createMessageEl(msg) {
        // API direction enum is `in | out | internal`; normalise the external
        // pair to the inbound/outbound this renderer aligns against. `__outbound`
        // is set on a reply WE just sent, so it always renders on the sent side.
        let direction = msg.direction || 'inbound';
        if (direction === 'in') direction = 'inbound';
        else if (direction === 'out') direction = 'outbound';
        if (msg.__outbound) direction = 'outbound';
        const body = msg.body || '';
        const author = msg.author_name || (direction === 'inbound' ? (i18n.customer || 'Customer') : (i18n.agent || 'Agent'));
        let msgTime = '';
        if (msg.created_at) {
            try {
                const d = new Date(msg.created_at);
                msgTime = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
            } catch (_) {
                msgTime = msg.created_at;
            }
        }

        const wrap = document.createElement('div');
        wrap.className = 'mb-3';
        if (msg.id) wrap.dataset.messageId = msg.id;

        if (direction === 'internal') {
            wrap.className += ' mx-auto max-w-lg';
            const inner = document.createElement('div');
            inner.className = 'rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-2.5';
            const header = document.createElement('div');
            header.className = 'flex items-center gap-2 text-xs text-yellow-700';
            const label = document.createElement('span');
            label.className = 'font-medium';
            label.textContent = i18n.note_label || 'Internal note';
            const meta = document.createElement('span');
            meta.className = 'ml-auto';
            meta.textContent = author + ' \u00b7 ' + msgTime;
            header.append(label, meta);
            const bodyEl = document.createElement('p');
            bodyEl.className = 'mt-1 text-sm text-yellow-800 whitespace-pre-wrap';
            bodyEl.textContent = body;
            inner.append(header, bodyEl);
            wrap.append(inner);
        } else if (direction === 'inbound') {
            wrap.className += ' flex justify-start';
            const bubble = document.createElement('div');
            bubble.className = 'max-w-xs rounded-lg bg-gray-100 px-4 py-2.5 sm:max-w-md';
            const header = document.createElement('div');
            header.className = 'flex items-center gap-2 text-xs text-gray-500';
            const nameEl = document.createElement('span');
            nameEl.className = 'font-medium';
            nameEl.textContent = author;
            const timeEl = document.createElement('span');
            timeEl.textContent = msgTime;
            header.append(nameEl, timeEl);
            const bodyEl = document.createElement('p');
            bodyEl.className = 'mt-1 text-sm text-gray-800 whitespace-pre-wrap';
            bodyEl.textContent = body;
            bubble.append(header, bodyEl);
            wrap.append(bubble);
        } else {
            wrap.className += ' flex justify-end';
            const bubble = document.createElement('div');
            bubble.className = 'max-w-xs rounded-lg px-4 py-2.5 sm:max-w-md '
                + (msg.__failed ? 'bg-red-500' : 'bg-indigo-600')
                + (msg.__pending ? ' opacity-70' : '');
            const header = document.createElement('div');
            header.className = 'flex items-center gap-2 text-xs ' + (msg.__failed ? 'text-red-100' : 'text-indigo-200');
            const nameEl = document.createElement('span');
            nameEl.className = 'font-medium';
            nameEl.textContent = author;
            const timeEl = document.createElement('span');
            timeEl.textContent = msg.__failed ? (i18n.send_failed || 'Failed to send') : msgTime;
            header.append(nameEl, timeEl);
            const bodyEl = document.createElement('p');
            bodyEl.className = 'mt-1 text-sm text-white whitespace-pre-wrap';
            bodyEl.textContent = body;
            bubble.append(header, bodyEl);
            wrap.append(bubble);
        }

        return wrap;
    }

    // --- Load thread ---
    // opts.around — open centered on (and highlight) a specific message id.
    async function loadThread(convId, opts) {
        activeConvId = convId;

        // Highlight active conversation in list
        listPanel.querySelectorAll('[data-conversation-id]').forEach((el) => {
            const isActive = el.dataset.conversationId === convId;
            el.classList.toggle('bg-indigo-50', isActive);
            el.classList.toggle('ring-1', isActive);
            el.classList.toggle('ring-indigo-200', isActive);
            el.classList.toggle('hover:bg-gray-50', !isActive);
        });

        try {
            const around = opts && opts.around;
            const data = await apiFetch(url(tplThread, convId) + (around ? '?around=' + encodeURIComponent(around) : ''));
            if (!data) return;

            renderThread(data.conversation, data.messages || [], data.page || null);
            showThread();

            // Update URL without reload
            const newUrl = url(tplShow, convId);
            if (window.location.pathname !== newUrl) {
                history.pushState({ convId }, '', newUrl);
            }
        } catch (e) {
            showToast(i18n.error_thread || e.message, true);
        }
    }

    // Cursor pagination state for the open thread (from the API's `page` object).
    // Reset whenever a thread is (re)loaded. Drives scroll-up (older) history and,
    // after an `around` focus window, scroll-down (newer) until live takes over.
    let threadPage = emptyPage();
    let loadingOlder = false;
    let loadingNewer = false;
    let inboxTempCounter = 0; // ids for optimistic (not-yet-confirmed) replies/notes

    function emptyPage() {
        return { older_cursor: null, newer_cursor: null, has_more_older: false, has_more_newer: false, target_id: null };
    }

    function applyThreadPage(page) {
        threadPage = page ? {
            older_cursor: page.older_cursor ?? null,
            newer_cursor: page.newer_cursor ?? null,
            has_more_older: !!page.has_more_older,
            has_more_newer: !!page.has_more_newer,
            target_id: page.target_id ?? null,
        } : emptyPage();
    }

    // Attach the scroll-driven history loader to a messages area (idempotent).
    function attachThreadScroll(area) {
        if (!area || area.dataset.scrollBound) return;
        area.dataset.scrollBound = '1';
        area.addEventListener('scroll', () => {
            if (area.scrollTop <= 80) loadOlder();
            if (threadPage.has_more_newer && (area.scrollHeight - area.scrollTop - area.clientHeight) <= 80) loadNewer();
        });
    }

    // Scroll to a specific message and flash it (jump-to-message). Falls back to
    // the bottom if the target isn't in the current DOM.
    function focusTargetMessage(area, targetId) {
        if (!area || !targetId) return;
        const sel = '[data-message-id="' + (window.CSS && CSS.escape ? CSS.escape(targetId) : targetId) + '"]';
        const el = area.querySelector(sel);
        if (!el) { area.scrollTop = area.scrollHeight; return; }
        el.scrollIntoView({ block: 'center' });
        el.classList.add('msg-flash');
        setTimeout(() => el.classList.remove('msg-flash'), 2000);
    }

    // Fetch + PREPEND the next older page, preserving the agent's scroll position.
    async function loadOlder() {
        if (loadingOlder || !threadPage.has_more_older || !threadPage.older_cursor || !activeConvId) return;
        loadingOlder = true;
        try {
            const data = await apiFetch(url(tplThread, activeConvId) + '?before=' + encodeURIComponent(threadPage.older_cursor));
            if (!data) return;
            const area = threadPanel.querySelector('[data-inbox-messages]');
            if (!area) return;
            const prevHeight = area.scrollHeight;
            const prevTop = area.scrollTop;
            const frag = document.createDocumentFragment();
            (data.messages || []).forEach((m) => {
                if (m.id && area.querySelector('[data-message-id="' + m.id + '"]')) return;
                frag.append(createMessageEl(m));
            });
            area.insertBefore(frag, area.firstChild);
            area.scrollTop = prevTop + (area.scrollHeight - prevHeight); // keep view steady
            threadPage.older_cursor = data.page ? data.page.older_cursor : null;
            threadPage.has_more_older = !!(data.page && data.page.has_more_older);
        } catch (_) {
            // silent — a failed history page just leaves the current view intact
        } finally {
            loadingOlder = false;
        }
    }

    // Fetch + APPEND the next newer page (only relevant after an `around` window).
    async function loadNewer() {
        if (loadingNewer || !threadPage.has_more_newer || !threadPage.newer_cursor || !activeConvId) return;
        loadingNewer = true;
        try {
            const data = await apiFetch(url(tplThread, activeConvId) + '?after=' + encodeURIComponent(threadPage.newer_cursor));
            if (!data) return;
            const area = threadPanel.querySelector('[data-inbox-messages]');
            if (!area) return;
            const frag = document.createDocumentFragment();
            (data.messages || []).forEach((m) => {
                if (m.id && area.querySelector('[data-message-id="' + m.id + '"]')) return;
                frag.append(createMessageEl(m));
            });
            area.append(frag);
            threadPage.newer_cursor = data.page ? data.page.newer_cursor : null;
            threadPage.has_more_newer = !!(data.page && data.page.has_more_newer);
        } catch (_) {
            // silent
        } finally {
            loadingNewer = false;
        }
    }

    // --- Render thread into the panel ---
    function renderThread(conv, messages, page) {
        const convId = conv.id || '';
        const channel = conv.channel || 'unknown';
        const status = conv.status || 'open';
        const title = conv.title || conv.contact_name || '';
        const kind = conv.kind || 'human';
        const assignee = conv.assignee_name || null;

        threadPanel.innerHTML = '';

        const container = document.createElement('div');
        container.id = 'inbox-thread';
        container.className = 'flex h-full flex-col';
        container.dataset.conversationId = convId;
        container.dataset.channel = channel;

        // Header
        const header = document.createElement('div');
        header.className = 'shrink-0 border-b border-gray-200 bg-white px-4 py-3 sm:px-6';

        const headerRow = document.createElement('div');
        headerRow.className = 'flex items-center justify-between gap-3';

        // Title area
        const titleArea = document.createElement('div');
        titleArea.className = 'min-w-0';

        const titleRow = document.createElement('div');
        titleRow.className = 'flex items-center gap-2';

        // Mobile back button
        const backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.setAttribute('data-inbox-back', '');
        backBtn.className = 'rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden';
        backBtn.innerHTML = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>';

        const titleEl = document.createElement('h2');
        titleEl.className = 'truncate text-sm font-semibold text-gray-900';
        titleEl.textContent = title;

        titleRow.append(backBtn, titleEl);

        const chipsRow = document.createElement('div');
        chipsRow.className = 'mt-1 flex flex-wrap items-center gap-1.5';

        const statusChip = document.createElement('span');
        statusChip.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ' + (statusColors[status] || statusColors.open);
        statusChip.textContent = statusLabels[status] || status;
        statusChip.setAttribute('data-inbox-status-chip', '');

        const channelChip = document.createElement('span');
        channelChip.className = 'inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600';
        channelChip.textContent = channel;

        chipsRow.append(statusChip, channelChip);

        if (kind === 'ai') {
            const aiChip = document.createElement('span');
            aiChip.className = 'inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700';
            aiChip.textContent = 'AI';
            chipsRow.append(aiChip);

            const aiActive = conv.ai_active === true;
            const aiStatusChip = document.createElement('span');
            if (aiActive) {
                aiStatusChip.className = 'inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700';
                aiStatusChip.innerHTML = '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714a2.25 2.25 0 0 0 .659 1.591L19 14.5m-4.75-11.396c.25.023.5.05.75.082M12 19.5a2.25 2.25 0 0 1-2.25-2.25V15.5" /></svg>';
                const label = document.createElement('span');
                label.textContent = i18n.ai_active || 'AI active';
                aiStatusChip.append(label);
            } else {
                aiStatusChip.className = 'inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600';
                const label = document.createElement('span');
                label.textContent = i18n.human_active || 'Human active';
                aiStatusChip.append(label);
            }
            chipsRow.append(aiStatusChip);
        }

        titleArea.append(titleRow, chipsRow);

        // Actions
        const actions = document.createElement('div');
        actions.className = 'flex shrink-0 items-center gap-2';

        const assigneeLabel = document.createElement('span');
        assigneeLabel.className = 'hidden text-xs sm:inline ' + (assignee ? 'text-gray-500' : 'text-gray-400');
        assigneeLabel.setAttribute('data-inbox-assignee-label', '');
        assigneeLabel.textContent = assignee
            ? (i18n.assigned_to || 'Assigned to :name').replace(':name', assignee)
            : (i18n.unassigned || 'Unassigned');

        const assignBtn = document.createElement('button');
        assignBtn.type = 'button';
        if (assignee) {
            assignBtn.setAttribute('data-inbox-unassign', '');
            assignBtn.className = 'rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50';
            assignBtn.textContent = i18n.unassign || 'Unassign';
        } else {
            assignBtn.setAttribute('data-inbox-take', '');
            assignBtn.className = 'rounded-lg border border-indigo-300 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100';
            assignBtn.textContent = i18n.take || 'Take';
        }

        const statusSelect = document.createElement('select');
        statusSelect.setAttribute('data-inbox-status-select', '');
        statusSelect.className = 'rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-7 text-xs font-medium text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40';
        ['open', 'pending', 'resolved', 'closed'].forEach((s) => {
            const opt = document.createElement('option');
            opt.value = s;
            opt.textContent = statusLabels[s] || s;
            if (s === status) opt.selected = true;
            statusSelect.append(opt);
        });

        if (kind === 'ai') {
            const aiActive = conv.ai_active === true;
            const handoffBtn = document.createElement('button');
            handoffBtn.type = 'button';
            if (aiActive) {
                handoffBtn.setAttribute('data-inbox-takeover', '');
                handoffBtn.className = 'rounded-lg border border-indigo-300 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50';
                handoffBtn.textContent = i18n.takeover || 'Take over from AI';
            } else {
                handoffBtn.setAttribute('data-inbox-handback', '');
                handoffBtn.className = 'rounded-lg border border-purple-300 px-2.5 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-50';
                handoffBtn.textContent = i18n.handback || 'Hand back to AI';
            }
            actions.append(assigneeLabel, handoffBtn, assignBtn, statusSelect);
        } else {
            actions.append(assigneeLabel, assignBtn, statusSelect);
        }
        headerRow.append(titleArea, actions);
        header.append(headerRow);

        // Messages
        const messagesArea = document.createElement('div');
        messagesArea.id = 'inbox-messages';
        messagesArea.className = 'flex-1 overflow-y-auto px-4 py-4 sm:px-6';
        messagesArea.setAttribute('data-inbox-messages', '');

        messages.forEach((msg) => messagesArea.append(createMessageEl(msg)));

        applyThreadPage(page);
        attachThreadScroll(messagesArea);

        // Focus a referenced message (around mode) or pin to the latest.
        setTimeout(() => {
            if (threadPage.target_id) focusTargetMessage(messagesArea, threadPage.target_id);
            else messagesArea.scrollTop = messagesArea.scrollHeight;
        }, 0);

        // Composer
        const composer = document.createElement('div');
        composer.className = 'shrink-0 border-t border-gray-200 bg-white px-4 py-3 sm:px-6';

        const noteRow = document.createElement('div');
        noteRow.className = 'mb-2 flex items-center gap-3';

        const noteLabel = document.createElement('label');
        noteLabel.className = 'flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer';
        const noteCheckbox = document.createElement('input');
        noteCheckbox.type = 'checkbox';
        noteCheckbox.setAttribute('data-inbox-note-toggle', '');
        noteCheckbox.className = 'h-3.5 w-3.5 rounded border-gray-300 text-yellow-500 focus:ring-yellow-500/40';
        const noteLabelText = document.createElement('span');
        noteLabelText.textContent = i18n.note_label || 'Internal note';
        noteLabel.append(noteCheckbox, noteLabelText);
        noteRow.append(noteLabel);

        if (channel === 'whatsapp') {
            const hint = document.createElement('span');
            hint.className = 'text-xs text-amber-600';
            hint.textContent = root.dataset.i18nWhatsappWindowHint || 'WhatsApp 24h messaging window';
            noteRow.append(hint);
        }

        const form = document.createElement('form');
        form.setAttribute('data-inbox-reply-form', '');
        form.className = 'flex gap-2';

        const textarea = document.createElement('textarea');
        textarea.setAttribute('data-inbox-reply-body', '');
        textarea.rows = 1;
        textarea.className = 'block flex-1 resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40';
        textarea.placeholder = root.dataset.i18nReplyPlaceholder || 'Type your reply...';

        const submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.setAttribute('data-inbox-reply-submit', '');
        submitBtn.className = 'inline-flex shrink-0 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 disabled:opacity-50';
        submitBtn.textContent = root.dataset.i18nReplySubmit || 'Send';

        form.append(textarea, submitBtn);
        composer.append(noteRow, form);

        container.append(header, messagesArea, composer);
        threadPanel.append(container);
        threadPanel.classList.add('flex');
        threadPanel.classList.remove('hidden');
    }

    // --- Conversation click (delegated) ---
    listPanel.addEventListener('click', (e) => {
        const item = e.target.closest('[data-conversation-id]');
        if (!item) return;
        e.preventDefault();
        loadThread(item.dataset.conversationId);
    });

    // --- Back button (mobile, delegated) ---
    threadPanel.addEventListener('click', (e) => {
        if (e.target.closest('[data-inbox-back]')) {
            showList();
            history.pushState({}, '', indexUrl);
        }
    });

    // Append a message, skipping it if its id is already in the thread (so a
    // locally-echoed send isn't duplicated when the SSE stream re-delivers it).
    // Scrolls to the newest only when `force` (our own send) or the agent is
    // already near the bottom — so a live message never yanks them away from
    // older history they've scrolled up to read.
    function appendInboxMessage(messagesArea, msg, force) {
        if (!messagesArea || !msg) return;
        if (msg.id && messagesArea.querySelector('[data-message-id="' + msg.id + '"]')) return;
        const nearBottom = (messagesArea.scrollHeight - messagesArea.scrollTop - messagesArea.clientHeight) < 120;
        messagesArea.append(createMessageEl(msg));
        if (force || nearBottom) messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    // --- Reply form (delegated) ---
    // OPTIMISTIC send: clear the composer and show the reply on the right at once,
    // POST in the background, then reconcile (or mark failed and restore the text).
    threadPanel.addEventListener('submit', (e) => {
        const form = e.target.closest('[data-inbox-reply-form]');
        if (!form) return;
        e.preventDefault();

        const bodyEl = form.querySelector('[data-inbox-reply-body]');
        const body = bodyEl?.value?.trim();
        if (!body || !activeConvId) return;

        const isNote = threadPanel.querySelector('[data-inbox-note-toggle]')?.checked;
        bodyEl.value = '';
        bodyEl.focus();
        sendInboxOptimistic(activeConvId, body, isNote, bodyEl);
    });

    async function sendInboxOptimistic(convId, body, isNote, bodyEl) {
        const area = threadPanel.querySelector('[data-inbox-messages]');
        const tempId = 'tmp-' + (++inboxTempCounter);
        if (area) {
            appendInboxMessage(area, {
                id: tempId, body, __pending: true, created_at: Date.now(),
                direction: isNote ? 'internal' : 'out',
                __outbound: ! isNote,
            }, true);
        }
        try {
            const endpoint = isNote ? tplNotes : tplReply;
            const data = await apiFetch(url(endpoint, convId), { method: 'POST', body: JSON.stringify({ body }) });
            if (!data || !data.message) { failInboxMessage(tempId, body, isNote); return; }
            if (! isNote) data.message.__outbound = true;
            reconcileInboxMessage(tempId, data.message);
        } catch (err) {
            failInboxMessage(tempId, body, isNote);
            if (bodyEl && !bodyEl.value) bodyEl.value = body; // let them resend
            showToast(isNote ? (i18n.error_note || err.message) : (i18n.error_reply || err.message), true);
        }
    }

    // Replace the optimistic placeholder with the server's real message (real id),
    // unless a live SSE echo already delivered it (then just drop the placeholder).
    function reconcileInboxMessage(tempId, realMsg) {
        const area = threadPanel.querySelector('[data-inbox-messages]');
        if (!area) return;
        const tempEl = area.querySelector('[data-message-id="' + tempId + '"]');
        const realEl = realMsg.id ? area.querySelector('[data-message-id="' + realMsg.id + '"]') : null;
        if (realEl && realEl !== tempEl) {
            if (tempEl) tempEl.remove();
            return;
        }
        const newEl = createMessageEl(realMsg);
        if (tempEl) tempEl.replaceWith(newEl);
        else area.append(newEl);
    }

    function failInboxMessage(tempId, body, isNote) {
        const area = threadPanel.querySelector('[data-inbox-messages]');
        const el = area && area.querySelector('[data-message-id="' + tempId + '"]');
        if (el) el.replaceWith(createMessageEl({ id: tempId, body: body || '', direction: isNote ? 'internal' : 'out', __outbound: ! isNote, __failed: true }));
    }

    // --- Enter to send; Shift+Enter inserts a newline (delegated) ---
    threadPanel.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' || e.shiftKey || e.isComposing) return;
        const bodyEl = e.target.closest('[data-inbox-reply-body]');
        if (!bodyEl) return;
        e.preventDefault();
        bodyEl.closest('[data-inbox-reply-form]')?.requestSubmit();
    });

    // --- Assign / Take / Unassign (delegated) ---
    threadPanel.addEventListener('click', async (e) => {
        const takeBtn = e.target.closest('[data-inbox-take]');
        const unassignBtn = e.target.closest('[data-inbox-unassign]');
        if (!takeBtn && !unassignBtn) return;
        if (!activeConvId) return;

        const assigneeId = takeBtn ? 'self' : null;

        try {
            const data = await apiFetch(url(tplAssign, activeConvId), {
                method: 'POST',
                body: JSON.stringify({ assignee_user_id: assigneeId }),
            });
            if (!data) return;
            showToast(assigneeId ? (i18n.assign_success || 'Assigned.') : (i18n.unassign_success || 'Unassigned.'), false);

            // Reload thread to update header
            loadThread(activeConvId);
        } catch (err) {
            showToast(i18n.error_assign || err.message, true);
        }
    });

    // --- Takeover / Handback (delegated) ---
    threadPanel.addEventListener('click', async (e) => {
        const takeoverBtn = e.target.closest('[data-inbox-takeover]');
        const handbackBtn = e.target.closest('[data-inbox-handback]');
        if (!takeoverBtn && !handbackBtn) return;
        if (!activeConvId) return;

        const btn = takeoverBtn || handbackBtn;
        btn.disabled = true;

        try {
            if (takeoverBtn) {
                await apiFetch(url(tplTakeover, activeConvId), { method: 'POST' });
                showToast(i18n.takeover_success || 'You took over.', false);
            } else {
                await apiFetch(url(tplHandback, activeConvId), { method: 'POST' });
                showToast(i18n.handback_success || 'Handed back to AI.', false);
            }
            loadThread(activeConvId);
        } catch (err) {
            showToast(i18n.error_handoff || err.message, true);
            btn.disabled = false;
        }
    });

    // --- Status change (delegated) ---
    threadPanel.addEventListener('change', async (e) => {
        const select = e.target.closest('[data-inbox-status-select]');
        if (!select || !activeConvId) return;

        try {
            const data = await apiFetch(url(tplStatus, activeConvId), {
                method: 'PATCH',
                body: JSON.stringify({ status: select.value }),
            });
            if (!data) return;

            // Update status chip
            const chip = threadPanel.querySelector('[data-inbox-status-chip]');
            if (chip) {
                chip.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ' + (statusColors[select.value] || statusColors.open);
                chip.textContent = statusLabels[select.value] || select.value;
            }

            showToast(i18n.status_updated || 'Status updated.', false);
        } catch (err) {
            showToast(i18n.error_status || err.message, true);
        }
    });

    // --- Filter dropdowns ---
    root.querySelectorAll('[data-inbox-filter]').forEach((select) => {
        select.addEventListener('change', () => {
            const params = new URLSearchParams();
            root.querySelectorAll('[data-inbox-filter]').forEach((s) => {
                if (s.value) params.set(s.dataset.inboxFilter, s.value);
            });
            const qs = params.toString();
            window.location.href = indexUrl + (qs ? '?' + qs : '');
        });
    });

    // --- Load more ---
    root.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-inbox-load-more]');
        if (!btn) return;

        const offset = parseInt(btn.dataset.offset, 10) || 0;
        const params = new URLSearchParams(window.location.search);
        params.set('limit', '25');
        params.set('offset', String(offset));

        btn.textContent = root.dataset.i18nLoading || 'Loading...';
        btn.disabled = true;

        try {
            const res = await fetch(indexUrl + '?' + params.toString(), {
                headers: { 'Accept': 'text/html' },
            });
            if (!res.ok) throw new Error();
            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newItems = doc.querySelectorAll('[data-conversation-id]');
            const list = document.getElementById('inbox-conversation-list');
            const btnParent = btn.parentElement;

            newItems.forEach((item) => {
                list.insertBefore(item.cloneNode(true), btnParent);
            });

            const newLoadMore = doc.querySelector('[data-inbox-load-more]');
            if (newLoadMore) {
                btn.dataset.offset = newLoadMore.dataset.offset;
                btn.textContent = root.dataset.i18nLoadMore || 'Load more';
                btn.disabled = false;
            } else {
                btnParent.remove();
            }
        } catch (err) {
            showToast(i18n.error_load || err.message, true);
            btn.textContent = root.dataset.i18nLoadMore || 'Load more';
            btn.disabled = false;
        }
    });

    // --- Browser back/forward ---
    window.addEventListener('popstate', (e) => {
        if (e.state?.convId) {
            loadThread(e.state.convId);
        } else {
            showList();
            activeConvId = null;
            listPanel.querySelectorAll('[data-conversation-id]').forEach((el) => {
                el.classList.remove('bg-indigo-50', 'ring-1', 'ring-indigo-200');
                el.classList.add('hover:bg-gray-50');
            });
        }
    });

    // --- Initial state: if a thread was server-rendered, mark it active ---
    const prerendered = threadPanel.querySelector('[data-conversation-id]');
    if (prerendered) {
        activeConvId = prerendered.dataset.conversationId;
        showThread();
    }

    // -----------------------------------------------------------------------
    // Real-time: SSE connection, typing indicator, read markers
    // -----------------------------------------------------------------------

    const streamUrl  = root.dataset.streamUrl;
    const tplRead    = root.dataset.readUrlTemplate;
    const tplTyping  = root.dataset.typingUrlTemplate;
    const typingLabel = root.dataset.i18nTyping || ':name is typing...';
    const readLabel   = root.dataset.i18nRead || 'Read';

    // --- SSE connection with exponential backoff ---
    let sse = null;
    let sseRetryDelay = 1000;
    const SSE_MAX_DELAY = 30000;

    function connectSSE() {
        if (!streamUrl) return;

        sse = new EventSource(streamUrl);
        sseRetryDelay = 1000; // reset on successful open

        sse.onopen = () => { sseRetryDelay = 1000; };

        sse.addEventListener('message', (e) => {
            try { handleSSE('message', JSON.parse(e.data)); } catch (_) {}
        });
        sse.addEventListener('conversation', (e) => {
            try { handleSSE('conversation', JSON.parse(e.data)); } catch (_) {}
        });
        sse.addEventListener('typing', (e) => {
            try { handleSSE('typing', JSON.parse(e.data)); } catch (_) {}
        });
        sse.addEventListener('read', (e) => {
            try { handleSSE('read', JSON.parse(e.data)); } catch (_) {}
        });

        sse.onerror = () => {
            sse.close();
            setTimeout(connectSSE, sseRetryDelay);
            sseRetryDelay = Math.min(sseRetryDelay * 2, SSE_MAX_DELAY);
        };
    }

    // --- SSE event router ---
    function handleSSE(type, data) {
        const convId = data.conversation_id || data.id || '';

        if (type === 'message') {
            // Append to active thread
            if (convId === activeConvId) {
                appendInboxMessage(threadPanel.querySelector('[data-inbox-messages]'), data);
                // Agent is viewing — mark as read
                postRead(convId);
            }
            // Update conversation list item
            updateConvListItem(convId, {
                snippet: data.body || '',
                timestamp: data.created_at,
            });
        }

        if (type === 'conversation') {
            updateConvListItem(convId, {
                status: data.status,
                assignee: data.assignee_name,
                snippet: data.last_message_body || data.snippet || '',
                timestamp: data.updated_at || data.last_message_at,
            });
        }

        if (type === 'typing') {
            if (convId === activeConvId) {
                showTypingIndicator(data.author_name || '');
            }
        }

        if (type === 'read') {
            if (convId === activeConvId) {
                markOutboundRead(data.read_until || '');
            }
        }
    }

    // --- Conversation list live update ---
    function updateConvListItem(convId, patch) {
        const item = listPanel.querySelector(`[data-conversation-id="${convId}"]`);
        if (!item) return; // new conversation — could prepend, but skip for simplicity

        // Update snippet text
        if (patch.snippet !== undefined) {
            const snippetEl = item.querySelector('[data-conv-snippet]');
            if (snippetEl) snippetEl.textContent = patch.snippet;
        }

        // Update timestamp
        if (patch.timestamp) {
            const timeEl = item.querySelector('[data-conv-time]');
            if (timeEl) {
                try {
                    const d = new Date(patch.timestamp);
                    timeEl.textContent = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
                } catch (_) {
                    timeEl.textContent = patch.timestamp;
                }
            }
        }

        // Update status chip
        if (patch.status) {
            const chip = item.querySelector('[data-conv-status]');
            if (chip) {
                chip.className = 'inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium ' + (statusColors[patch.status] || statusColors.open);
                chip.textContent = statusLabels[patch.status] || patch.status;
            }
        }

        // Move to top of list
        const list = document.getElementById('inbox-conversation-list');
        if (list && list.firstElementChild !== item) {
            list.insertBefore(item, list.firstElementChild);
        }
    }

    // --- Typing indicator ---
    let typingTimeout = null;

    function showTypingIndicator(name) {
        let indicator = threadPanel.querySelector('#inbox-typing');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'inbox-typing';
            indicator.className = 'px-4 pb-2 text-xs text-gray-400 italic sm:px-6';
            const messagesArea = threadPanel.querySelector('[data-inbox-messages]');
            if (messagesArea) {
                messagesArea.parentNode.insertBefore(indicator, messagesArea.nextSibling);
            }
        }
        indicator.textContent = typingLabel.replace(':name', name);
        indicator.hidden = false;

        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => { indicator.hidden = true; }, 3000);
    }

    // --- Outgoing typing signal (debounced) ---
    let lastTypingSent = 0;
    threadPanel.addEventListener('input', (e) => {
        if (!e.target.closest('[data-inbox-reply-body]')) return;
        if (!activeConvId || !tplTyping) return;

        const now = Date.now();
        if (now - lastTypingSent < 3000) return; // debounce 3s
        lastTypingSent = now;

        fetch(url(tplTyping, activeConvId), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).catch(() => {}); // fire-and-forget
    });

    // --- Read marking ---
    function postRead(convId) {
        if (!tplRead) return;
        fetch(url(tplRead, convId), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).catch(() => {}); // fire-and-forget
    }

    // Mark as read when loading a thread (patch into existing loadThread flow)
    const origLoadThread = loadThread;
    loadThread = async function (convId) {
        await origLoadThread(convId);
        postRead(convId);
    };

    // --- Mark outbound messages as read ---
    function markOutboundRead(readUntil) {
        if (!readUntil) return;
        const messagesArea = threadPanel.querySelector('[data-inbox-messages]');
        if (!messagesArea) return;

        // Find outbound message bubbles and add a "Read" indicator
        messagesArea.querySelectorAll('.justify-end').forEach((wrap) => {
            // Only add once
            if (wrap.querySelector('[data-read-marker]')) return;
            const marker = document.createElement('div');
            marker.setAttribute('data-read-marker', '');
            marker.className = 'mt-0.5 text-right text-xs text-gray-400';
            marker.textContent = readLabel;
            wrap.append(marker);
        });
    }

    // --- Start SSE ---
    connectSSE();

    // Wire up an existing (server-rendered) thread: adopt it as active, read the
    // embedded pagination cursors, enable scroll-up history, and either focus the
    // referenced message (?message=…) or pin to the latest.
    const initialThread = threadPanel.querySelector('#inbox-thread[data-conversation-id]');
    const initialMessages = threadPanel.querySelector('[data-inbox-messages]');
    if (initialThread) activeConvId = initialThread.dataset.conversationId;
    if (initialMessages) {
        let page = null;
        const pageEl = threadPanel.querySelector('[data-inbox-page-json]');
        if (pageEl) { try { page = JSON.parse(pageEl.textContent); } catch (_) { page = null; } }
        applyThreadPage(page);
        attachThreadScroll(initialMessages);
        if (threadPage.target_id) focusTargetMessage(initialMessages, threadPage.target_id);
        else initialMessages.scrollTop = initialMessages.scrollHeight;
    }
}

document.addEventListener('DOMContentLoaded', initInbox);

// ---------------------------------------------------------------------------
// Team Chat — internal 1:1 and group conversations
// ---------------------------------------------------------------------------
function initTeamChat() {
    const root = document.querySelector('[data-team]');
    if (!root) return;

    const listPanel   = document.getElementById('team-list-panel');
    const threadPanel = document.getElementById('team-thread-panel');
    const toastContainer = document.getElementById('team-toast');

    const csrf          = root.dataset.csrf;
    const loginUrl      = root.dataset.loginUrl;
    const currentUserId = root.dataset.currentUserId;
    const tplThread     = root.dataset.threadUrlTemplate;
    const tplSend       = root.dataset.sendUrlTemplate;
    const createUrl     = root.dataset.createUrl;
    const tplAddMembers = root.dataset.addMembersUrlTemplate;
    const streamUrl     = root.dataset.streamUrl;
    // Base paths for history/navigation (carry the app URL prefix from Blade).
    const indexUrl = root.dataset.indexUrl || '/app/team';
    const tplShow  = root.dataset.showUrlTemplate || (indexUrl + '/{id}');

    // i18n strings from data attributes
    const i18n = {};
    Object.keys(root.dataset).forEach((k) => {
        if (k.startsWith('i18n')) {
            const key = k.replace('i18n', '').replace(/^[A-Z]/, (c) => c.toLowerCase())
                .replace(/[A-Z]/g, (c) => '_' + c.toLowerCase());
            i18n[key] = root.dataset[k];
        }
    });

    let activeConvId = null;

    // --- Helpers ---

    function url(template, id) {
        return template.replace('{id}', id);
    }

    function showToast(message, isError) {
        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto rounded-lg px-4 py-2.5 text-sm font-medium shadow-lg transition-opacity '
            + (isError ? 'bg-red-600 text-white' : 'bg-gray-900 text-white');
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    async function apiFetch(fetchUrl, options) {
        const res = await fetch(fetchUrl, {
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            ...options,
        });
        if (res.status === 401) {
            window.location.href = loginUrl;
            return null;
        }
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err?.error?.message || 'Request failed');
        }
        if (res.status === 204) return {};
        return res.json();
    }

    // --- Mobile panel toggling ---
    function showThread() {
        if (window.innerWidth < 1024) {
            listPanel.classList.add('hidden');
            threadPanel.classList.remove('hidden');
        }
        threadPanel.classList.add('flex');
    }

    function showList() {
        if (window.innerWidth < 1024) {
            listPanel.classList.remove('hidden');
            threadPanel.classList.add('hidden');
        }
    }

    // --- Build a message DOM node ---
    // Team messages only carry `participant_id` (no author_user_id/author_name),
    // and there is no /auth/me endpoint — so the FE can't know up-front which
    // participant is "me". A `participant_id` is unique per (user, conversation),
    // so we collect the ones the API returns on messages WE send: any message
    // whose participant_id is in the set is ours. Persisted in localStorage and
    // keyed by the current user id (from the JWT), so it survives reloads, only
    // needs one send per conversation ever, and one account's ids can't leak into
    // another's (localStorage isn't cleared on logout). Until we've sent in a
    // thread, its messages default to the received side (left).
    const MY_PIDS_KEY = 'tekomata.team.my_participants:' + (currentUserId || 'anon');
    let myPids = new Set();
    try { myPids = new Set(JSON.parse(localStorage.getItem(MY_PIDS_KEY)) || []); } catch (_) { myPids = new Set(); }

    function rememberMyPid(pid) {
        if (!pid || myPids.has(pid)) return false;
        myPids.add(pid);
        try { localStorage.setItem(MY_PIDS_KEY, JSON.stringify([...myPids])); } catch (_) {}
        return true; // newly learned
    }

    function isMineMsg(msg) {
        // `__mine` is set on messages WE just sent (authored by us by definition);
        // everything else is matched by the participant ids we've learned.
        return !!(msg && (msg.__mine || (msg.participant_id && myPids.has(msg.participant_id))));
    }

    // The messages currently shown in the thread, kept in memory so we can add a
    // new one (deduped by id) or re-render in place when we first learn our own
    // participant id — without a refetch and without ever double-appending.
    let teamThreadMessages = [];
    let tempCounter = 0; // ids for optimistic (not-yet-confirmed) messages

    // While a send is in flight, the server's SSE echo of OUR message (which has
    // no participant_id) can beat the POST response and paint it on the left for a
    // blink. So we buffer SSE messages during a send and flush them right after,
    // by which point the message is already placed correctly from the response.
    let pendingSends = 0;
    const sseBuffer = [];

    function flushSseBuffer() {
        const queued = sseBuffer.splice(0);
        queued.forEach((m) => addTeamMessage(m));
    }

    function teamEmptyEl() {
        const p = document.createElement('p');
        p.className = 'py-8 text-center text-sm text-gray-400';
        p.textContent = i18n.empty_state || '';
        return p;
    }

    function renderTeamMessages() {
        const area = threadPanel.querySelector('[data-team-messages]');
        if (!area) return;
        area.innerHTML = '';
        if (teamThreadMessages.length === 0) {
            area.append(teamEmptyEl());
            return;
        }
        teamThreadMessages.forEach((m) => area.append(createTeamMessageEl(m)));
        area.scrollTop = area.scrollHeight;
    }

    // Add (or update) a message in the thread, then repaint. The SAME message can
    // arrive twice with different fields: the SSE echo carries no `participant_id`,
    // while the POST response we send does (plus `__mine`). So we MERGE by id — the
    // richer copy's fields win — instead of keeping whichever landed first. This is
    // what fixes a sent message rendering left when the SSE echo beats the response.
    function addTeamMessage(msg) {
        const area = threadPanel.querySelector('[data-team-messages]');
        if (!area || !msg) return;
        const idx = msg.id ? teamThreadMessages.findIndex((m) => m.id === msg.id) : -1;
        if (idx === -1) {
            teamThreadMessages.push(msg);
        } else {
            teamThreadMessages[idx] = Object.assign({}, teamThreadMessages[idx], msg);
        }
        renderTeamMessages();
    }

    function createTeamMessageEl(msg) {
        const body = msg.body || '';
        const authorName = msg.author_name || '';
        const isMine = isMineMsg(msg);
        let msgTime = '';
        if (msg.created_at) {
            try {
                const d = new Date(msg.created_at);
                msgTime = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
            } catch (_) {
                msgTime = msg.created_at;
            }
        }

        const wrap = document.createElement('div');
        wrap.className = 'mb-3';
        if (msg.id) wrap.dataset.messageId = msg.id;

        if (isMine) {
            wrap.className += ' flex justify-end';
            const bubble = document.createElement('div');
            bubble.className = 'max-w-xs rounded-lg px-4 py-2.5 sm:max-w-md '
                + (msg.__failed ? 'bg-red-500' : 'bg-indigo-600')
                + (msg.__pending ? ' opacity-70' : '');
            const header = document.createElement('div');
            header.className = 'flex items-center gap-2 text-xs ' + (msg.__failed ? 'text-red-100' : 'text-indigo-200');
            const nameEl = document.createElement('span');
            nameEl.className = 'font-medium';
            nameEl.textContent = authorName;
            const timeEl = document.createElement('span');
            timeEl.textContent = msg.__failed ? (i18n.send_failed || 'Failed to send') : msgTime;
            header.append(nameEl, timeEl);
            const bodyEl = document.createElement('p');
            bodyEl.className = 'mt-1 text-sm text-white whitespace-pre-wrap';
            bodyEl.textContent = body;
            bubble.append(header, bodyEl);
            wrap.append(bubble);
        } else {
            wrap.className += ' flex justify-start';
            const bubble = document.createElement('div');
            bubble.className = 'max-w-xs rounded-lg bg-gray-100 px-4 py-2.5 sm:max-w-md';
            const header = document.createElement('div');
            header.className = 'flex items-center gap-2 text-xs text-gray-500';
            const nameEl = document.createElement('span');
            nameEl.className = 'font-medium';
            nameEl.textContent = authorName;
            const timeEl = document.createElement('span');
            timeEl.textContent = msgTime;
            header.append(nameEl, timeEl);
            const bodyEl = document.createElement('p');
            bodyEl.className = 'mt-1 text-sm text-gray-800 whitespace-pre-wrap';
            bodyEl.textContent = body;
            bubble.append(header, bodyEl);
            wrap.append(bubble);
        }

        return wrap;
    }

    // --- Load thread ---
    async function loadThread(convId) {
        activeConvId = convId;

        // Highlight active conversation in list
        listPanel.querySelectorAll('[data-conversation-id]').forEach((el) => {
            const isActive = el.dataset.conversationId === convId;
            el.classList.toggle('bg-indigo-50', isActive);
            el.classList.toggle('ring-1', isActive);
            el.classList.toggle('ring-indigo-200', isActive);
            el.classList.toggle('hover:bg-gray-50', !isActive);
        });

        try {
            const data = await apiFetch(url(tplThread, convId));
            if (!data) return;

            // Find conversation metadata from list
            const convItem = listPanel.querySelector(`[data-conversation-id="${convId}"]`);
            const scope = convItem?.dataset.scope || 'direct';

            renderThread(convId, scope, data.messages || []);
            showThread();

            // Update URL without reload
            const newUrl = url(tplShow, convId);
            if (window.location.pathname !== newUrl) {
                history.pushState({ convId }, '', newUrl);
            }
        } catch (e) {
            showToast(i18n.error_thread || e.message, true);
        }
    }

    // --- Render thread into the panel ---
    function renderThread(convId, scope, messages) {
        // Try to extract title from list item
        const convItem = listPanel.querySelector(`[data-conversation-id="${convId}"]`);
        const title = convItem?.querySelector('.text-sm.font-medium')?.textContent?.trim() || (i18n.badge_direct || 'Direct');

        const scopeColor = scope === 'group' ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700';
        const scopeLabel = scope === 'group' ? (i18n.badge_group || 'Group') : (i18n.badge_direct || 'Direct');

        threadPanel.innerHTML = '';

        const container = document.createElement('div');
        container.id = 'team-thread';
        container.className = 'flex h-full flex-col';
        container.dataset.conversationId = convId;
        container.dataset.scope = scope;

        // Header
        const header = document.createElement('div');
        header.className = 'shrink-0 border-b border-gray-200 bg-white px-4 py-3 sm:px-6';

        const headerRow = document.createElement('div');
        headerRow.className = 'flex items-center justify-between gap-3';

        const titleArea = document.createElement('div');
        titleArea.className = 'min-w-0';

        const titleRow = document.createElement('div');
        titleRow.className = 'flex items-center gap-2';

        // Mobile back button
        const backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.setAttribute('data-team-back', '');
        backBtn.className = 'rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden';
        backBtn.innerHTML = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>';

        const titleEl = document.createElement('h2');
        titleEl.className = 'truncate text-sm font-semibold text-gray-900';
        titleEl.textContent = title;

        titleRow.append(backBtn, titleEl);

        const chipsRow = document.createElement('div');
        chipsRow.className = 'mt-1 flex flex-wrap items-center gap-1.5';

        const scopeChip = document.createElement('span');
        scopeChip.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ' + scopeColor;
        scopeChip.textContent = scopeLabel;

        chipsRow.append(scopeChip);
        titleArea.append(titleRow, chipsRow);

        // Actions (add members for groups)
        const actions = document.createElement('div');
        actions.className = 'flex shrink-0 items-center gap-2';
        if (scope === 'group') {
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.setAttribute('data-team-add-members-open', '');
            addBtn.className = 'rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50';
            addBtn.textContent = i18n.add_members || 'Add Members';
            actions.append(addBtn);
        }

        headerRow.append(titleArea, actions);
        header.append(headerRow);

        // Messages
        const messagesArea = document.createElement('div');
        messagesArea.id = 'team-messages';
        messagesArea.className = 'flex-1 overflow-y-auto px-4 py-4 sm:px-6';
        messagesArea.setAttribute('data-team-messages', '');

        teamThreadMessages = (messages || []).slice();
        if (teamThreadMessages.length === 0) {
            messagesArea.append(teamEmptyEl());
        } else {
            teamThreadMessages.forEach((msg) => messagesArea.append(createTeamMessageEl(msg)));
        }
        setTimeout(() => { messagesArea.scrollTop = messagesArea.scrollHeight; }, 0);

        // Composer
        const composer = document.createElement('div');
        composer.className = 'shrink-0 border-t border-gray-200 bg-white px-4 py-3 sm:px-6';

        const form = document.createElement('form');
        form.setAttribute('data-team-reply-form', '');
        form.className = 'flex gap-2';

        const textarea = document.createElement('textarea');
        textarea.setAttribute('data-team-reply-body', '');
        textarea.rows = 1;
        textarea.className = 'block flex-1 resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40';
        textarea.placeholder = i18n.reply_placeholder || 'Type a message...';

        const submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.setAttribute('data-team-reply-submit', '');
        submitBtn.className = 'inline-flex shrink-0 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 disabled:opacity-50';
        submitBtn.textContent = i18n.reply_submit || 'Send';

        form.append(textarea, submitBtn);
        composer.append(form);

        container.append(header, messagesArea, composer);
        threadPanel.append(container);
        threadPanel.classList.add('flex');
        threadPanel.classList.remove('hidden');
    }

    // --- Conversation click (delegated) ---
    listPanel.addEventListener('click', (e) => {
        const item = e.target.closest('[data-conversation-id]');
        if (!item) return;
        e.preventDefault();
        loadThread(item.dataset.conversationId);
    });

    // --- Back button (mobile, delegated) ---
    threadPanel.addEventListener('click', (e) => {
        if (e.target.closest('[data-team-back]')) {
            showList();
            history.pushState({}, '', indexUrl);
        }
    });

    // --- Reply form (delegated) — OPTIMISTIC send ---
    // Clear the input and show the message on the right immediately, then POST in
    // the background and reconcile. No waiting on the round-trip; on the rare
    // failure the bubble is marked failed (and the text is restored to retry).
    threadPanel.addEventListener('submit', (e) => {
        const form = e.target.closest('[data-team-reply-form]');
        if (!form) return;
        e.preventDefault();

        const bodyEl = form.querySelector('[data-team-reply-body]');
        const body = bodyEl?.value?.trim();
        if (!body || !activeConvId) return;

        bodyEl.value = '';        // clear instantly
        bodyEl.focus();
        sendTeamOptimistic(activeConvId, body, bodyEl);
    });

    async function sendTeamOptimistic(convId, body, bodyEl) {
        const tempId = 'tmp-' + (++tempCounter);
        addTeamMessage({ id: tempId, conversation_id: convId, body, __mine: true, __pending: true, created_at: Date.now() });

        pendingSends++; // buffer SSE echoes until our message settles (no left flash)
        try {
            const data = await apiFetch(url(tplSend, convId), {
                method: 'POST',
                body: JSON.stringify({ body }),
            });
            if (!data || !data.message) { failTeamMessage(tempId); return; }
            data.message.__mine = true;
            rememberMyPid(data.message.participant_id);
            reconcileTeamMessage(tempId, data.message);
        } catch (err) {
            failTeamMessage(tempId);
            // Put the text back so the agent can resend without retyping.
            if (bodyEl && !bodyEl.value) bodyEl.value = body;
            showToast(i18n.error_send || err.message, true);
        } finally {
            pendingSends--;
            if (pendingSends === 0) flushSseBuffer();
        }
    }

    // Swap the optimistic placeholder for the server's real message (carrying the
    // real id + participant_id), so later SSE echoes dedupe against it.
    function reconcileTeamMessage(tempId, realMsg) {
        const tIdx = teamThreadMessages.findIndex((m) => m.id === tempId);
        const rIdx = realMsg.id ? teamThreadMessages.findIndex((m) => m.id === realMsg.id) : -1;
        if (rIdx !== -1 && rIdx !== tIdx) {
            // The real message is already present (a flushed SSE echo) — drop the
            // placeholder and fold our `__mine` onto the real entry.
            if (tIdx !== -1) teamThreadMessages.splice(tIdx, 1);
            const ri = teamThreadMessages.findIndex((m) => m.id === realMsg.id);
            teamThreadMessages[ri] = Object.assign({}, teamThreadMessages[ri], realMsg, { __pending: false, __failed: false });
        } else if (tIdx !== -1) {
            teamThreadMessages[tIdx] = Object.assign({}, teamThreadMessages[tIdx], realMsg, { __pending: false, __failed: false });
        } else {
            teamThreadMessages.push(Object.assign({}, realMsg, { __pending: false }));
        }
        renderTeamMessages();
    }

    function failTeamMessage(tempId) {
        const idx = teamThreadMessages.findIndex((m) => m.id === tempId);
        if (idx === -1) return;
        teamThreadMessages[idx].__pending = false;
        teamThreadMessages[idx].__failed = true;
        renderTeamMessages();
    }

    // --- Enter to send; Shift+Enter inserts a newline (delegated) ---
    threadPanel.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' || e.shiftKey || e.isComposing) return;
        const bodyEl = e.target.closest('[data-team-reply-body]');
        if (!bodyEl) return;
        e.preventDefault();
        bodyEl.closest('[data-team-reply-form]')?.requestSubmit();
    });

    // --- New Chat Modal ---
    const newChatModal   = document.getElementById('team-new-chat-modal');
    const directFields   = newChatModal?.querySelector('[data-team-direct-fields]');
    const groupFields    = newChatModal?.querySelector('[data-team-group-fields]');

    function openNewChatModal() { newChatModal?.classList.remove('hidden'); }
    function closeNewChatModal() { newChatModal?.classList.add('hidden'); }

    root.addEventListener('click', (e) => {
        if (e.target.closest('[data-team-new-chat-open]')) openNewChatModal();
        if (e.target.closest('[data-team-modal-close]') || e.target.closest('[data-team-modal-backdrop]')) closeNewChatModal();
    });

    // Scope radio toggle
    newChatModal?.addEventListener('change', (e) => {
        if (!e.target.closest('[data-team-scope-radio]')) return;
        const scope = e.target.value;
        if (directFields) directFields.classList.toggle('hidden', scope !== 'direct');
        if (groupFields) groupFields.classList.toggle('hidden', scope !== 'group');
    });

    // Create conversation submit
    newChatModal?.addEventListener('submit', async (e) => {
        const form = e.target.closest('[data-team-create-form]');
        if (!form) return;
        e.preventDefault();

        const scope = form.querySelector('input[name="scope"]:checked')?.value || 'direct';
        let payload;

        if (scope === 'direct') {
            const userId = form.querySelector('input[name="user_id"]')?.value?.trim();
            if (!userId) return;
            payload = { scope: 'direct', user_id: userId };
        } else {
            const title = form.querySelector('input[name="title"]')?.value?.trim();
            const membersRaw = form.querySelector('input[name="member_user_ids"]')?.value?.trim();
            if (!title) return;
            const memberUserIds = membersRaw ? membersRaw.split(',').map((s) => s.trim()).filter(Boolean) : [];
            payload = { scope: 'group', title, member_user_ids: memberUserIds };
        }

        try {
            const data = await apiFetch(createUrl, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            if (!data) return;

            closeNewChatModal();
            showToast(i18n.create_success || 'Conversation created.', false);

            // Navigate to new conversation
            if (data.conversation?.id) {
                window.location.href = url(tplShow, data.conversation.id);
            } else {
                window.location.href = indexUrl;
            }
        } catch (err) {
            showToast(i18n.error_create || err.message, true);
        }
    });

    // --- Add Members Modal ---
    const addMembersModal = document.getElementById('team-add-members-modal');

    function openAddMembersModal() { addMembersModal?.classList.remove('hidden'); }
    function closeAddMembersModal() { addMembersModal?.classList.add('hidden'); }

    root.addEventListener('click', (e) => {
        if (e.target.closest('[data-team-add-members-open]')) openAddMembersModal();
        if (e.target.closest('[data-team-members-close]') || e.target.closest('[data-team-members-backdrop]')) closeAddMembersModal();
    });

    addMembersModal?.addEventListener('submit', async (e) => {
        const form = e.target.closest('[data-team-add-members-form]');
        if (!form) return;
        e.preventDefault();
        if (!activeConvId) return;

        const membersRaw = form.querySelector('input[name="member_user_ids"]')?.value?.trim();
        if (!membersRaw) return;

        const memberUserIds = membersRaw.split(',').map((s) => s.trim()).filter(Boolean);
        if (!memberUserIds.length) return;

        try {
            await apiFetch(url(tplAddMembers, activeConvId), {
                method: 'POST',
                body: JSON.stringify({ member_user_ids: memberUserIds }),
            });
            closeAddMembersModal();
            form.querySelector('input[name="member_user_ids"]').value = '';
            showToast(i18n.add_members_success || 'Members added.', false);
        } catch (err) {
            showToast(i18n.error_add_members || err.message, true);
        }
    });

    // --- Browser back/forward ---
    window.addEventListener('popstate', (e) => {
        if (e.state?.convId) {
            loadThread(e.state.convId);
        } else {
            showList();
            activeConvId = null;
            listPanel.querySelectorAll('[data-conversation-id]').forEach((el) => {
                el.classList.remove('bg-indigo-50', 'ring-1', 'ring-indigo-200');
                el.classList.add('hover:bg-gray-50');
            });
        }
    });

    // --- Initial state: if a thread was server-rendered, render it from the
    // embedded JSON payload (no fetch) so alignment is correct on the FIRST paint
    // — the server can't know which participant is "me", so it ships the raw
    // messages and we paint them here. Falls back to a fetch if the payload is
    // missing/unparseable.
    const prerendered = threadPanel.querySelector('[data-conversation-id]');
    if (prerendered) {
        activeConvId = prerendered.dataset.conversationId;
        const scope = prerendered.dataset.scope || 'direct';
        const jsonEl = prerendered.querySelector('[data-team-thread-json]');
        let messages = null;
        if (jsonEl) {
            try { messages = JSON.parse(jsonEl.textContent); } catch (_) { messages = null; }
        }
        showThread();
        if (Array.isArray(messages)) {
            renderThread(activeConvId, scope, messages);
        } else {
            loadThread(activeConvId);
        }
    }

    // --- SSE: reuse existing inbox stream, filter for team events ---
    let sse = null;
    let sseRetryDelay = 1000;
    const SSE_MAX_DELAY = 30000;

    function connectSSE() {
        if (!streamUrl) return;

        sse = new EventSource(streamUrl);
        sseRetryDelay = 1000;

        sse.onopen = () => { sseRetryDelay = 1000; };

        sse.addEventListener('message', (e) => {
            try { handleSSE('message', JSON.parse(e.data)); } catch (_) {}
        });

        sse.onerror = () => {
            sse.close();
            setTimeout(connectSSE, sseRetryDelay);
            sseRetryDelay = Math.min(sseRetryDelay * 2, SSE_MAX_DELAY);
        };
    }

    function handleSSE(type, data) {
        const convId = data.conversation_id || '';

        if (type === 'message') {
            // Append to active thread
            if (convId === activeConvId) {
                // Hold echoes that land mid-send; flushed once the response places
                // our own message (so it never flashes on the left first).
                if (pendingSends > 0) sseBuffer.push(data);
                else addTeamMessage(data);
            }
            // Update conversation list snippet
            updateConvListItem(convId, {
                snippet: data.body || '',
                timestamp: data.created_at,
            });
        }
    }

    function updateConvListItem(convId, patch) {
        const item = listPanel.querySelector(`[data-conversation-id="${convId}"]`);
        if (!item) return;

        if (patch.snippet !== undefined) {
            const snippetEl = item.querySelector('[data-conv-snippet]');
            if (snippetEl) snippetEl.textContent = patch.snippet;
        }

        if (patch.timestamp) {
            const timeEl = item.querySelector('[data-conv-time]');
            if (timeEl) {
                try {
                    const d = new Date(patch.timestamp);
                    timeEl.textContent = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
                } catch (_) {
                    timeEl.textContent = patch.timestamp;
                }
            }
        }

        // Move to top of list
        const list = document.getElementById('team-conversation-list');
        if (list && list.firstElementChild !== item) {
            list.insertBefore(item, list.firstElementChild);
        }
    }

    connectSSE();

    // Pin an existing (server-rendered) thread to its latest message on load.
    const initialMessages = threadPanel.querySelector('[data-team-messages]');
    if (initialMessages) initialMessages.scrollTop = initialMessages.scrollHeight;
}

document.addEventListener('DOMContentLoaded', initTeamChat);

// ---------------------------------------------------------------------------
// Catalog import — async upload, live job tracker, conflict review, history
// ---------------------------------------------------------------------------
// Progressively enhances the import panel on the product list page. Uploads are
// non-blocking: POST returns a job id, then a per-job SSE stream drives the
// status badge, counts, mid-flight auto-apply toggle, conflict review, and the
// failed-rows retry panel. All routes + strings come from the JSON config the
// Blade partial embeds, so nothing is hard-coded here.
function initCatalogImport() {
    const root = document.querySelector('[data-import]');
    if (!root) return;

    let config;
    try {
        config = JSON.parse(root.querySelector('[data-import-config]').textContent);
    } catch (_) {
        return;
    }

    const i18n   = config.i18n || {};
    const routes = config.routes || {};
    const csrf   = config.csrf;

    // --- tiny helpers ---
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const t = (key, repl) => {
        let s = i18n[key] ?? key;
        if (repl) for (const k in repl) s = s.replace(`:${k}`, repl[k]);
        return s;
    };
    const jobUrl = (name, id) => (routes[name] || '').replace('__JOB__', encodeURIComponent(id));
    const jsonHeaders = { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' };

    async function apiError(res) {
        try {
            const data = await res.json();
            return data?.error?.message || Object.values(data?.errors || {})[0]?.[0] || t('load_error');
        } catch (_) {
            return t('load_error');
        }
    }

    // --- elements ---
    const openBtn   = document.querySelector('[data-import-open]');
    const panel     = root.querySelector('[data-import-panel]');
    const closeBtn  = root.querySelector('[data-import-close]');
    const form      = root.querySelector('[data-import-form]');
    const formError = root.querySelector('[data-import-form-error]');
    const submitBtn = root.querySelector('[data-import-submit]');
    const jobsEl    = root.querySelector('[data-import-jobs]');
    const reviewEl  = root.querySelector('[data-import-review]');
    const histToggle = root.querySelector('[data-import-history-toggle]');
    const histEl    = root.querySelector('[data-import-history]');
    const histChevron = root.querySelector('[data-import-history-chevron]');

    // job_id -> { id, status, filename, format, autoApply, summary, counts, concurrent, sse }
    const jobs = new Map();

    // --- panel open/close ---
    const openPanel  = () => { panel.hidden = false; panel.querySelector('input[type=file]')?.focus(); };
    const closePanel = () => { panel.hidden = true; };
    openBtn?.addEventListener('click', () => { panel.hidden ? openPanel() : closePanel(); });
    closeBtn?.addEventListener('click', closePanel);

    // --- status badge ---
    const STATUS_STYLES = {
        queued: 'bg-gray-100 text-gray-600',
        parsing: 'bg-blue-50 text-blue-700',
        staged: 'bg-amber-50 text-amber-700',
        applying: 'bg-blue-50 text-blue-700',
        done: 'bg-green-50 text-green-700',
        partial: 'bg-orange-50 text-orange-700',
        failed: 'bg-red-50 text-red-700',
        discarded: 'bg-gray-100 text-gray-500',
    };
    const statusLabel = (s) => t('status_' + s) || s;
    const isPreApply = (s) => s === 'queued' || s === 'parsing' || s === 'staged';
    const isTerminal = (s) => s === 'done' || s === 'partial' || s === 'failed' || s === 'discarded';

    function countsLine(job) {
        const c = job.counts || {};
        const parts = [];
        if (c.imported != null) parts.push(`${c.imported} ${t('count_imported')}`);
        if (c.updated != null)  parts.push(`${c.updated} ${t('count_updated')}`);
        if (c.skipped != null)  parts.push(`${c.skipped} ${t('count_skipped')}`);
        if (c.failed)           parts.push(`<span class="text-red-600">${c.failed} ${t('count_failed')}</span>`);
        return parts.join(' · ');
    }

    // --- render / update a job card ---
    function renderJob(job) {
        let card = jobsEl.querySelector(`[data-import-job="${CSS.escape(job.id)}"]`);
        if (!card) {
            card = document.createElement('div');
            card.dataset.importJob = job.id;
            card.className = 'rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm';
            jobsEl.append(card);
        }

        const fmt = (job.format || '').toLowerCase() === 'csv' ? t('format_csv') : t('format_xlsx');
        const showAutoApply = isPreApply(job.status);
        const showReview = job.status === 'staged' && job.summary?.needs_review;
        const showRetry  = job.status === 'partial' || job.status === 'failed';
        const concurrent = job.status === 'staged' && (job.concurrent?.length > 0);

        card.innerHTML = `
            <div class="flex flex-wrap items-center gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="truncate text-sm font-medium text-gray-900">${esc(job.filename)}</span>
                        <span class="shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-gray-500">${esc(fmt)}</span>
                    </div>
                    <p data-job-counts class="mt-0.5 text-xs text-gray-500">${countsLine(job)}</p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ${STATUS_STYLES[job.status] || 'bg-gray-100 text-gray-600'}">${esc(statusLabel(job.status))}</span>
                <div class="flex shrink-0 items-center gap-2">
                    ${showAutoApply ? `
                        <label class="flex items-center gap-1.5 text-xs text-gray-600">
                            <input type="checkbox" data-job-autoapply ${job.autoApply ? 'checked' : ''}
                                   class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            ${esc(t('auto_apply_label'))}
                        </label>` : ''}
                    ${showReview ? `<button type="button" data-job-review class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 transition hover:bg-amber-100">${esc(t('review_button'))}</button>` : ''}
                    ${showRetry ? `<button type="button" data-job-review class="rounded-lg border border-orange-300 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-800 transition hover:bg-orange-100">${esc(t('retry_button'))}</button>` : ''}
                    ${isPreApply(job.status) ? `<button type="button" data-job-discard class="rounded-lg px-2 py-1.5 text-xs font-medium text-gray-400 transition hover:text-red-600" title="${esc(t('discard_button'))}">&times;</button>` : ''}
                </div>
            </div>
            ${concurrent ? `<p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">${esc(t('concurrent_warning'))}</p>` : ''}
        `;

        card.querySelector('[data-job-autoapply]')?.addEventListener('change', (e) => toggleAutoApply(job, e.target.checked));
        card.querySelector('[data-job-review]')?.addEventListener('click', () => openReview(job.id));
        card.querySelector('[data-job-discard]')?.addEventListener('click', () => discardJob(job, false));
    }

    function removeJobCard(id) {
        jobsEl.querySelector(`[data-import-job="${CSS.escape(id)}"]`)?.remove();
    }

    // --- merge an SSE/event payload into a job ---
    function applyEvent(job, status, data) {
        job.status = status || job.status;

        if (data) {
            // staged summary
            if (data.ok_count != null || data.conflict_count != null || data.error_count != null || data.needs_review != null) {
                job.summary = {
                    ok_count: data.ok_count,
                    conflict_count: data.conflict_count,
                    error_count: data.error_count,
                    needs_review: data.needs_review,
                };
            }
            if (data.concurrent_staged_jobs) job.concurrent = data.concurrent_staged_jobs;
            if (data.auto_apply != null) job.autoApply = !!data.auto_apply;
            if (data.original_filename) job.filename = data.original_filename;
            if (data.format) job.format = data.format;

            // final counts (done/partial/failed)
            const c = job.counts || {};
            if (data.imported_count != null) c.imported = data.imported_count;
            if (data.updated_count != null)  c.updated = data.updated_count;
            if (data.skipped_count != null)  c.skipped = data.skipped_count;
            if (data.failed_count != null)   c.failed = data.failed_count;
            job.counts = c;
        }

        renderJob(job);

        if (isTerminal(job.status)) {
            closeJobSSE(job);
            loadHistory(true); // keep history fresh once a job settles
        }
    }

    // --- per-job SSE ---
    function connectJobSSE(job) {
        closeJobSSE(job);
        const url = jobUrl('stream', job.id);
        if (!url) return;

        const sse = new EventSource(url);
        job.sse = sse;

        const EVENTS = ['import.queued', 'import.parsing', 'import.staged', 'import.applying', 'import.done', 'import.partial', 'import.failed'];
        EVENTS.forEach((name) => {
            sse.addEventListener(name, (e) => {
                let data = {};
                try { data = JSON.parse(e.data); } catch (_) {}
                applyEvent(job, name.replace('import.', ''), data);
            });
        });
        // Fallback for unnamed messages carrying a {status} field.
        sse.addEventListener('message', (e) => {
            let data = {};
            try { data = JSON.parse(e.data); } catch (_) { return; }
            if (data.status) applyEvent(job, data.status, data);
        });

        sse.onerror = () => {
            // EventSource auto-reconnects; once the job is terminal we stop.
            if (isTerminal(job.status)) closeJobSSE(job);
        };
    }

    function closeJobSSE(job) {
        if (job.sse) { job.sse.close(); job.sse = null; }
    }

    // --- upload ---
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.hidden = true;

        const fileInput = form.querySelector('input[type=file]');
        if (!fileInput.files.length) {
            formError.textContent = t('select_file');
            formError.hidden = false;
            return;
        }

        const fd = new FormData();
        fd.append('catalog_file', fileInput.files[0]);
        fd.append('auto_apply', form.querySelector('input[name=auto_apply]').checked ? '1' : '0');

        const filename = fileInput.files[0].name;
        const format = /\.csv$|\.txt$/i.test(filename) ? 'csv' : 'xlsx';
        const autoApply = form.querySelector('input[name=auto_apply]').checked;

        submitBtn.disabled = true;
        const original = submitBtn.textContent;
        submitBtn.textContent = t('uploading');

        try {
            const res = await fetch(routes.enqueue, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: fd,
            });

            if (!res.ok) {
                formError.textContent = await apiError(res);
                formError.hidden = false;
                return;
            }

            const { job } = await res.json();
            const id = job.job_id || job.id;
            const entry = { id, status: job.status || 'queued', filename, format, autoApply, summary: null, counts: {}, concurrent: [], sse: null };
            jobs.set(id, entry);
            renderJob(entry);
            connectJobSSE(entry);

            form.reset();
        } catch (_) {
            formError.textContent = t('load_error');
            formError.hidden = false;
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = original;
        }
    });

    // --- mid-flight auto-apply toggle ---
    async function toggleAutoApply(job, value) {
        try {
            const res = await fetch(jobUrl('autoApply', job.id), {
                method: 'POST',
                headers: jsonHeaders,
                body: JSON.stringify({ auto_apply: value }),
            });
            if (res.ok) {
                const data = await res.json().catch(() => ({}));
                job.autoApply = value;
                if (data.job?.status) applyEvent(job, data.job.status, data.job);
            }
        } catch (_) { /* leave the checkbox; the next SSE state reconciles it */ }
    }

    // --- discard / dismiss ---
    async function discardJob(job, fromReview) {
        const msg = (job.status === 'partial' || job.status === 'failed') ? t('confirm_dismiss') : t('confirm_discard');
        if (!window.confirm(msg)) return;

        try {
            const res = await fetch(jobUrl('discard', job.id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (res.ok) {
                closeJobSSE(job);
                if (job.status === 'partial' || job.status === 'failed') {
                    job.status = 'discarded';
                    renderJob(job);
                } else {
                    removeJobCard(job.id);
                    jobs.delete(job.id);
                }
                if (fromReview) closeReview();
                loadHistory(true);
            }
        } catch (_) { /* swallow — user can retry */ }
    }

    // -----------------------------------------------------------------------
    // Review drawer — conflicts grouped by entity + error / failed rows
    // -----------------------------------------------------------------------
    let reviewDecisions = new Map(); // conflict_id -> 'create' | 'skip'

    async function openReview(id) {
        let data;
        try {
            const res = await fetch(jobUrl('staged', id), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error();
            data = await res.json();
        } catch (_) {
            reviewEl.hidden = false;
            reviewEl.innerHTML = `<div class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${esc(t('load_error'))}</div>`;
            return;
        }
        renderReview(id, data);
    }

    function closeReview() {
        reviewEl.hidden = true;
        reviewEl.innerHTML = '';
        reviewDecisions = new Map();
    }

    function renderReview(id, data) {
        const job = jobs.get(id) || { id, status: data.job?.status || 'staged' };
        const conflicts = data.conflicts || [];
        const errorRows = data.error_rows || [];
        const failedRows = data.failed_rows || [];
        const okCount = data.job?.ok_count ?? job.summary?.ok_count ?? data.ok_count ?? 0;
        const isFailedView = job.status === 'partial' || job.status === 'failed';

        reviewDecisions = new Map();
        conflicts.forEach((c) => {
            const cid = c.conflict_id || c.id;
            reviewDecisions.set(cid, c.decision && c.decision !== 'pending' ? c.decision : null);
        });

        reviewEl.hidden = false;
        reviewEl.innerHTML = `
            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">${esc(t('review_title'))}</h2>
                    <button type="button" data-review-close class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="${esc(t('close_button'))}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                ${(job.concurrent?.length > 0) ? `<p class="border-b border-amber-100 bg-amber-50 px-5 py-3 text-xs text-amber-800">${esc(t('concurrent_warning'))}</p>` : ''}

                <div class="divide-y divide-gray-100">
                    <div class="px-5 py-3 text-sm text-gray-600">${esc(t('ok_rows', { count: okCount }))}</div>

                    ${!isFailedView && conflicts.length ? `
                        <div class="px-5 py-4">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">${esc(t('section_conflicts'))}</p>
                                <div class="flex gap-2">
                                    <button type="button" data-decide-all="create" class="rounded border border-gray-300 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">${esc(t('decide_all_create'))}</button>
                                    <button type="button" data-decide-all="skip" class="rounded border border-gray-300 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">${esc(t('decide_all_skip'))}</button>
                                </div>
                            </div>
                            <div class="space-y-2">${conflicts.map(renderConflictCard).join('')}</div>
                        </div>` : ''}

                    ${errorRows.length ? `
                        <details class="px-5 py-4">
                            <summary class="cursor-pointer text-xs font-semibold uppercase tracking-wide text-gray-500">${esc(t('section_errors'))} (${errorRows.length})</summary>
                            <ul class="mt-2 space-y-1">${errorRows.map(renderIssueRow).join('')}</ul>
                        </details>` : ''}

                    ${isFailedView ? `
                        <div class="px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">${esc(t('section_failed'))} (${failedRows.length})</p>
                            <p class="mt-1 text-xs text-gray-500">${esc(t('failed_hint'))}</p>
                            <ul class="mt-2 space-y-1">${failedRows.map(renderIssueRow).join('')}</ul>
                        </div>` : ''}
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                    <span data-review-summary class="text-sm text-gray-600"></span>
                    <div class="flex gap-2">
                        ${isFailedView
                            ? `<button type="button" data-review-dismiss class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">${esc(t('dismiss_button'))}</button>
                               <button type="button" data-review-retry class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-500">${esc(t('retry_button'))}</button>`
                            : `<button type="button" data-review-discard class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">${esc(t('discard_button'))}</button>
                               <button type="button" data-review-apply class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">${esc(t('apply_button'))}</button>`}
                    </div>
                </div>
            </div>`;

        // wire controls
        reviewEl.querySelector('[data-review-close]')?.addEventListener('click', closeReview);
        reviewEl.querySelectorAll('[data-conflict-id]').forEach((cardEl) => {
            cardEl.querySelectorAll('[data-decision]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    reviewDecisions.set(cardEl.dataset.conflictId, btn.dataset.decision);
                    paintConflict(cardEl);
                    refreshSummary();
                });
            });
        });
        reviewEl.querySelectorAll('[data-decide-all]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const decision = btn.dataset.decideAll;
                reviewEl.querySelectorAll('[data-conflict-id]').forEach((cardEl) => {
                    reviewDecisions.set(cardEl.dataset.conflictId, decision);
                    paintConflict(cardEl);
                });
                refreshSummary();
            });
        });
        reviewEl.querySelector('[data-review-apply]')?.addEventListener('click', () => applyImport(job));
        reviewEl.querySelector('[data-review-discard]')?.addEventListener('click', () => discardJob(jobs.get(id) || job, true));
        reviewEl.querySelector('[data-review-retry]')?.addEventListener('click', () => retryImport(jobs.get(id) || job));
        reviewEl.querySelector('[data-review-dismiss]')?.addEventListener('click', () => discardJob(jobs.get(id) || job, true));

        reviewEl.querySelectorAll('[data-conflict-id]').forEach(paintConflict);
        refreshSummary();
        reviewEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function renderConflictCard(c) {
        const cid = c.conflict_id || c.id;
        const typeLabel = c.conflict_type === 'new_price_tier' ? t('conflict_price_tier') : t('conflict_warehouse');
        return `
            <div data-conflict-id="${esc(cid)}" class="rounded-lg border border-gray-200 px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><span class="text-gray-500">${esc(typeLabel)}:</span> ${esc(c.conflict_key)}</p>
                        <p class="text-xs text-gray-500">${esc(t('conflict_affects', { count: c.affected_row_count ?? 0 }))}</p>
                    </div>
                    <div class="inline-flex overflow-hidden rounded-lg border border-gray-300">
                        <button type="button" data-decision="create" class="px-3 py-1.5 text-xs font-medium">${esc(t('decide_create'))}</button>
                        <button type="button" data-decision="skip" class="border-l border-gray-300 px-3 py-1.5 text-xs font-medium">${esc(t('decide_skip'))}</button>
                    </div>
                </div>
                <p data-create-note hidden class="mt-2 text-xs text-gray-400">${esc(t('create_note'))}</p>
            </div>`;
    }

    function renderIssueRow(r) {
        return `<li class="text-xs text-gray-600"><span class="font-medium text-gray-700">${esc(t('error_row', { row: r.row_number ?? '—' }))}:</span> ${esc(r.error_message || '')}</li>`;
    }

    function paintConflict(cardEl) {
        const decision = reviewDecisions.get(cardEl.dataset.conflictId);
        cardEl.querySelectorAll('[data-decision]').forEach((btn) => {
            const active = btn.dataset.decision === decision;
            btn.classList.toggle('bg-indigo-600', active && decision === 'create');
            btn.classList.toggle('bg-gray-600', active && decision === 'skip');
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('text-gray-600', !active);
        });
        const note = cardEl.querySelector('[data-create-note]');
        if (note) note.hidden = decision !== 'create';
    }

    function refreshSummary() {
        const summaryEl = reviewEl.querySelector('[data-review-summary]');
        const applyBtn = reviewEl.querySelector('[data-review-apply]');
        if (!summaryEl) return;
        let unresolved = 0;
        reviewDecisions.forEach((d) => { if (!d) unresolved++; });
        summaryEl.textContent = unresolved === 0 ? t('all_resolved') : t('unresolved', { count: unresolved });
        if (applyBtn) applyBtn.disabled = unresolved > 0;
    }

    async function applyImport(job) {
        // Persist all decisions, then trigger the explicit apply.
        const decisions = [];
        reviewDecisions.forEach((decision, conflict_id) => { if (decision) decisions.push({ conflict_id, decision }); });

        try {
            if (decisions.length) {
                const dres = await fetch(jobUrl('decisions', job.id), {
                    method: 'POST',
                    headers: jsonHeaders,
                    body: JSON.stringify({ decisions }),
                });
                if (!dres.ok) return;
            }
            const res = await fetch(jobUrl('apply', job.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const data = await res.json().catch(() => ({}));
                const tracked = jobs.get(job.id) || job;
                applyEvent(tracked, data.job?.status || 'applying', data.job);
                if (!tracked.sse) connectJobSSE(tracked);
                closeReview();
            }
        } catch (_) { /* user can retry */ }
    }

    async function retryImport(job) {
        try {
            const res = await fetch(jobUrl('retry', job.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const data = await res.json().catch(() => ({}));
                const tracked = jobs.get(job.id) || { ...job, counts: {}, summary: null, concurrent: [] };
                jobs.set(job.id, tracked);
                applyEvent(tracked, data.job?.status || 'applying', data.job);
                if (!tracked.sse) connectJobSSE(tracked);
                closeReview();
            }
        } catch (_) { /* user can retry */ }
    }

    // -----------------------------------------------------------------------
    // History
    // -----------------------------------------------------------------------
    let historyOpen = false;

    histToggle?.addEventListener('click', () => {
        historyOpen = !historyOpen;
        histEl.hidden = !historyOpen;
        if (histChevron) histChevron.style.transform = historyOpen ? 'rotate(90deg)' : '';
        if (historyOpen) loadHistory(true);
    });

    async function loadHistory(force) {
        if (!historyOpen && !force) return;
        if (!historyOpen) return; // only fetch when visible
        try {
            const res = await fetch(routes.history, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error();
            const { jobs: rows } = await res.json();
            renderHistory(rows || []);
        } catch (_) {
            histEl.innerHTML = `<p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${esc(t('load_error'))}</p>`;
        }
    }

    function renderHistory(rows) {
        if (!rows.length) {
            histEl.innerHTML = `<p class="rounded-xl border border-gray-200 bg-white px-5 py-6 text-center text-sm text-gray-500">${esc(t('history_empty'))}</p>`;
            return;
        }

        const body = rows.map((r) => {
            const id = r.job_id || r.id;
            const status = r.status || '';
            const fmt = (r.format || '').toLowerCase() === 'csv' ? t('format_csv') : t('format_xlsx');
            const counts = [
                `${r.imported_count ?? 0} ${t('count_imported')}`,
                `${r.updated_count ?? 0} ${t('count_updated')}`,
                `${r.skipped_count ?? 0} ${t('count_skipped')}`,
                (r.failed_count ? `<span class="text-red-600">${r.failed_count} ${t('count_failed')}</span>` : ''),
            ].filter(Boolean).join(' · ');
            let date = r.created_at || '';
            try { date = new Date(r.created_at).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }); } catch (_) {}

            let action = '';
            if (status === 'staged') action = `<button type="button" data-history-resume="${esc(id)}" class="rounded border border-amber-300 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 hover:bg-amber-100">${esc(t('resume_button'))}</button>`;
            else if (status === 'partial' || status === 'failed') action = `<button type="button" data-history-retry="${esc(id)}" class="rounded border border-orange-300 bg-orange-50 px-2 py-1 text-xs font-medium text-orange-800 hover:bg-orange-100">${esc(t('retry_button'))}</button>`;

            return `
                <tr class="border-b border-gray-100 last:border-b-0">
                    <td class="py-2.5 pr-3">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-sm text-gray-800">${esc(r.original_filename || '—')}</span>
                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-gray-500">${esc(fmt)}</span>
                        </div>
                    </td>
                    <td class="py-2.5 pr-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[status] || 'bg-gray-100 text-gray-600'}">${esc(statusLabel(status))}</span></td>
                    <td class="py-2.5 pr-3 text-xs text-gray-500">${counts}</td>
                    <td class="py-2.5 pr-3 text-xs text-gray-500">${esc(r.uploaded_by || '—')}</td>
                    <td class="py-2.5 pr-3 text-xs text-gray-400">${esc(date)}</td>
                    <td class="py-2.5 text-right">${action}</td>
                </tr>`;
        }).join('');

        histEl.innerHTML = `
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-medium uppercase tracking-wide text-gray-400">
                            <th class="px-3 py-2 font-medium">${esc(t('history_col_file'))}</th>
                            <th class="px-3 py-2 font-medium">${esc(t('history_col_status'))}</th>
                            <th class="px-3 py-2 font-medium">${esc(t('history_col_result'))}</th>
                            <th class="px-3 py-2 font-medium">${esc(t('history_col_by'))}</th>
                            <th class="px-3 py-2 font-medium">${esc(t('history_col_date'))}</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>${body}</tbody>
                </table>
            </div>`;

        histEl.querySelectorAll('[data-history-resume]').forEach((btn) =>
            btn.addEventListener('click', () => openReview(btn.dataset.historyResume)));
        histEl.querySelectorAll('[data-history-retry]').forEach((btn) =>
            btn.addEventListener('click', () => openReview(btn.dataset.historyRetry)));
    }
}

document.addEventListener('DOMContentLoaded', initCatalogImport);

// ---------------------------------------------------------------------------
// CS feature-assistant widget (homepage + in-app). A floating launcher + chat
// panel that asks the tekomata-owned assistant about features/pricing via the
// same-origin /cs/ask proxy (which attaches the session JWT in-app). Synchronous
// request/response — no polling. Progressive enhancement over x-cs-widget.
// ---------------------------------------------------------------------------
function initCsWidget() {
    const root = document.querySelector('[data-cs-widget]');
    if (!root) return;

    const panel    = root.querySelector('[data-cs-panel]');
    const toggle   = root.querySelector('[data-cs-toggle]');
    const closeBtn = root.querySelector('[data-cs-close]');
    const iconOpen = root.querySelector('[data-cs-icon-open]');
    const iconClose = root.querySelector('[data-cs-icon-close]');
    const messages = root.querySelector('[data-cs-messages]');
    const form     = root.querySelector('[data-cs-form]');
    const input    = root.querySelector('[data-cs-input]');
    const sendBtn  = root.querySelector('[data-cs-send]');
    if (!panel || !toggle || !form || !input || !messages) return;

    const surface = root.dataset.csSurface || 'homepage';
    const askUrl  = root.dataset.csAskUrl;
    const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';

    const setOpen = (open) => {
        panel.hidden = !open;
        if (iconOpen) iconOpen.hidden = open;
        if (iconClose) iconClose.hidden = !open;
        if (open) input.focus();
    };
    toggle.addEventListener('click', () => setOpen(panel.hidden));
    closeBtn?.addEventListener('click', () => setOpen(false));

    const scrollDown = () => { messages.scrollTop = messages.scrollHeight; };

    // Always build bubbles via textContent — never inject answer HTML.
    const bubble = (text, who) => {
        const wrap = document.createElement('div');
        wrap.className = who === 'user' ? 'flex justify-end' : 'flex';
        const b = document.createElement('div');
        b.className = who === 'user'
            ? 'max-w-[85%] rounded-2xl rounded-tr-sm bg-indigo-600 px-3.5 py-2 text-sm text-white'
            : 'max-w-[85%] whitespace-pre-line rounded-2xl rounded-tl-sm bg-gray-100 px-3.5 py-2 text-sm text-gray-800';
        b.textContent = text;
        wrap.appendChild(b);
        messages.appendChild(wrap);
        scrollDown();
        return b;
    };

    let busy = false;
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const question = input.value.trim();
        if (!question || busy) return;

        busy = true;
        if (sendBtn) sendBtn.disabled = true;
        bubble(question, 'user');
        input.value = '';

        // Placeholder "thinking…" bubble, replaced when the answer lands.
        const pending = bubble('…', 'bot');
        pending.classList.add('animate-pulse');

        try {
            const res = await fetch(askUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ question, surface }),
            });
            const data = res.ok ? await res.json() : {};
            pending.classList.remove('animate-pulse');
            pending.textContent = (data && typeof data.answer === 'string' && data.answer)
                ? data.answer
                : (root.dataset.csErrorText || 'Sorry, something went wrong. Please try again.');
        } catch {
            pending.classList.remove('animate-pulse');
            pending.textContent = root.dataset.csErrorText || 'Sorry, something went wrong. Please try again.';
        } finally {
            busy = false;
            if (sendBtn) sendBtn.disabled = false;
            input.focus();
        }
    });
}

document.addEventListener('DOMContentLoaded', initCsWidget);

// ---------------------------------------------------------------------------
// Product media manager (resources/views/products/partials/media-manager.blade.php)
//
// Photos (view-tagged) + videos, one thumbnail, drag-to-reorder. Every call
// goes same-origin to ProductMediaController (JWT attached server-side, never
// here). Mutations re-fetch the gallery so the UI always reflects server truth.
// Routes + strings come from the embedded JSON config — nothing hard-coded.
// ---------------------------------------------------------------------------
function initProductMedia() {
    const root = document.querySelector('[data-product-media]');
    if (!root) return;

    let config;
    try {
        config = JSON.parse(root.querySelector('[data-product-media-config]').textContent);
    } catch (_) {
        return;
    }

    const routes = config.routes || {};
    const i18n   = config.i18n || {};
    const csrf   = config.csrf;

    const t = (key) => i18n[key] ?? key;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const mediaUrl = (name, id) => (routes[name] || '').replace('__ID__', encodeURIComponent(id));
    const sized = (url) => url + (url.includes('?') ? '&' : '?') + 'size=' + (config.thumbSize || 300);

    const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    const VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];
    const PHOTO_MAX = 20 * 1024 * 1024;
    const VIDEO_MAX = 100 * 1024 * 1024;

    async function apiError(res) {
        try {
            const d = await res.json();
            return d?.error || Object.values(d?.errors || {})[0]?.[0] || t('generic_error');
        } catch (_) {
            return t('generic_error');
        }
    }

    // --- elements ---
    const errorEl   = root.querySelector('[data-pm-error]');
    const form      = root.querySelector('[data-pm-form]');
    const fileInput = root.querySelector('[data-pm-file]');
    const viewWrap  = root.querySelector('[data-pm-view-wrap]');
    const viewSel   = root.querySelector('[data-pm-view]');
    const thumbChk  = root.querySelector('[data-pm-thumbnail]');
    const rulesEl   = root.querySelector('[data-pm-rules]');
    const submitBtn = root.querySelector('[data-pm-submit]');
    const grid      = root.querySelector('[data-pm-grid]');
    const emptyEl   = root.querySelector('[data-pm-empty]');
    const dragHint  = root.querySelector('[data-pm-drag-hint]');
    const kindBtns  = root.querySelectorAll('[data-pm-kind]');

    let kind = 'photo';

    const showError = (msg) => { errorEl.textContent = msg; errorEl.hidden = false; };
    const clearError = () => { errorEl.hidden = true; errorEl.textContent = ''; };

    // --- photo / video toggle ---
    function selectKind(k) {
        kind = k;
        kindBtns.forEach((b) => {
            const active = b.dataset.pmKind === k;
            b.classList.toggle('bg-indigo-600', active);
            b.classList.toggle('text-white', active);
            b.classList.toggle('text-gray-700', !active);
        });
        viewWrap.hidden = k !== 'photo';
        rulesEl.textContent = k === 'photo' ? t('photo_rules') : t('video_rules');
        fileInput.accept = k === 'photo' ? PHOTO_TYPES.join(',') : VIDEO_TYPES.join(',');
        fileInput.value = '';
        clearError();
    }
    kindBtns.forEach((b) => b.addEventListener('click', () => selectKind(b.dataset.pmKind)));

    // --- gallery render ---
    function tile(item) {
        const isVideo = item.kind === 'video';
        const src = isVideo ? config.videoPlaceholder : sized(item.url);
        const badges = [];
        if (isVideo) {
            badges.push(`<span class="rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-medium text-white">${esc(t('video_badge'))}</span>`);
        } else if (item.view) {
            badges.push(`<span class="rounded bg-gray-900/70 px-1.5 py-0.5 text-[10px] font-medium text-white">${esc(t('view_' + item.view))}</span>`);
        }
        if (item.is_thumbnail) {
            badges.push(`<span class="rounded bg-indigo-600 px-1.5 py-0.5 text-[10px] font-medium text-white">${esc(t('thumbnail_badge'))}</span>`);
        }

        const thumbBtn = (!isVideo && !item.is_thumbnail)
            ? `<button type="button" data-pm-make-thumb class="rounded bg-white/90 px-1.5 py-0.5 text-[11px] font-medium text-indigo-700 shadow hover:bg-white">${esc(t('make_thumbnail'))}</button>`
            : '';

        const el = document.createElement('div');
        el.className = 'group relative aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-50';
        el.setAttribute('draggable', 'true');
        el.dataset.mediaId = item.id;
        el.innerHTML = `
            <img src="${esc(src)}" alt="" class="h-full w-full object-cover" draggable="false">
            <div class="pointer-events-none absolute left-1.5 top-1.5 flex flex-wrap gap-1">${badges.join('')}</div>
            <div class="absolute inset-x-1.5 bottom-1.5 flex items-center justify-between gap-1 opacity-0 transition group-hover:opacity-100">
                ${thumbBtn}
                <button type="button" data-pm-delete class="ml-auto rounded bg-white/90 px-1.5 py-0.5 text-[11px] font-medium text-red-600 shadow hover:bg-white">${esc(t('delete'))}</button>
            </div>`;
        return el;
    }

    function render(media) {
        grid.innerHTML = '';
        (media || []).forEach((item) => grid.appendChild(tile(item)));
        const has = (media || []).length > 0;
        emptyEl.hidden = has;
        dragHint.hidden = (media || []).length < 2;
    }

    async function loadGallery() {
        try {
            const res = await fetch(routes.list, { headers: { Accept: 'application/json' } });
            if (!res.ok) { showError(t('load_error')); return; }
            const data = await res.json();
            render(data?.data?.media || []);
        } catch (_) {
            showError(t('load_error'));
        }
    }

    // --- upload ---
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearError();

        const file = fileInput.files?.[0];
        if (!file) return;

        const rules = kind === 'photo' ? t('photo_rules') : t('video_rules');
        const allowed = kind === 'photo' ? PHOTO_TYPES : VIDEO_TYPES;
        if (!allowed.includes(file.type)) { showError(rules); return; }
        if (file.size > (kind === 'photo' ? PHOTO_MAX : VIDEO_MAX)) { showError(rules); return; }

        const fd = new FormData();
        fd.append('file', file);
        if (kind === 'photo') {
            if (!viewSel.value) { showError(t('view_required')); return; }
            fd.append('view', viewSel.value);
            if (thumbChk.checked) fd.append('is_thumbnail', '1');
        }

        submitBtn.disabled = true;
        const label = submitBtn.textContent;
        submitBtn.textContent = t('uploading');
        try {
            const res = await fetch(routes.store, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: fd,
            });
            if (!res.ok) { showError(await apiError(res)); return; }
            form.reset();
            thumbChk.checked = false;
            await loadGallery();
        } catch (_) {
            showError(t('generic_error'));
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = label;
        }
    });

    // --- tile actions (delegated) ---
    grid.addEventListener('click', async (e) => {
        const tileEl = e.target.closest('[data-media-id]');
        if (!tileEl) return;
        const id = tileEl.dataset.mediaId;

        if (e.target.closest('[data-pm-make-thumb]')) {
            clearError();
            try {
                const res = await fetch(mediaUrl('thumbnail', id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                });
                if (!res.ok) { showError(await apiError(res)); return; }
                await loadGallery();
            } catch (_) { showError(t('generic_error')); }
            return;
        }

        if (e.target.closest('[data-pm-delete]')) {
            if (!confirm(t('confirm_delete'))) return;
            clearError();
            try {
                const res = await fetch(mediaUrl('delete', id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                });
                if (!res.ok) { showError(await apiError(res)); return; }
                await loadGallery();
            } catch (_) { showError(t('generic_error')); }
        }
    });

    // --- drag to reorder ---
    let dragId = null;
    grid.addEventListener('dragstart', (e) => {
        const tileEl = e.target.closest('[data-media-id]');
        if (!tileEl) return;
        dragId = tileEl.dataset.mediaId;
        tileEl.classList.add('opacity-50');
    });
    grid.addEventListener('dragend', (e) => {
        e.target.closest('[data-media-id]')?.classList.remove('opacity-50');
    });
    grid.addEventListener('dragover', (e) => {
        e.preventDefault();
        const over = e.target.closest('[data-media-id]');
        const dragging = grid.querySelector(`[data-media-id="${dragId}"]`);
        if (!over || !dragging || over === dragging) return;
        const rect = over.getBoundingClientRect();
        const after = (e.clientY - rect.top) > rect.height / 2 || (e.clientX - rect.left) > rect.width / 2;
        grid.insertBefore(dragging, after ? over.nextSibling : over);
    });
    grid.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!dragId) return;
        dragId = null;
        const ids = [...grid.querySelectorAll('[data-media-id]')].map((el) => el.dataset.mediaId);
        clearError();
        try {
            const res = await fetch(routes.reorder, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ media_ids: ids }),
            });
            if (!res.ok) { showError(await apiError(res)); await loadGallery(); return; }
            const data = await res.json();
            render(data?.data?.media || []);
        } catch (_) {
            showError(t('generic_error'));
            await loadGallery();
        }
    });

    selectKind('photo');
    loadGallery();
}

document.addEventListener('DOMContentLoaded', initProductMedia);


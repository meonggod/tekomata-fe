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
        const direction = msg.direction || 'inbound';
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
            bubble.className = 'max-w-xs rounded-lg bg-indigo-600 px-4 py-2.5 sm:max-w-md';
            const header = document.createElement('div');
            header.className = 'flex items-center gap-2 text-xs text-indigo-200';
            const nameEl = document.createElement('span');
            nameEl.className = 'font-medium';
            nameEl.textContent = author;
            const timeEl = document.createElement('span');
            timeEl.textContent = msgTime;
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

            renderThread(data.conversation, data.messages || []);
            showThread();

            // Update URL without reload
            const newUrl = '/inbox/' + convId;
            if (window.location.pathname !== newUrl) {
                history.pushState({ convId }, '', newUrl);
            }
        } catch (e) {
            showToast(i18n.error_thread || e.message, true);
        }
    }

    // --- Render thread into the panel ---
    function renderThread(conv, messages) {
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

        // Scroll to bottom
        setTimeout(() => { messagesArea.scrollTop = messagesArea.scrollHeight; }, 0);

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
            history.pushState({}, '', '/inbox');
        }
    });

    // --- Reply form (delegated) ---
    threadPanel.addEventListener('submit', async (e) => {
        const form = e.target.closest('[data-inbox-reply-form]');
        if (!form) return;
        e.preventDefault();

        const bodyEl = form.querySelector('[data-inbox-reply-body]');
        const body = bodyEl?.value?.trim();
        if (!body || !activeConvId) return;

        const submitBtn = form.querySelector('[data-inbox-reply-submit]');
        if (submitBtn) submitBtn.disabled = true;

        const isNote = threadPanel.querySelector('[data-inbox-note-toggle]')?.checked;

        try {
            if (isNote) {
                const data = await apiFetch(url(tplNotes, activeConvId), {
                    method: 'POST',
                    body: JSON.stringify({ body }),
                });
                if (!data) return;
                const messagesArea = threadPanel.querySelector('[data-inbox-messages]');
                if (messagesArea && data.message) {
                    messagesArea.append(createMessageEl(data.message));
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                }
                showToast(i18n.note_success || 'Note added.', false);
            } else {
                const data = await apiFetch(url(tplReply, activeConvId), {
                    method: 'POST',
                    body: JSON.stringify({ body }),
                });
                if (!data) return;
                const messagesArea = threadPanel.querySelector('[data-inbox-messages]');
                if (messagesArea && data.message) {
                    messagesArea.append(createMessageEl(data.message));
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                }
                showToast(i18n.reply_success || 'Reply sent.', false);
            }
            bodyEl.value = '';
        } catch (err) {
            showToast(isNote ? (i18n.error_note || err.message) : (i18n.error_reply || err.message), true);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
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
            window.location.href = '/inbox' + (qs ? '?' + qs : '');
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
            const res = await fetch('/inbox?' + params.toString(), {
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
                const messagesArea = threadPanel.querySelector('[data-inbox-messages]');
                if (messagesArea) {
                    messagesArea.append(createMessageEl(data));
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                }
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
    function createTeamMessageEl(msg) {
        const body = msg.body || '';
        const authorId = msg.author_user_id || '';
        const authorName = msg.author_name || '';
        const isMine = authorId === currentUserId;
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

        if (isMine) {
            wrap.className += ' flex justify-end';
            const bubble = document.createElement('div');
            bubble.className = 'max-w-xs rounded-lg bg-indigo-600 px-4 py-2.5 sm:max-w-md';
            const header = document.createElement('div');
            header.className = 'flex items-center gap-2 text-xs text-indigo-200';
            const nameEl = document.createElement('span');
            nameEl.className = 'font-medium';
            nameEl.textContent = authorName;
            const timeEl = document.createElement('span');
            timeEl.textContent = msgTime;
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
            const newUrl = '/team/' + convId;
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

        messages.forEach((msg) => messagesArea.append(createTeamMessageEl(msg)));
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
            history.pushState({}, '', '/team');
        }
    });

    // --- Reply form (delegated) ---
    threadPanel.addEventListener('submit', async (e) => {
        const form = e.target.closest('[data-team-reply-form]');
        if (!form) return;
        e.preventDefault();

        const bodyEl = form.querySelector('[data-team-reply-body]');
        const body = bodyEl?.value?.trim();
        if (!body || !activeConvId) return;

        const submitBtn = form.querySelector('[data-team-reply-submit]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const data = await apiFetch(url(tplSend, activeConvId), {
                method: 'POST',
                body: JSON.stringify({ body }),
            });
            if (!data) return;
            const messagesArea = threadPanel.querySelector('[data-team-messages]');
            if (messagesArea && data.message) {
                messagesArea.append(createTeamMessageEl(data.message));
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
            bodyEl.value = '';
            showToast(i18n.reply_success || 'Message sent.', false);
        } catch (err) {
            showToast(i18n.error_send || err.message, true);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
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
                window.location.href = '/team/' + data.conversation.id;
            } else {
                window.location.href = '/team';
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

    // --- Initial state: if a thread was server-rendered, mark it active ---
    const prerendered = threadPanel.querySelector('[data-conversation-id]');
    if (prerendered) {
        activeConvId = prerendered.dataset.conversationId;
        showThread();
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
                const messagesArea = threadPanel.querySelector('[data-team-messages]');
                if (messagesArea) {
                    messagesArea.append(createTeamMessageEl(data));
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                }
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
}

document.addEventListener('DOMContentLoaded', initTeamChat);

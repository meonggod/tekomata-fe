/**
 * tekomata — embeddable web-chat widget
 *
 * Usage:
 *   <script src="https://yoursite.com/js/widget.js" data-site-key="COMPANY_ID"></script>
 *
 * Attributes:
 *   data-site-key  (required) — company ID / embed key
 *   data-api-url   (optional) — API base URL; defaults to the script's own origin
 */
(function () {
    'use strict';

    // -----------------------------------------------------------------------
    // Bootstrap: read config from the script tag
    // -----------------------------------------------------------------------
    var scriptTag = document.currentScript
        || (function () {
            var tags = document.getElementsByTagName('script');
            return tags[tags.length - 1];
        })();

    var SITE_KEY = scriptTag.getAttribute('data-site-key');
    if (!SITE_KEY) {
        console.warn('[tekomata] data-site-key is required');
        return;
    }

    var API_URL = scriptTag.getAttribute('data-api-url');
    if (!API_URL) {
        // Derive from script src origin
        try {
            var src = scriptTag.src || scriptTag.getAttribute('src') || '';
            var u = new URL(src);
            API_URL = u.origin;
        } catch (_) {
            API_URL = '';
        }
    }
    // Strip trailing slash
    API_URL = API_URL.replace(/\/+$/, '');

    var ENDPOINT = API_URL + '/api/v1/webhooks/web-chat';
    var LS_PREFIX = 'tekomata_';
    var POLL_INTERVAL = 5000;

    // -----------------------------------------------------------------------
    // Persistence helpers
    // -----------------------------------------------------------------------
    function lsKey(suffix) {
        return LS_PREFIX + suffix + '_' + SITE_KEY;
    }

    function lsGet(suffix) {
        try { return localStorage.getItem(lsKey(suffix)); } catch (_) { return null; }
    }

    function lsSet(suffix, value) {
        try { localStorage.setItem(lsKey(suffix), value); } catch (_) {}
    }

    function lsGetJSON(suffix) {
        try { return JSON.parse(localStorage.getItem(lsKey(suffix))); } catch (_) { return null; }
    }

    function lsSetJSON(suffix, value) {
        try { localStorage.setItem(lsKey(suffix), JSON.stringify(value)); } catch (_) {}
    }

    // -----------------------------------------------------------------------
    // Visitor identity
    // -----------------------------------------------------------------------
    function uuid4() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    var visitorId = lsGet('visitor') || uuid4();
    lsSet('visitor', visitorId);

    var visitorName = lsGet('name') || '';
    var messages = lsGetJSON('messages') || [];
    var lastMsgId = lsGet('last_msg_id') || '';

    // -----------------------------------------------------------------------
    // CSS — all inline, scoped under #tekomata-widget
    // -----------------------------------------------------------------------
    var CSS = (
        '#tekomata-widget,#tekomata-widget *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;line-height:1.4;}'
        + '#tekomata-widget{position:fixed;bottom:20px;right:20px;z-index:99999;}'
        + '#tkm-bubble{width:60px;height:60px;border-radius:50%;background:#4f46e5;color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.15);transition:transform .15s ease,box-shadow .15s ease;}'
        + '#tkm-bubble:hover{transform:scale(1.05);box-shadow:0 6px 16px rgba(0,0,0,.2);}'
        + '#tkm-bubble svg{width:28px;height:28px;}'
        + '#tkm-panel{display:none;position:fixed;bottom:90px;right:20px;width:380px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 110px);border-radius:16px;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,.18);flex-direction:column;overflow:hidden;z-index:99999;}'
        + '#tkm-panel.open{display:flex;}'
        + '#tkm-header{padding:16px 20px;background:#4f46e5;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}'
        + '#tkm-header h3{font-size:15px;font-weight:600;}'
        + '#tkm-close{background:none;border:none;color:#fff;cursor:pointer;padding:4px;border-radius:6px;display:flex;align-items:center;justify-content:center;}'
        + '#tkm-close:hover{background:rgba(255,255,255,.15);}'
        + '#tkm-close svg{width:20px;height:20px;}'
        + '#tkm-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:8px;}'
        + '.tkm-msg{max-width:80%;padding:10px 14px;border-radius:12px;font-size:13px;word-wrap:break-word;white-space:pre-wrap;}'
        + '.tkm-msg-visitor{align-self:flex-end;background:#4f46e5;color:#fff;border-bottom-right-radius:4px;}'
        + '.tkm-msg-agent{align-self:flex-start;background:#f3f4f6;color:#1f2937;border-bottom-left-radius:4px;}'
        + '.tkm-msg-name{font-size:11px;font-weight:600;margin-bottom:2px;opacity:.75;}'
        + '#tkm-name-form{padding:12px 16px;border-top:1px solid #e5e7eb;flex-shrink:0;}'
        + '#tkm-name-form input{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:13px;outline:none;transition:border-color .15s;}'
        + '#tkm-name-form input:focus{border-color:#4f46e5;}'
        + '#tkm-name-form p{font-size:12px;color:#6b7280;margin-bottom:6px;}'
        + '#tkm-composer{padding:12px 16px;border-top:1px solid #e5e7eb;display:flex;gap:8px;flex-shrink:0;align-items:flex-end;}'
        + '#tkm-composer textarea{flex:1;resize:none;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px;font-size:13px;min-height:40px;max-height:100px;outline:none;transition:border-color .15s;}'
        + '#tkm-composer textarea:focus{border-color:#4f46e5;}'
        + '#tkm-send{width:36px;height:36px;border-radius:50%;background:#4f46e5;color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;}'
        + '#tkm-send:hover{background:#4338ca;}'
        + '#tkm-send:disabled{opacity:.5;cursor:default;}'
        + '#tkm-send svg{width:18px;height:18px;}'
        + '@media(max-width:420px){#tkm-panel{bottom:0;right:0;width:100%;max-width:100%;height:100%;max-height:100%;border-radius:0;}#tekomata-widget #tkm-bubble{bottom:16px;right:16px;}}'
    );

    // -----------------------------------------------------------------------
    // Build DOM
    // -----------------------------------------------------------------------
    var root = document.createElement('div');
    root.id = 'tekomata-widget';

    var style = document.createElement('style');
    style.textContent = CSS;
    root.appendChild(style);

    // Chat bubble
    var bubble = document.createElement('button');
    bubble.id = 'tkm-bubble';
    bubble.setAttribute('aria-label', 'Open chat');
    bubble.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8Z"/></svg>';
    root.appendChild(bubble);

    // Chat panel
    var panel = document.createElement('div');
    panel.id = 'tkm-panel';

    // Header
    var header = document.createElement('div');
    header.id = 'tkm-header';
    var headerTitle = document.createElement('h3');
    headerTitle.textContent = 'Chat with us';
    var closeBtn = document.createElement('button');
    closeBtn.id = 'tkm-close';
    closeBtn.setAttribute('aria-label', 'Close chat');
    closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';
    header.appendChild(headerTitle);
    header.appendChild(closeBtn);
    panel.appendChild(header);

    // Messages area
    var messagesEl = document.createElement('div');
    messagesEl.id = 'tkm-messages';
    panel.appendChild(messagesEl);

    // Name input (shown before first message if no stored name)
    var nameForm = document.createElement('div');
    nameForm.id = 'tkm-name-form';
    var nameHint = document.createElement('p');
    nameHint.textContent = 'What should we call you?';
    var nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.placeholder = 'Your name';
    nameInput.setAttribute('autocomplete', 'name');
    nameForm.appendChild(nameHint);
    nameForm.appendChild(nameInput);
    if (visitorName) nameForm.style.display = 'none';
    panel.appendChild(nameForm);

    // Composer
    var composer = document.createElement('div');
    composer.id = 'tkm-composer';
    var textarea = document.createElement('textarea');
    textarea.rows = 1;
    textarea.placeholder = 'Type a message\u2026';
    var sendBtn = document.createElement('button');
    sendBtn.id = 'tkm-send';
    sendBtn.setAttribute('aria-label', 'Send message');
    sendBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 14-7-4 7 4 7-14-7Zm0 0h7.5"/></svg>';
    composer.appendChild(textarea);
    composer.appendChild(sendBtn);
    panel.appendChild(composer);

    root.appendChild(panel);
    document.body.appendChild(root);

    // -----------------------------------------------------------------------
    // Render helpers
    // -----------------------------------------------------------------------
    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendMessageEl(msg) {
        var wrap = document.createElement('div');
        wrap.className = 'tkm-msg ' + (msg.direction === 'visitor' ? 'tkm-msg-visitor' : 'tkm-msg-agent');

        if (msg.direction !== 'visitor' && msg.author_name) {
            var nameEl = document.createElement('div');
            nameEl.className = 'tkm-msg-name';
            nameEl.textContent = msg.author_name;
            wrap.appendChild(nameEl);
        }

        var bodyEl = document.createElement('span');
        bodyEl.textContent = msg.body;
        wrap.appendChild(bodyEl);

        messagesEl.appendChild(wrap);
        scrollToBottom();
    }

    // Render stored messages on open
    function renderStoredMessages() {
        messagesEl.innerHTML = '';
        for (var i = 0; i < messages.length; i++) {
            appendMessageEl(messages[i]);
        }
    }

    // -----------------------------------------------------------------------
    // API
    // -----------------------------------------------------------------------
    function sendMessage(body) {
        var clientMsgId = uuid4();
        var msg = {
            direction: 'visitor',
            body: body,
            client_message_id: clientMsgId,
            timestamp: Date.now()
        };

        // Optimistic UI
        messages.push(msg);
        lsSetJSON('messages', messages);
        appendMessageEl(msg);

        var payload = {
            site_key: SITE_KEY,
            visitor_id: visitorId,
            body: body,
            client_message_id: clientMsgId
        };
        if (visitorName) payload.name = visitorName;

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (res) {
            if (!res.ok) {
                console.warn('[tekomata] send failed:', res.status);
            }
            return res.json().catch(function () { return {}; });
        }).then(function (data) {
            // If server returns a message id, track it for polling
            if (data && data.message_id) {
                lastMsgId = data.message_id;
                lsSet('last_msg_id', lastMsgId);
            }
        }).catch(function (err) {
            console.warn('[tekomata] send error:', err);
        });
    }

    // -----------------------------------------------------------------------
    // Polling for replies
    // -----------------------------------------------------------------------
    var pollTimer = null;
    var pollFailed = false;

    function startPolling() {
        if (pollTimer || pollFailed) return;
        pollTimer = setInterval(poll, POLL_INTERVAL);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function poll() {
        var pollUrl = ENDPOINT + '?site_key=' + encodeURIComponent(SITE_KEY)
            + '&visitor_id=' + encodeURIComponent(visitorId);
        if (lastMsgId) {
            pollUrl += '&after=' + encodeURIComponent(lastMsgId);
        }

        fetch(pollUrl).then(function (res) {
            if (!res.ok) {
                // Endpoint not available yet — stop polling silently
                pollFailed = true;
                stopPolling();
                return null;
            }
            return res.json();
        }).then(function (data) {
            if (!data || !data.messages) return;
            var newMessages = data.messages;
            for (var i = 0; i < newMessages.length; i++) {
                var m = newMessages[i];
                // Avoid duplicates
                var isDupe = false;
                for (var j = 0; j < messages.length; j++) {
                    if (messages[j].client_message_id && messages[j].client_message_id === m.client_message_id) {
                        isDupe = true;
                        break;
                    }
                    if (m.id && messages[j].id === m.id) {
                        isDupe = true;
                        break;
                    }
                }
                if (!isDupe) {
                    var msg = {
                        direction: m.direction === 'inbound' ? 'visitor' : 'agent',
                        body: m.body || '',
                        author_name: m.author_name || '',
                        id: m.id || '',
                        timestamp: Date.now()
                    };
                    messages.push(msg);
                    appendMessageEl(msg);
                }
                if (m.id) {
                    lastMsgId = m.id;
                    lsSet('last_msg_id', lastMsgId);
                }
            }
            lsSetJSON('messages', messages);
        }).catch(function () {
            // Network error — stop polling silently
            pollFailed = true;
            stopPolling();
        });
    }

    // -----------------------------------------------------------------------
    // Event handlers
    // -----------------------------------------------------------------------
    var panelOpen = false;

    function openPanel() {
        panelOpen = true;
        panel.classList.add('open');
        renderStoredMessages();
        startPolling();
        textarea.focus();
    }

    function closePanel() {
        panelOpen = false;
        panel.classList.remove('open');
        stopPolling();
    }

    bubble.addEventListener('click', function () {
        if (panelOpen) {
            closePanel();
        } else {
            openPanel();
        }
    });

    closeBtn.addEventListener('click', function () {
        closePanel();
    });

    // Name input: press Enter to confirm
    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var name = nameInput.value.trim();
            if (name) {
                visitorName = name;
                lsSet('name', visitorName);
                nameForm.style.display = 'none';
                textarea.focus();
            }
        }
    });

    // Send on button click
    sendBtn.addEventListener('click', function () {
        submitMessage();
    });

    // Send on Enter (Shift+Enter for newline)
    textarea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            submitMessage();
        }
    });

    // Auto-resize textarea
    textarea.addEventListener('input', function () {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
    });

    function submitMessage() {
        // If name hasn't been given yet, require it first
        if (!visitorName) {
            var name = nameInput.value.trim();
            if (!name) {
                nameInput.focus();
                return;
            }
            visitorName = name;
            lsSet('name', visitorName);
            nameForm.style.display = 'none';
        }

        var body = textarea.value.trim();
        if (!body) return;

        textarea.value = '';
        textarea.style.height = 'auto';
        sendMessage(body);
    }
})();

(function () {
    'use strict';
    var config = window.nekokoMessagesRefresh;
    var root = document.querySelector('.nekoko-messages');
    if (!config) return;
    var busy = false;
    var pending = false;
    var stopped = false;
    var notice;

    function updateDots(unread) {
        document.querySelectorAll('.header-account__trigger:not(.header-account__login), .header-account__item--messages > a, .site-header .menu-toggle').forEach(function (target) {
            var dot = target.querySelector('.nekoko-unread-dot');
            if (unread > 0 && !dot) {
                dot = document.createElement('span');
                dot.className = 'nekoko-unread-dot';
                var label = document.createElement('span');
                label.className = 'screen-reader-text';
                label.textContent = 'Nepročitane poruke';
                dot.appendChild(label);
                target.appendChild(dot);
            } else if (!unread && dot) {
                dot.remove();
            }
        });
    }

    function editing() {
        return Array.prototype.some.call(root.querySelectorAll('textarea'), function (field) {
            return field.value.length > 0 || document.activeElement === field;
        });
    }

    function refresh() {
        if (!root || !pending || document.hidden || stopped) return;
        if (!editing()) {
            stopped = true;
            window.location.reload();
            return;
        }
        if (!notice) {
            notice = document.createElement('p');
            notice.className = 'nekoko-messages__refresh-notice';
            notice.setAttribute('role', 'status');
            notice.textContent = 'Razgovori su ažurirani. Stranica će se osvežiti kada završite pisanje poruke.';
            root.insertBefore(notice, root.firstChild);
        }
    }

    function check() {
        if (busy || document.hidden || stopped) return;
        busy = true;
        var controller = new AbortController();
        var timeout = window.setTimeout(function () { controller.abort(); }, 10000);
        var body = new URLSearchParams({action: 'nekoko_messages_state', nonce: config.nonce});
        fetch(config.url, {
            method: 'POST', credentials: 'same-origin', cache: 'no-store',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString(), signal: controller.signal
        }).then(function (response) {
            if (response.status === 401 || response.status === 403 || response.status === 400) stopped = true;
            if (!response.ok) throw new Error('refresh-failed');
            return response.json();
        }).then(function (result) {
            if (result.success && result.data) {
                updateDots(Number(result.data.unread) || 0);
                if (root && result.data.state !== config.state) {
                    pending = true;
                    refresh();
                }
            }
        }).catch(function () {
            // Temporary connection failures are retried on the next interval.
        }).finally(function () {
            window.clearTimeout(timeout);
            busy = false;
        });
    }

    window.setInterval(check, 20000);
    document.addEventListener('visibilitychange', check);
    window.addEventListener('focus', check);
    if (root) {
        root.addEventListener('input', function () { if (pending) refresh(); });
        root.addEventListener('focusout', function () { window.setTimeout(refresh, 0); });
        root.addEventListener('submit', function () { stopped = true; });
    }
    check();
}());

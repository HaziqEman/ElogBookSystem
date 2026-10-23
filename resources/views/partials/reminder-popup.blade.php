@php $reminderStudentId = \Illuminate\Support\Facades\Auth::guard('student')->id(); @endphp

<div id="reminder-popup" data-student="{{ $reminderStudentId }}" class="mb-5 hidden" role="region" aria-label="Reminders" aria-live="polite"></div>

<style>
    @keyframes reminderIn { from { opacity: 0; transform: translateY(-14px); } to { opacity: 1; transform: none; } }
    .reminder-in { animation: reminderIn .3s ease-out; }
    @media (prefers-reduced-motion: reduce) { .reminder-in { animation: none; } }
</style>

<script>
(function () {
    if (window.__reminderPopup) return;
    window.__reminderPopup = true;

    var box = document.getElementById('reminder-popup');
    if (!box) return;

    var storeKey = 'elogbook.dismissed.' + box.getAttribute('data-student');
    var items = [];
    var today = '';
    var expanded = false;
    var lastSignature = '';
    var timer = null;

    var ICONS = {
        attendance: 'fa-clock', clockout: 'fa-right-from-bracket', logbook: 'fa-file-pen',
        missing: 'fa-file-circle-exclamation', revision: 'fa-rotate-left', overdue: 'fa-triangle-exclamation',
        today: 'fa-hourglass-half', soon: 'fa-list-check', assigned: 'fa-building-user'
    };

    var LEVELS = {
        high: { row: 'border-rose-200 bg-rose-50', icon: 'bg-rose-100 text-rose-700', button: 'bg-rose-600 hover:bg-rose-700 text-white' },
        medium: { row: 'border-amber-200 bg-amber-50', icon: 'bg-amber-100 text-amber-700', button: 'bg-amber-600 hover:bg-amber-700 text-white' },
        info: { row: 'border-blue-200 bg-blue-50', icon: 'bg-blue-100 text-blue-700', button: 'bg-blue-600 hover:bg-blue-700 text-white' }
    };

    // Dismissed reminders: { key: 'forever' | 'YYYY-MM-DD' }. A changed situation has a new key, so it shows again.
    function loadDismissed() {
        try { return JSON.parse(localStorage.getItem(storeKey) || '{}') || {}; } catch (e) { return {}; }
    }

    function saveDismissed(map) {
        try { localStorage.setItem(storeKey, JSON.stringify(map)); } catch (e) {}
    }

    function isHidden(item) {
        var value = loadDismissed()[item.key];

        return value === 'forever' || (!!today && value === today);
    }

    function dismiss(keys) {
        var map = loadDismissed();

        items.forEach(function (item) {
            if (keys.indexOf(item.key) !== -1) map[item.key] = item.snooze === 'forever' ? 'forever' : today;
        });

        // Forget old daily dismissals.
        Object.keys(map).forEach(function (key) {
            if (map[key] !== 'forever' && map[key] !== today) delete map[key];
        });

        saveDismissed(map);
        render();
    }

    function el(tag, cls, text) {
        var node = document.createElement(tag);

        if (cls) node.className = cls;
        if (text !== undefined && text !== null) node.textContent = text;

        return node;
    }

    function row(item) {
        var level = LEVELS[item.level] || LEVELS.info;
        var wrap = el('div', 'flex flex-wrap items-center gap-3 rounded-2xl border p-3 ' + level.row);

        var icon = el('span', 'flex h-9 w-9 flex-none items-center justify-center rounded-xl ' + level.icon);
        icon.appendChild(el('i', 'fa-solid ' + (ICONS[item.kind] || 'fa-bell')));
        wrap.appendChild(icon);

        var text = el('div', 'min-w-0 flex-[1_1_220px]');
        text.appendChild(el('p', 'break-words text-sm font-extrabold text-[#0F1B4D]', item.title));
        if (item.text) text.appendChild(el('p', 'break-words text-xs text-[#5B6478]', item.text));
        wrap.appendChild(text);

        var actions = el('div', 'flex items-center gap-1.5');

        if (item.url && item.action) {
            var link = el('a', 'flex min-h-9 items-center rounded-xl px-3 text-sm font-bold transition ' + level.button, item.action);
            link.href = item.url;
            link.setAttribute('data-reminder-action', item.key);
            actions.appendChild(link);
        }

        var close = el('button', 'flex h-9 w-9 items-center justify-center rounded-lg text-[#5B6478] transition hover:bg-white/70');
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss this reminder');
        close.setAttribute('data-dismiss', item.key);
        close.appendChild(el('i', 'fa-solid fa-xmark'));
        actions.appendChild(close);

        wrap.appendChild(actions);

        return wrap;
    }

    function render() {
        var visible = items.filter(function (item) { return !isHidden(item); });

        if (!visible.length) {
            box.classList.add('hidden');
            box.textContent = '';
            lastSignature = '';
            return;
        }

        var signature = visible.map(function (item) { return item.key; }).join('|');
        var shown = expanded ? visible : visible.slice(0, 3);

        var card = el('section', 'overflow-hidden rounded-[22px] border border-[#FBCFCB] bg-gradient-to-r from-[#FFE9E7] to-[#FFF3F0] shadow-[0_6px_18px_rgba(15,27,77,0.08)]');
        if (signature !== lastSignature) card.className += ' reminder-in';
        lastSignature = signature;

        var head = el('div', 'flex flex-wrap items-center justify-between gap-2 px-4 py-3');
        var title = el('div', 'flex flex-wrap items-center gap-2');
        var bell = el('span', 'flex h-8 w-8 items-center justify-center rounded-full bg-[#FDBA3B]/30 text-[#D97706]');
        bell.appendChild(el('i', 'fa-solid fa-bell'));
        title.appendChild(bell);
        title.appendChild(el('span', 'rounded-full bg-[#BE123C] px-2.5 py-0.5 text-[10px] font-extrabold tracking-wider text-white', 'REMINDER'));
        title.appendChild(el('span', 'text-sm font-extrabold text-[#0F1B4D]', visible.length === 1 ? '1 thing needs your attention' : visible.length + ' things need your attention'));
        head.appendChild(title);

        var all = el('button', 'rounded-lg px-3 py-1.5 text-xs font-bold text-[#5B6478] transition hover:bg-white/70', 'Dismiss all');
        all.type = 'button';
        all.setAttribute('data-dismiss-all', '1');
        head.appendChild(all);
        card.appendChild(head);

        var list = el('div', 'space-y-2 px-3 pb-3');
        shown.forEach(function (item) { list.appendChild(row(item)); });
        card.appendChild(list);

        if (visible.length > 3) {
            var more = el('button', 'mb-3 ml-4 text-xs font-bold text-[#1F45D6] hover:underline', expanded ? 'Show less' : 'Show ' + (visible.length - 3) + ' more');
            more.type = 'button';
            more.setAttribute('data-toggle-more', '1');
            card.appendChild(more);
        }

        box.textContent = '';
        box.appendChild(card);
        box.classList.remove('hidden');
    }

    function load() {
        fetch('/student/reminders', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (data) {
                if (!data) return;

                items = data.items || [];
                today = data.today || '';
                render();
            })
            .catch(function () {});
    }

    function soon() {
        clearTimeout(timer);
        timer = setTimeout(load, 400);
    }

    window.refreshReminders = load;

    box.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;

        var one = target.closest('[data-dismiss]');

        if (one) {
            dismiss([one.getAttribute('data-dismiss')]);
            return;
        }

        if (target.closest('[data-dismiss-all]')) {
            dismiss(items.filter(function (item) { return !isHidden(item); }).map(function (item) { return item.key; }));
            return;
        }

        if (target.closest('[data-toggle-more]')) {
            expanded = !expanded;
            render();
            return;
        }

        var link = target.closest('a[data-reminder-action]');

        if (link) {
            var url = new URL(link.href, window.location.href);

            // Already on that page: open the right part without reloading.
            if (url.pathname === window.location.pathname) {
                event.preventDefault();

                if (url.hash === '#new-entry' && typeof window.openNewEntry === 'function') {
                    window.openNewEntry(null, url.searchParams.get('write'));
                } else if (url.searchParams.get('week') && typeof window.refreshLive === 'function') {
                    window.refreshLive(url.href).then(function (ok) {
                        if (ok) window.history.pushState({ live: true }, '', url.href);

                        var section = document.getElementById('logbook-week');
                        if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                } else if (url.hash) {
                    var node = document.getElementById(url.hash.slice(1));
                    if (node) node.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
    });

    // When a reminder is checked: on load, on coming back to the tab, every 5 minutes, and after any action.
    document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
    window.addEventListener('focus', soon);
    window.addEventListener('pageshow', soon);
    document.addEventListener('async:success', soon);
    document.addEventListener('async:swapped', soon);
    setInterval(load, 300000);

    load();
})();
</script>
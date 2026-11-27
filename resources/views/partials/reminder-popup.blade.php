<div id="reminder-popup" data-student="{{ $reminderStudentId }}" class="hidden" role="region" aria-label="Student reminders" aria-live="polite"></div>

<style>
    #reminder-popup {
        position: fixed;
        z-index: 70;
        top: calc(env(safe-area-inset-top, 0px) + 6rem);
        right: max(1rem, env(safe-area-inset-right, 0px));
        width: min(390px, calc(100vw - 2rem));
        pointer-events: none;
    }

    #reminder-popup > * {
        pointer-events: auto;
    }

    #reminder-popup.is-minimized {
        top: 50%;
        right: 0;
        width: auto;
        transform: translateY(-50%);
    }

    @keyframes reminderIn {
        from { opacity: 0; transform: translateY(-10px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes reminderPanelIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .reminder-in { animation: reminderIn .24s ease-out; }
    .reminder-panel-in { animation: reminderPanelIn .18s ease-out; }

    @media (prefers-reduced-motion: reduce) {
        .reminder-in,
        .reminder-panel-in { animation: none; }
    }
</style>

<script>
(function () {
    if (window.__reminderPopup) return;
    window.__reminderPopup = true;

    var box = document.getElementById('reminder-popup');
    if (!box) return;

    var storeKey = 'elogbook.dismissed.' + box.getAttribute('data-student');
    var minimizeKey = 'elogbook.reminders.minimized.' + box.getAttribute('data-student');
    var items = [];
    var today = '';
    var open = false;
    var expanded = false;
    var lastSignature = '';
    var timer = null;
    var minimized = false;

    try { minimized = localStorage.getItem(minimizeKey) === '1'; } catch (e) {}

    // Each kind of reminder uses one of these card styles.
    var STYLES = {
        attendance: { label: 'Attendance', icon: 'fa-clock', card: 'border-[#F3C4BF]', chip: 'bg-[#FFE4E6] text-[#BE123C]', button: 'bg-[#BE123C] hover:bg-[#9F1239]' },
        logbook: { label: 'Logbook', icon: 'fa-file-pen', card: 'border-[#FDE68A]', chip: 'bg-[#FEF3C7] text-[#B45309]', button: 'bg-[#B45309] hover:bg-[#92400E]' },
        revision: { label: 'Revision', icon: 'fa-rotate-left', card: 'border-[#FED7AA]', chip: 'bg-[#FFEDD5] text-[#9A3412]', button: 'bg-[#C2410C] hover:bg-[#9A3412]' },
        overdue: { label: 'Overdue task', icon: 'fa-triangle-exclamation', card: 'border-[#F3C4BF]', chip: 'bg-[#FFE4E6] text-[#BE123C]', button: 'bg-[#BE123C] hover:bg-[#9F1239]' },
        task: { label: 'Task', icon: 'fa-list-check', card: 'border-[#C7D2FE]', chip: 'bg-[#E0E7FF] text-[#3730A3]', button: 'bg-[#2F5BFF] hover:bg-[#1F45D6]' },
        assigned: { label: 'New task', icon: 'fa-building-user', card: 'border-[#C7D2FE]', chip: 'bg-[#E0E7FF] text-[#3730A3]', button: 'bg-[#2F5BFF] hover:bg-[#1F45D6]' }
    };

    var KIND_STYLE = {
        attendance: 'attendance', clockout: 'attendance',
        logbook: 'logbook', missing: 'logbook',
        revision: 'revision',
        overdue: 'overdue', today: 'task', soon: 'task',
        assigned: 'assigned'
    };

    // Dismissed reminders: { key: 'forever' | 'YYYY-MM-DD' }. A changed situation has a new key, so it shows again.
    function loadDismissed() {
        try { return JSON.parse(localStorage.getItem(storeKey) || '{}') || {}; } catch (e) { return {}; }
    }

    function saveDismissed(map) {
        try { localStorage.setItem(storeKey, JSON.stringify(map)); } catch (e) {}
    }

    function setMinimized(value) {
        minimized = value;

        try { localStorage.setItem(minimizeKey, minimized ? '1' : '0'); } catch (e) {}
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

    function card(item) {
        var style = STYLES[KIND_STYLE[item.kind]] || STYLES.task;
        var wrap = el('article', 'relative rounded-2xl border bg-white p-3.5 shadow-sm transition hover:shadow-md ' + style.card);

        var head = el('div', 'flex items-start gap-3 pr-7');
        var chip = el('span', 'flex h-10 w-10 flex-none items-center justify-center rounded-xl ' + style.chip);
        chip.appendChild(el('i', 'fa-solid text-sm ' + style.icon));
        head.appendChild(chip);

        var text = el('div', 'min-w-0');
        text.appendChild(el('p', 'text-[10px] font-extrabold uppercase tracking-[.12em] text-[#64748B]', style.label));
        text.appendChild(el('p', 'mt-0.5 break-words text-sm font-bold leading-snug text-[#172554]', item.title));
        if (item.text) text.appendChild(el('p', 'mt-1 line-clamp-2 break-words text-xs leading-relaxed text-[#64748B]', item.text));
        head.appendChild(text);
        wrap.appendChild(head);

        var close = el('button', 'absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-lg text-[#94A3B8] transition hover:bg-slate-100 hover:text-[#334155] focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400');
        close.type = 'button';
        close.setAttribute('aria-label', 'Hide this reminder');
        close.setAttribute('data-dismiss', item.key);
        close.appendChild(el('i', 'fa-solid fa-xmark text-[11px]'));
        wrap.appendChild(close);

        if (item.url && item.action) {
            var link = el('a', 'mt-3 flex min-h-10 items-center justify-between rounded-xl px-3.5 text-xs font-extrabold text-white transition ' + style.button);
            link.href = item.url;
            link.setAttribute('data-reminder-action', item.key);
            link.appendChild(el('span', '', item.action));
            link.appendChild(el('i', 'fa-solid fa-arrow-right text-[11px]'));
            wrap.appendChild(link);
        }

        return wrap;
    }

    function render() {
        var visible = items.filter(function (item) { return !isHidden(item); });

        if (!visible.length) {
            box.classList.add('hidden');
            box.textContent = '';
            open = false;
            expanded = false;
            lastSignature = '';
            return;
        }

        var signature = visible.map(function (item) { return item.key; }).join('|');
        var shown = expanded ? visible : visible.slice(0, 3);
        var behind = visible.some(function (item) { return item.kind === 'overdue' || item.kind === 'missing'; });

        if (minimized) {
            box.classList.add('is-minimized');

            var mini = el('section', 'reminder-in rounded-l-2xl border border-r-0 border-white/90 bg-white/90 p-1.5 shadow-[0_16px_48px_rgba(15,27,77,0.24)] backdrop-blur-xl');
            var restore = el('button', 'relative flex h-14 w-14 items-center justify-center rounded-l-xl bg-gradient-to-br from-[#4338CA] via-[#3B5BDB] to-[#0EA5E9] text-lg text-white shadow-lg transition hover:-translate-x-1 focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-200');
            restore.type = 'button';
            restore.setAttribute('data-restore-reminders', '1');
            restore.setAttribute('aria-label', 'Show ' + visible.length + (visible.length === 1 ? ' reminder' : ' reminders'));
            restore.title = 'Show reminders';
            restore.appendChild(el('i', 'fa-solid fa-bell'));
            restore.appendChild(el('span', 'absolute -left-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-extrabold text-white', String(visible.length)));
            mini.appendChild(restore);

            box.textContent = '';
            box.appendChild(mini);
            box.classList.remove('hidden');
            return;
        }

        box.classList.remove('is-minimized');

        var section = el('section', 'rounded-[24px] border border-white/80 bg-white/90 p-3 shadow-[0_24px_70px_rgba(15,27,77,0.2)] ring-1 ring-[#DDE6FF] backdrop-blur-xl');
        if (signature !== lastSignature) section.className += ' reminder-in';
        lastSignature = signature;

        var header = el('div', 'flex items-stretch gap-0.5 rounded-[19px] bg-gradient-to-br from-[#4338CA] via-[#3B5BDB] to-[#0EA5E9] p-1.5 shadow-lg shadow-indigo-950/20');
        var toggle = el('button', 'group flex min-w-0 flex-1 items-center gap-3 rounded-[15px] p-2.5 text-left text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80');
        toggle.type = 'button';
        toggle.setAttribute('data-toggle-panel', '1');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-controls', 'reminder-list');

        var bell = el('span', 'relative flex h-11 w-11 flex-none items-center justify-center rounded-2xl border border-white/20 bg-white/15 text-lg shadow-inner backdrop-blur-sm');
        bell.appendChild(el('i', 'fa-solid fa-bell'));
        var count = el('span', 'absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-indigo-600 bg-rose-500 px-1 text-[10px] font-extrabold text-white', String(visible.length));
        bell.appendChild(count);
        toggle.appendChild(bell);

        var heading = el('span', 'min-w-0 flex-1');
        heading.appendChild(el('span', 'block text-[9px] font-extrabold uppercase tracking-[.18em] text-blue-100', 'Your daily brief'));
        heading.appendChild(el('span', 'mt-0.5 block text-sm font-extrabold leading-tight', 'A few things need you'));
        heading.appendChild(el('span', 'mt-1 block text-[11px] font-medium text-blue-100', visible.length + (visible.length === 1 ? ' reminder' : ' reminders') + ' to take care of' + (behind ? ' · overdue included' : '')));
        toggle.appendChild(heading);

        var chevron = el('span', 'flex h-8 w-8 flex-none items-center justify-center rounded-xl bg-white/10 text-xs transition group-hover:bg-white/20');
        chevron.appendChild(el('i', 'fa-solid ' + (open ? 'fa-chevron-up' : 'fa-chevron-down')));
        toggle.appendChild(chevron);
        header.appendChild(toggle);

        var minimizeButton = el('button', 'flex w-10 flex-none items-center justify-center rounded-xl text-white/85 transition hover:bg-white/15 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80');
        minimizeButton.type = 'button';
        minimizeButton.setAttribute('data-minimize-reminders', '1');
        minimizeButton.setAttribute('aria-label', 'Minimize reminders to the side');
        minimizeButton.title = 'Minimize to side';
        minimizeButton.appendChild(el('i', 'fa-solid fa-minus text-xs'));
        header.appendChild(minimizeButton);
        section.appendChild(header);

        var panel = el('div', open ? 'reminder-panel-in mt-3' : 'hidden');
        panel.id = 'reminder-list';

        var panelHeading = el('div', 'flex items-center justify-between gap-3 px-1');
        panelHeading.appendChild(el('p', 'text-[10px] font-extrabold uppercase tracking-[.14em] text-[#64748B]', 'Needs your attention'));
        var closeAll = el('button', 'inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[10px] font-bold text-[#64748B] transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400', 'Dismiss all');
        closeAll.type = 'button';
        closeAll.setAttribute('aria-label', 'Dismiss all reminders for today');
        closeAll.setAttribute('data-dismiss-all', '1');
        closeAll.appendChild(el('i', 'fa-solid fa-xmark'));
        panelHeading.appendChild(closeAll);
        panel.appendChild(panelHeading);

        var cards = el('div', 'mt-2.5 max-h-[55vh] space-y-2.5 overflow-y-auto px-0.5 pb-0.5');
        shown.forEach(function (item) { cards.appendChild(card(item)); });
        panel.appendChild(cards);

        if (visible.length > 3) {
            var more = el('button', 'mt-2 w-full rounded-xl py-2 text-xs font-extrabold text-[#3B5BDB] transition hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400', expanded ? 'Show fewer reminders' : 'Show ' + (visible.length - 3) + ' more');
            more.type = 'button';
            more.setAttribute('data-toggle-more', '1');
            panel.appendChild(more);
        }

        section.appendChild(panel);
        box.textContent = '';
        box.appendChild(section);
        box.classList.remove('hidden');
    }

    function load() {
        fetch('/student/dashboard/reminders', {
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

        if (target.closest('[data-toggle-panel]')) {
            open = !open;
            if (!open) expanded = false;
            render();
            return;
        }

        if (target.closest('[data-minimize-reminders]')) {
            setMinimized(true);
            render();
            return;
        }

        if (target.closest('[data-restore-reminders]')) {
            setMinimized(false);
            render();
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

    document.addEventListener('click', function (event) {
        if (open && !box.contains(event.target)) {
            open = false;
            expanded = false;
            render();
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (open && event.key === 'Escape') {
            open = false;
            expanded = false;
            render();
        }
    });

    // When reminders are checked: on load, on coming back to the tab, every 5 minutes, and after any action.
    document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
    window.addEventListener('focus', soon);
    window.addEventListener('pageshow', soon);
    document.addEventListener('page:loaded', soon);
    document.addEventListener('async:success', soon);
    document.addEventListener('async:swapped', soon);
    setInterval(load, 300000);

    load();
})();
</script>
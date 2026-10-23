@php $reminderStudentId = \Illuminate\Support\Facades\Auth::guard('student')->id(); @endphp

<div id="reminder-popup" data-student="{{ $reminderStudentId }}" class="mb-6 hidden" role="region" aria-label="Reminders" aria-live="polite"></div>

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
        var wrap = el('div', 'relative flex min-w-[200px] flex-1 flex-col gap-2 rounded-2xl border bg-white p-3.5 ' + style.card);

        var head = el('div', 'flex items-center gap-2.5 pr-5');
        var chip = el('span', 'flex h-8 w-8 flex-none items-center justify-center rounded-[10px] ' + style.chip);
        chip.appendChild(el('i', 'fa-solid text-sm ' + style.icon));
        head.appendChild(chip);

        var text = el('div', 'min-w-0');
        text.appendChild(el('p', 'text-sm font-extrabold', style.label));
        text.appendChild(el('p', 'break-words text-xs font-semibold text-[#334155]', item.title));
        if (item.text) text.appendChild(el('p', 'mt-0.5 line-clamp-2 break-words text-[11px] text-[#5B6478]', item.text));
        head.appendChild(text);
        wrap.appendChild(head);

        var close = el('button', 'absolute right-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-md text-[#94A3B8] transition hover:bg-slate-100 hover:text-[#5B6478]');
        close.type = 'button';
        close.setAttribute('aria-label', 'Hide this reminder');
        close.setAttribute('data-dismiss', item.key);
        close.appendChild(el('i', 'fa-solid fa-xmark text-xs'));
        wrap.appendChild(close);

        if (item.url && item.action) {
            var link = el('a', 'mt-auto flex min-h-9 items-center justify-center rounded-xl px-3 text-sm font-bold text-white transition ' + style.button, item.action);
            link.href = item.url;
            link.setAttribute('data-reminder-action', item.key);
            wrap.appendChild(link);
        }

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
        var behind = visible.some(function (item) { return item.kind === 'overdue' || item.kind === 'missing'; });

        var section = el('section', 'relative rounded-[22px] border border-[#FBCFCB] bg-gradient-to-r from-[#FFE9E7] to-[#FFF3F0] px-5 py-5 shadow-[0_6px_18px_rgba(15,27,77,0.06)] md:px-7');
        if (signature !== lastSignature) section.className += ' reminder-in';
        lastSignature = signature;

        var closeAll = el('button', 'absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg text-[#5B6478] transition hover:bg-white/70');
        closeAll.type = 'button';
        closeAll.setAttribute('aria-label', 'Hide all reminders for now');
        closeAll.setAttribute('title', 'Hide all reminders for now');
        closeAll.setAttribute('data-dismiss-all', '1');
        closeAll.appendChild(el('i', 'fa-solid fa-xmark'));
        section.appendChild(closeAll);

        var layout = el('div', 'flex flex-wrap items-center gap-x-6 gap-y-4 pr-8');

        var left = el('div', 'flex min-w-0 flex-[1_1_260px] items-center gap-4');
        var bell = el('span', 'flex h-14 w-14 flex-none items-center justify-center rounded-full bg-[#FDBA3B]/30 text-3xl text-[#D97706]');
        bell.appendChild(el('i', 'fa-solid fa-bell'));
        left.appendChild(bell);

        var heading = el('div');
        heading.appendChild(el('span', 'rounded-full bg-[#BE123C] px-2.5 py-0.5 text-[10px] font-extrabold tracking-wider text-white', 'REMINDER'));
        heading.appendChild(el('h2', 'mt-1 text-xl font-extrabold leading-tight text-[#0F1B4D]', 'You have pending tasks!'));
        heading.appendChild(el('p', 'mt-0.5 text-[13px] text-[#5B6478]', behind
            ? 'Some of these are already overdue. Please catch up as soon as you can.'
            : "Don't forget to complete these before they're overdue."));
        left.appendChild(heading);
        layout.appendChild(left);

        var cards = el('div', 'flex min-w-0 flex-[3_1_520px] flex-wrap gap-3');
        shown.forEach(function (item) { cards.appendChild(card(item)); });
        layout.appendChild(cards);

        section.appendChild(layout);

        if (visible.length > 3) {
            var more = el('button', 'mt-3 text-xs font-bold text-[#1F45D6] hover:underline', expanded ? 'Show less' : 'Show ' + (visible.length - 3) + ' more');
            more.type = 'button';
            more.setAttribute('data-toggle-more', '1');
            section.appendChild(more);
        }

        box.textContent = '';
        box.appendChild(section);
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

    // When reminders are checked: on load, on coming back to the tab, every 5 minutes, and after any action.
    document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
    window.addEventListener('focus', soon);
    window.addEventListener('pageshow', soon);
    document.addEventListener('async:success', soon);
    document.addEventListener('async:swapped', soon);
    setInterval(load, 300000);

    load();
})();
</script>
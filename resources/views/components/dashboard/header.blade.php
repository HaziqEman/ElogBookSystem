@props(['pageTitle', 'portalName', 'todayLabel', 'displayName', 'roleLabel', 'dashboardHref'])

<header
    class="sticky top-0 isolate z-20 flex h-16 shrink-0 items-center justify-between gap-3 overflow-visible border-b border-indigo-100/70 bg-gradient-to-r from-white via-indigo-50/70 to-blue-50/80 px-4 shadow-sm backdrop-blur-xl md:h-[4.5rem] md:px-8"
>

    {{-- Decorative blue and lavender wave background --}}
    <div
        class="pointer-events-none absolute inset-0 -z-10 overflow-hidden"
        aria-hidden="true"
    >
        <svg
            class="absolute inset-0 h-full w-full"
            viewBox="0 0 1200 100"
            preserveAspectRatio="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <defs>
                {{-- Soft blue gradient --}}
                <linearGradient
                    id="headerWaveBlue"
                    x1="0"
                    y1="0"
                    x2="1"
                    y2="1"
                >
                    <stop
                        offset="0%"
                        stop-color="#93C5FD"
                        stop-opacity="0.25"
                    />
                    <stop
                        offset="100%"
                        stop-color="#6366F1"
                        stop-opacity="0.04"
                    />
                </linearGradient>

                {{-- Lavender gradient --}}
                <linearGradient
                    id="headerWavePurple"
                    x1="0"
                    y1="0"
                    x2="1"
                    y2="0"
                >
                    <stop
                        offset="0%"
                        stop-color="#C4B5FD"
                        stop-opacity="0.05"
                    />
                    <stop
                        offset="55%"
                        stop-color="#A5B4FC"
                        stop-opacity="0.22"
                    />
                    <stop
                        offset="100%"
                        stop-color="#93C5FD"
                        stop-opacity="0.10"
                    />
                </linearGradient>
            </defs>

            {{-- Back wave --}}
            <path
                d="M0 0 H1200 V100 H0 Z"
                fill="url(#headerWaveBlue)"
            />

            {{-- Main flowing wave --}}
            <path
                d="M0 0 H730
               C690 15 680 48 625 72
               C570 98 480 100 400 100
               H0 Z"
                fill="url(#headerWavePurple)"
            />

            {{-- Right-side soft wave --}}
            <path
                d="M680 0
               C750 15 780 65 860 78
               C980 100 1080 50 1200 35
               V0 Z"
                fill="#818CF8"
                fill-opacity="0.07"
            />

            {{-- Soft curved highlight --}}
            <path
                d="M730 0 C690 30 680 65 625 85"
                fill="none"
                stroke="#A5B4FC"
                stroke-opacity="0.18"
                stroke-width="2"
            />
        </svg>
    </div>

    <div class="relative z-10 flex min-w-0 items-center gap-3">
        <button
            type="button"
            id="sidebar-toggle"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100 md:hidden"
            aria-label="Open menu"
            aria-controls="sidebar"
            aria-expanded="false"
        >
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="min-w-0">
            <p
                class="truncate text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 md:text-[11px]">
                UiTM E-Logbook <span class="mx-1 text-slate-300">/</span>
                {{ $portalName }}
            </p>
            <h2
                id="page-title"
                class="truncate text-base font-bold leading-tight tracking-tight text-slate-900 md:text-xl"
            >
                {{ $pageTitle }}</h2>
        </div>
    </div>

    <div class="relative z-10 flex shrink-0 items-center gap-2 md:gap-3">
        {{-- Today's date --}}
        <span
            class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 lg:inline-flex"
        >
            <i class="fa-regular fa-calendar text-blue-600"></i>
            {{ $todayLabel }}
        </span>

        @if (request()->is('student*') || request()->is('lecturer*') || request()->is('supervisor*'))
            @php $notifRole = request()->segment(1); @endphp

            <x-dashboard.notification-area :role="$notifRole" />

            <script>
                (function() {
                    var wrap = document.getElementById('notif-wrap');
                    var bell = document.getElementById('notif-bell');
                    var badge = document.getElementById('notif-badge');
                    var panel = document.getElementById('notif-panel');
                    var list = document.getElementById('notif-panel-list');
                    var sub = document.getElementById('notif-panel-sub');
                    var readAllBtn = document.getElementById('notif-panel-readall');
                    if (!wrap || !bell || !badge || !panel) return;

                    var unread = 0;
                    var MAX_ITEMS = 8;

                    function csrf() {
                        var m = document.querySelector('meta[name="csrf-token"]');
                        return m ? m.getAttribute('content') : '';
                    }

                    function el(tag, cls, text) {
                        var node = document.createElement(tag);
                        if (cls) node.className = cls;
                        if (text !== undefined && text !== null) node.textContent = text;
                        return node;
                    }

                    function setUnread(n) {
                        unread = n;
                        badge.textContent = n > 99 ? '99+' : n;
                        badge.classList.toggle('hidden', n === 0);
                        sub.textContent = n > 0 ? n + ' unread' : 'You are all caught up';
                        readAllBtn.classList.toggle('hidden', n === 0);
                    }

                    function refreshCount() {
                        return fetch(bell.getAttribute('data-count-url'), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin'
                            })
                            .then(function(r) {
                                return r.ok ? r.json() : null;
                            })
                            .then(function(d) {
                                if (!d) return;
                                setUnread(parseInt(d.count, 10) || 0);
                            })
                            .catch(function() {});
                    }

                    function showMessage(iconClass, text) {
                        list.innerHTML = '';
                        var box = el('div', 'px-6 py-10 text-center text-sm text-slate-500');
                        var icon = el('i', 'fa-solid ' + iconClass + ' mb-2 text-2xl text-slate-300');
                        box.appendChild(icon);
                        box.appendChild(el('p', '', text));
                        list.appendChild(box);
                    }

                    // Hitting the /open route marks it as read on the server; the redirect is not followed.
                    function markRead(data, row, btn, dot, title) {
                        if (data.unread !== '1') return;
                        data.unread = '0';
                        btn.classList.remove('bg-blue-50/60');
                        if (dot) dot.remove();
                        title.classList.remove('font-bold', 'text-slate-900');
                        title.classList.add('font-semibold', 'text-slate-700');

                        fetch(data.openUrl, {
                            method: 'GET',
                            credentials: 'same-origin',
                            redirect: 'manual',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).catch(function() {}).then(refreshCount);
                    }

                    function buildRow(src) {
                        var data = {
                            openUrl: src.getAttribute('data-open-url'),
                            type: src.getAttribute('data-type'),
                            unread: src.getAttribute('data-unread'),
                            title: src.getAttribute('data-title') || '',
                            body: src.getAttribute('data-body') || '',
                            time: src.getAttribute('data-time') || '',
                            ago: src.getAttribute('data-ago') || '',
                            count: parseInt(src.getAttribute('data-count'), 10) || 0,
                            icon: src.getAttribute('data-icon') || 'fa-bell',
                            iconClass: src.getAttribute('data-icon-class') ||
                                'bg-slate-100 text-slate-600'
                        };

                        var row = el('div', 'border-b border-slate-100 last:border-b-0');
                        var btn = el('button',
                            'flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50' +
                            (data.unread === '1' ? ' bg-blue-50/60' : ''));
                        btn.type = 'button';

                        var iconBox = el('span',
                            'flex h-10 w-10 flex-none items-center justify-center rounded-xl ' + data
                            .iconClass);
                        iconBox.appendChild(el('i', 'fa-solid ' + data.icon));
                        btn.appendChild(iconBox);

                        var textWrap = el('div', 'min-w-0 flex-1');
                        var title = el('p', 'break-words text-sm ' + (data.unread === '1' ?
                                'font-bold text-slate-900' : 'font-semibold text-slate-700'),
                            data.title + (data.count > 1 ? ' (' + data.count + ')' : ''));
                        textWrap.appendChild(title);

                        var body = null;
                        if (data.body) {
                            body = el('p', 'mt-0.5 truncate text-sm text-slate-500', data.body);
                            textWrap.appendChild(body);
                        }
                        if (data.ago) {
                            textWrap.appendChild(el('p', 'mt-1 text-xs text-slate-400', data.ago));
                        }
                        btn.appendChild(textWrap);

                        var dot = null;
                        if (data.unread === '1') {
                            dot = el('span', 'mt-2 h-2.5 w-2.5 flex-none rounded-full bg-blue-600');
                            btn.appendChild(dot);
                        }
                        row.appendChild(btn);

                        // Expanded details (full message, exact time, optional link)
                        var detail = el('div', 'hidden px-4 pb-3 pl-[4.25rem]');
                        if (data.time) detail.appendChild(el('p', 'text-xs text-slate-400', data.time));
                        if (data.type !== 'reminder' && data.openUrl) {
                            var link = el('a',
                                'mt-2 inline-block rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50',
                                'Open page');
                            link.href = data.openUrl;
                            detail.appendChild(link);
                        }
                        row.appendChild(detail);

                        btn.addEventListener('click', function() {
                            var open = detail.classList.toggle('hidden') === false;
                            if (body) {
                                body.classList.toggle('truncate', !open);
                                body.classList.toggle('whitespace-pre-line', open);
                                body.classList.toggle('break-words', open);
                            }
                            if (open) markRead(data, row, btn, dot, title);
                        });

                        return row;
                    }

                    function loadList() {
                        showMessage('fa-spinner fa-spin', 'Loading...');

                        fetch(bell.getAttribute('data-list-url'), {
                                headers: {
                                    'Accept': 'text/html',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin'
                            })
                            .then(function(r) {
                                return r.ok ? r.text() : Promise.reject();
                            })
                            .then(function(html) {
                                var doc = new DOMParser().parseFromString(html, 'text/html');
                                var items = doc.querySelectorAll('[data-notif-item]');

                                if (!items.length) {
                                    showMessage('fa-bell-slash', 'No notifications yet.');
                                    return;
                                }

                                list.innerHTML = '';
                                Array.prototype.slice.call(items, 0, MAX_ITEMS).forEach(function(src) {
                                    list.appendChild(buildRow(src));
                                });
                            })
                            .catch(function() {
                                showMessage('fa-triangle-exclamation',
                                    'Could not load notifications. Please try again.');
                            });
                    }

                    function openPanel() {
                        panel.classList.remove('hidden');
                        bell.setAttribute('aria-expanded', 'true');
                        loadList();
                        refreshCount();
                    }

                    function closePanel() {
                        panel.classList.add('hidden');
                        bell.setAttribute('aria-expanded', 'false');
                    }

                    bell.addEventListener('click', function(e) {
                        e.stopPropagation();
                        if (panel.classList.contains('hidden')) openPanel();
                        else closePanel();
                    });

                    readAllBtn.addEventListener('click', function() {
                        readAllBtn.disabled = true;
                        fetch(bell.getAttribute('data-read-all-url'), {
                            method: 'POST',
                            credentials: 'same-origin',
                            redirect: 'manual',
                            headers: {
                                'X-CSRF-TOKEN': csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        }).catch(function() {}).then(function() {
                            readAllBtn.disabled = false;
                            refreshCount();
                            if (!panel.classList.contains('hidden')) loadList();
                        });
                    });

                    document.addEventListener('click', function(e) {
                        if (!panel.classList.contains('hidden') && !wrap.contains(e.target))
                            closePanel();
                    });

                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
                            closePanel();
                            bell.focus();
                        }
                    });

                    refreshCount();
                    setInterval(refreshCount, 60000);
                    document.addEventListener('visibilitychange', function() {
                        if (!document.hidden) refreshCount();
                    });
                })();
            </script>
        @endif

        {{-- Profile menu --}}
        <div
            class="relative"
            id="profile-wrap"
        >
            <button
                type="button"
                id="profile-btn"
                aria-haspopup="true"
                aria-expanded="false"
                aria-controls="profile-menu"
                class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white py-1.5 pl-1.5 pr-2.5 transition hover:bg-slate-50 md:pr-3"
            >
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 text-sm font-bold text-white"
                >
                    {{ strtoupper(substr($displayName ?? 'S', 0, 1)) }}
                </span>
                <span class="hidden text-left leading-tight md:block">
                    <span
                        class="block max-w-[9rem] truncate text-sm font-semibold text-slate-800">{{ $displayName }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $portalName }}</span>
                </span>
                <i class="fa-solid fa-chevron-down hidden text-[10px] text-slate-400 md:block"></i>
            </button>

            <div
                id="profile-menu"
                class="absolute right-0 top-full z-40 mt-2 hidden w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
            >
                <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                    <p class="truncate text-sm font-bold text-slate-800">
                        {{ $displayName }}</p>
                    <p class="text-xs text-slate-500">{{ $roleLabel }}</p>
                </div>
                <div class="p-1.5">
                    <a
                        href="{{ $dashboardHref }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <i class="fa-solid fa-house w-4 text-slate-400"></i>
                        Dashboard
                    </a>
                    {{-- Logout must be POST (the route only accepts POST) --}}
                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                        class="m-0"
                    >
                        @csrf
                        <button
                            type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                        >
                            <i class="fa-solid fa-arrow-right-from-bracket w-4"></i>
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @include('components.dashboard.scripts.profile-menu')
    </div>
</header>

@extends('layouts.app')

@section('content')
    @php
        $notificationsUrl = url("/{$role}/notifications");
        $filterTabs = [
            ['key' => 'all', 'label' => 'All', 'count' => $allCount],
            ['key' => 'unread', 'label' => 'Unread', 'count' => $unread],
            ['key' => 'reminders', 'label' => 'Reminders', 'count' => $categoryCounts['reminders']],
            ['key' => 'messages', 'label' => 'Messages', 'count' => $categoryCounts['messages']],
            ['key' => 'entries', 'label' => 'Entries', 'count' => $categoryCounts['entries']],
        ];
    @endphp

    <div id="notification-inbox" class="mx-auto max-w-4xl space-y-5 sm:space-y-6">
        <div
            id="notif-toolbar"
            data-live
            data-notification-read-url="{{ $notificationsUrl }}/read"
            data-notification-dismiss-url="{{ $notificationsUrl }}/dismiss"
            data-notification-dismiss-read-url="{{ $notificationsUrl }}/dismiss-read"
            data-notification-dismiss-all-url="{{ $notificationsUrl }}/dismiss-all"
            data-notification-count-url="{{ $notificationsUrl }}/unread-count"
            class="space-y-4 sm:space-y-5"
        >
            <section
                class="relative z-20 overflow-visible rounded-3xl border border-white/70 bg-gradient-to-r from-[#EEF3FF] via-[#DFE9FF] to-[#C9D9FF] px-4 py-5 shadow-[0_10px_30px_rgba(49,88,216,0.12)] sm:px-8"
            >
                <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden rounded-3xl" aria-hidden="true">
                    <svg class="absolute inset-y-0 right-0 h-full w-1/2 text-[#8FAAFF]/50" viewBox="0 0 400 200" preserveAspectRatio="none">
                        <path d="M0 200 C120 190 200 80 400 10 L400 200 Z" fill="currentColor"></path>
                    </svg>
                    <svg class="absolute inset-y-0 right-0 h-full w-1/3 text-[#5B7FF0]/40" viewBox="0 0 400 200" preserveAspectRatio="none">
                        <path d="M0 200 C150 195 260 110 400 60 L400 200 Z" fill="currentColor"></path>
                    </svg>
                </div>

                <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-5">
                        <img
                            src="{{ asset('images/bell-3d.svg') }}"
                            alt=""
                            class="hidden h-24 w-24 flex-none sm:block"
                        >
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-[#3158D8]">Your Updates —</p>
                            <div class="mt-1 flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                                    Your inbox
                                </h1>
                                @if ($unread > 0)
                                    <span class="rounded-full bg-[#3158D8] px-3 py-1 text-xs font-bold text-white">
                                        {{ $unread }} new
                                    </span>
                                @endif
                            </div>
                            <p
                                id="notif-unread-text"
                                class="mt-1 text-sm text-slate-600"
                            >
                                {{ $unread > 0 ? 'You have new updates waiting for you.' : "You're all caught up. Here's your recent activity." }}
                            </p>
                        </div>
                    </div>

                </div>
            </section>

            <div
                id="notif-clear-confirm"
                class="hidden flex-col gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:flex-row sm:items-center sm:justify-between"
                role="alert"
            >
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5 text-rose-600" aria-hidden="true"></i>
                    <p class="text-sm font-semibold text-rose-900">
                        Clear all {{ $allCount }} {{ \Illuminate\Support\Str::plural('notification', $allCount) }}? This can't be undone.
                    </p>
                </div>
                <div class="flex flex-none justify-end gap-2">
                    <button
                        type="button"
                        id="notif-clear-cancel"
                        class="min-h-9 rounded-lg border border-rose-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-rose-100"
                    >Cancel</button>
                    <button
                        type="button"
                        id="notif-clear-confirm-button"
                        class="min-h-9 rounded-lg bg-rose-600 px-3 text-sm font-bold text-white transition hover:bg-rose-700 disabled:opacity-60"
                    >Clear all</button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <nav
                    class="relative z-10 flex w-full flex-wrap gap-1 rounded-2xl border border-slate-200 bg-white p-1 shadow-sm sm:w-fit sm:gap-1.5"
                    aria-label="Notification filters"
                >
                    @foreach ($filterTabs as $tab)
                        @php
                            $filterUrl = $tab['key'] === 'all'
                                ? $notificationsUrl
                                : $notificationsUrl.'?filter='.$tab['key'];
                        @endphp
                        <a
                            href="{{ $filterUrl }}"
                            aria-current="{{ $filter === $tab['key'] ? 'page' : 'false' }}"
                            @class([
                                'flex min-h-10 flex-1 items-center justify-center gap-1.5 rounded-xl px-3 text-xs font-bold transition sm:flex-none sm:gap-2 sm:px-4 sm:text-sm',
                                'bg-[#2F5BFF] text-white shadow-[0_4px_12px_rgba(47,91,255,0.28)] hover:bg-[#2448E5]' => $filter === $tab['key'],
                                'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $filter !== $tab['key'],
                            ])
                        >
                            {{ $tab['label'] }}
                            <span @class([
                                'rounded-full px-1.5 py-0.5 text-[10px] font-bold sm:px-2 sm:text-xs',
                                'bg-white/20 text-white' => $filter === $tab['key'],
                                'bg-slate-100 text-slate-500' => $filter !== $tab['key'],
                            ])>
                                {{ $tab['count'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    @if ($unread > 0)
                        <form
                            data-async
                            id="notif-read-all-form"
                            method="POST"
                            action="{{ $notificationsUrl }}/read-all"
                        >
                            @csrf
                            <button
                                type="submit"
                                id="notif-mark-all"
                                class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-[#3158D8] shadow-sm transition hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3158D8] disabled:opacity-60"
                            >
                                <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
                                Mark all as read
                            </button>
                        </form>
                    @endif

                    @if ($allCount > 0)
                        <div id="notif-clear-wrap" class="relative">
                            <button
                                type="button"
                                id="notif-clear-toggle"
                                aria-expanded="false"
                                aria-controls="notif-clear-menu"
                                class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-[#3158D8] shadow-sm transition hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3158D8]"
                            >
                                Clear
                                <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
                            </button>
                            <div
                                id="notif-clear-menu"
                                class="absolute right-0 top-full z-30 mt-2 hidden w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl"
                                role="menu"
                            >
                                @if ($readCount > 0)
                                    <button
                                        type="button"
                                        data-clear-mode="read"
                                        role="menuitem"
                                        class="flex min-h-10 w-full items-center gap-2.5 rounded-xl px-3 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i class="fa-regular fa-eye-slash w-4 text-slate-400" aria-hidden="true"></i>
                                        Clear read
                                    </button>
                                @endif
                                <button
                                    type="button"
                                    data-clear-mode="all"
                                    role="menuitem"
                                    class="flex min-h-10 w-full items-center gap-2.5 rounded-xl px-3 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                                >
                                    <i class="fa-regular fa-trash-can w-4" aria-hidden="true"></i>
                                    Clear all
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div
            id="notif-list"
            data-live
            class="space-y-4"
        >
            @include('notifications.partials.items', [
                'pageItems' => $pageItems,
                'nextUrl' => $nextUrl,
            ])
        </div>

        <div
            id="notif-modal"
            class="fixed inset-0 z-50 hidden items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="notif-modal-title"
        >
            <div
                id="notif-modal-backdrop"
                class="absolute inset-0 bg-slate-900/50"
            ></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <button
                    type="button"
                    id="notif-modal-x"
                    class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>

                <div class="flex items-start gap-4 pr-6">
                    <span
                        id="notif-modal-icon"
                        class="flex h-12 w-12 flex-none items-center justify-center rounded-xl text-lg"
                    ></span>
                    <div class="min-w-0 flex-1">
                        <h2
                            id="notif-modal-title"
                            class="break-words text-lg font-bold text-slate-900"
                        ></h2>
                        <p
                            id="notif-modal-time"
                            class="mt-0.5 text-xs text-slate-400"
                        ></p>
                    </div>
                </div>

                <p
                    id="notif-modal-body"
                    class="mt-4 whitespace-pre-line break-words text-sm leading-relaxed text-slate-600"
                ></p>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <a
                        id="notif-modal-open"
                        href="#"
                        class="hidden rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >Open page</a>
                    <button
                        type="button"
                        id="notif-modal-close"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >Got it</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var inbox = document.getElementById('notification-inbox');
            var modal = document.getElementById('notif-modal');
            var mIcon = document.getElementById('notif-modal-icon');
            var mTitle = document.getElementById('notif-modal-title');
            var mTime = document.getElementById('notif-modal-time');
            var mBody = document.getElementById('notif-modal-body');
            var mOpen = document.getElementById('notif-modal-open');
            var lastFocus = null;
            var loadingOlder = false;

            if (!inbox || !modal) return;

            function toast(message, type) {
                if (window.asyncToast) {
                    window.asyncToast(message, type || 'error');
                } else {
                    console.error(message);
                }
            }

            function postJson(url, payload) {
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';

                return fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload || {})
                }).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok) {
                            throw new Error(data.message || 'The notification action failed.');
                        }
                        return data;
                    });
                });
            }

            function refreshInbox() {
                if (window.refreshLive) {
                    window.refreshLive();
                } else {
                    window.location.reload();
                }

                var toolbar = document.getElementById('notif-toolbar');
                if (!toolbar) return;

                fetch(toolbar.dataset.notificationCountUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).then(function(response) {
                    if (!response.ok) throw new Error('Could not refresh notification count.');
                    return response.json();
                }).then(function(data) {
                    var count = parseInt(data.count, 10) || 0;
                    var badge = document.getElementById('notif-badge');
                    var panelSub = document.getElementById('notif-panel-sub');

                    if (badge) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.classList.toggle('hidden', count === 0);
                    }
                    if (panelSub) {
                        panelSub.textContent = count > 0 ? count + ' unread' : 'You are all caught up';
                    }
                }).catch(function(error) {
                    console.warn('Could not refresh notification count.', error);
                });
            }

            function openModal(item) {
                var count = parseInt(item.dataset.count, 10) || 0;
                mIcon.className =
                    'flex h-12 w-12 flex-none items-center justify-center rounded-xl text-lg ' +
                    item.dataset.iconClass;
                mIcon.innerHTML = '<i class="fa-solid ' + item.dataset.icon + '"></i>';
                mTitle.textContent = item.dataset.title + (count > 1 ? ' (' + count + ')' : '');
                mTime.textContent = item.dataset.time || '';
                mBody.textContent = item.dataset.body || '';
                mBody.classList.toggle('hidden', !item.dataset.body);

                if (item.dataset.type === 'reminder' || !item.dataset.openUrl) {
                    mOpen.classList.add('hidden');
                } else {
                    mOpen.href = item.dataset.openUrl;
                    mOpen.classList.remove('hidden');
                }

                lastFocus = document.activeElement;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.getElementById('notif-modal-close').focus();

                if (item.dataset.unread === '1') markRead(item);
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                var focusTarget = lastFocus && lastFocus.isConnected
                    ? lastFocus
                    : document.getElementById('notif-bell');
                if (focusTarget && focusTarget.focus) focusTarget.focus();
            }

            function markRead(item) {
                if (item.dataset.unread !== '1') return;

                item.dataset.unread = '0';
                item.classList.remove('bg-[#EAF2FB]');
                item.classList.add('bg-white');
                var dot = item.querySelector('[data-notif-dot]');
                if (dot) dot.remove();

                var ids = (item.dataset.notificationIds || '')
                    .split(',')
                    .map(function(id) { return parseInt(id, 10); })
                    .filter(function(id) { return id > 0; });

                postJson(item.dataset.readUrl, { ids: ids })
                    .then(function() { refreshInbox(); })
                    .catch(function(error) { toast(error.message); });
            }

            function dismissRow(button) {
                var toolbar = document.getElementById('notif-toolbar');
                var ids = (button.dataset.notificationIds || '')
                    .split(',')
                    .map(function(id) { return parseInt(id, 10); })
                    .filter(function(id) { return id > 0; });

                button.disabled = true;
                if (!toolbar) {
                    button.disabled = false;
                    toast('Could not find notification controls.');
                    return;
                }
                postJson(toolbar.dataset.notificationDismissUrl, { ids: ids })
                    .then(function(data) {
                        toast(data.message || 'Notification cleared.', 'success');
                        refreshInbox();
                    })
                    .catch(function(error) {
                        button.disabled = false;
                        toast(error.message);
                    });
            }

            function clearNotifications(mode) {
                var toolbar = document.getElementById('notif-toolbar');
                var confirmButton = document.getElementById('notif-clear-confirm-button');
                if (!toolbar) {
                    toast('Could not find notification controls.');
                    return;
                }
                var url = mode === 'read'
                    ? toolbar.dataset.notificationDismissReadUrl
                    : toolbar.dataset.notificationDismissAllUrl;

                if (mode === 'all' && confirmButton) confirmButton.disabled = true;

                postJson(url)
                    .then(function(data) {
                        var confirm = document.getElementById('notif-clear-confirm');
                        var menu = document.getElementById('notif-clear-menu');
                        var toggle = document.getElementById('notif-clear-toggle');
                        if (confirm) {
                            confirm.classList.add('hidden');
                            confirm.classList.remove('flex');
                        }
                        if (menu) menu.classList.add('hidden');
                        if (toggle) toggle.setAttribute('aria-expanded', 'false');
                        toast(data.message || 'Notifications cleared.', 'success');
                        refreshInbox();
                    })
                    .catch(function(error) {
                        if (confirmButton && document.body.contains(confirmButton)) {
                            confirmButton.disabled = false;
                        }
                        toast(error.message);
                    });
            }

            function appendOlder(button) {
                if (loadingOlder) return;
                loadingOlder = true;
                button.disabled = true;
                button.textContent = 'Loading older notifications…';

                fetch(button.dataset.url, {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).then(function(response) {
                    if (!response.ok) throw new Error('Could not load older notifications.');
                    return response.text();
                }).then(function(html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var sourceItems = doc.getElementById('notif-items');
                    var targetItems = document.getElementById('notif-items');

                    if (!sourceItems || !targetItems) {
                        throw new Error('The older notifications response was incomplete.');
                    }

                    var empty = targetItems.querySelector('#notif-empty-state');
                    if (empty) empty.remove();

                    Array.prototype.slice.call(sourceItems.querySelectorAll('[data-notification-group]'))
                        .forEach(function(sourceGroup) {
                            var period = sourceGroup.dataset.period;
                            var targetGroup = Array.prototype.slice.call(
                                targetItems.querySelectorAll('[data-notification-group]')
                            ).find(function(group) { return group.dataset.period === period; });

                            if (!targetGroup) {
                                targetItems.appendChild(sourceGroup);
                                return;
                            }

                            var targetRows = targetGroup.querySelector('[data-group-rows]');
                            var sourceRows = sourceGroup.querySelector('[data-group-rows]');
                            if (targetRows && sourceRows) {
                                while (sourceRows.firstElementChild) {
                                    targetRows.appendChild(sourceRows.firstElementChild);
                                }
                            }
                        });

                    var list = document.getElementById('notif-list');
                    var currentButton = list.querySelector('#notif-load-older');
                    var nextButton = doc.getElementById('notif-load-older');
                    if (currentButton) currentButton.remove();
                    if (nextButton) list.appendChild(nextButton);
                }).catch(function(error) {
                    button.disabled = false;
                    button.textContent = 'Load older notifications';
                    toast(error.message);
                }).then(function() {
                    loadingOlder = false;
                });
            }

            inbox.addEventListener('click', function(event) {
                var currentMenu = document.getElementById('notif-clear-menu');
                var currentWrap = document.getElementById('notif-clear-wrap');
                var currentToggle = document.getElementById('notif-clear-toggle');
                if (currentMenu && !currentMenu.classList.contains('hidden') &&
                    currentWrap && !currentWrap.contains(event.target)) {
                    currentMenu.classList.add('hidden');
                    currentToggle.setAttribute('aria-expanded', 'false');
                }

                var dismissButton = event.target.closest('[data-notification-dismiss]');
                if (dismissButton) {
                    event.preventDefault();
                    dismissRow(dismissButton);
                    return;
                }

                var control = event.target.closest('[data-notification-control]');
                if (control) return;

                var item = event.target.closest('[data-notif-item]');
                if (item) {
                    openModal(item);
                    return;
                }

                if (event.target.id === 'notif-modal-backdrop' ||
                    event.target.closest('#notif-modal-x') ||
                    event.target.closest('#notif-modal-close')) {
                    closeModal();
                    return;
                }

                if (event.target.closest('#notif-clear-toggle')) {
                    var clearMenu = document.getElementById('notif-clear-menu');
                    var clearToggle = document.getElementById('notif-clear-toggle');
                    var isOpen = !clearMenu.classList.contains('hidden');
                    clearMenu.classList.toggle('hidden', isOpen);
                    clearToggle.setAttribute('aria-expanded', String(!isOpen));
                    return;
                }

                var clearAction = event.target.closest('[data-clear-mode]');
                if (clearAction) {
                    var clearMenu = document.getElementById('notif-clear-menu');
                    var clearToggle = document.getElementById('notif-clear-toggle');
                    clearMenu.classList.add('hidden');
                    clearToggle.setAttribute('aria-expanded', 'false');
                    if (clearAction.dataset.clearMode === 'all') {
                        var clearConfirm = document.getElementById('notif-clear-confirm');
                        clearConfirm.classList.remove('hidden');
                        clearConfirm.classList.add('flex');
                        document.getElementById('notif-clear-cancel').focus();
                    } else {
                        clearNotifications('read');
                    }
                    return;
                }

                if (event.target.closest('#notif-clear-cancel')) {
                    var clearConfirm = document.getElementById('notif-clear-confirm');
                    clearConfirm.classList.add('hidden');
                    clearConfirm.classList.remove('flex');
                    return;
                }

                if (event.target.closest('#notif-clear-confirm-button')) {
                    clearNotifications('all');
                    return;
                }

                var loadOlderButton = event.target.closest('#notif-load-older');
                if (loadOlderButton) appendOlder(loadOlderButton);

            });

            inbox.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    closeModal();
                    return;
                }
                if ((event.key === 'Enter' || event.key === ' ') &&
                    event.target.matches && event.target.matches('[data-notif-item]')) {
                    event.preventDefault();
                    openModal(event.target);
                }
            });
        })();
    </script>
@endsection

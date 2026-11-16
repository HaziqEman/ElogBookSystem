@extends('layouts.app')

@section('content')
    @php
        $icons = [
            'message' => ['fa-comments', 'bg-[#E8EFFF] text-[#1F45D6]'],
            'review' => ['fa-clipboard-check', 'bg-[#D1FAE5] text-[#065F46]'],
            'reminder' => ['fa-bell', 'bg-[#FEF3C7] text-[#7C2D12]'],
            'task' => ['fa-list-check', 'bg-[#F0EAFF] text-[#6D28D9]'],
        ];
        $tz = \App\Models\Attendance::TIMEZONE;
    @endphp

    <div class="mx-auto max-w-3xl space-y-5">
        <div
            id="notif-header"
            data-live
            class="shadow-xs flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-5 md:p-6"
        >
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Notifications</h2>
                <p
                    id="notif-unread-text"
                    class="mt-1 text-sm text-slate-500"
                >{{ $unread > 0 ? $unread . ' unread' : 'You are all caught up.' }}</p>
            </div>
            @if ($unread > 0)
                <form
                    data-async
                    id="notif-read-all-form"
                    method="POST"
                    action="/{{ $role }}/notifications/read-all"
                >
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >Mark all as read</button>
                </form>
            @endif
        </div>

        <div
            id="notif-list"
            data-live
            class="shadow-xs overflow-hidden rounded-xl border border-slate-200 bg-white"
        >
            @forelse($notifications as $notification)
                @php
                    $isUnread = $notification->read_at === null;
                    [$icon, $iconClass] = $icons[$notification->type] ?? [
                        'fa-bell',
                        'bg-slate-100 text-slate-600',
                    ];
                    $when = $notification->updated_at
                        ? $notification->updated_at->timezone($tz)
                        : null;
                @endphp
                <div
                    role="button"
                    tabindex="0"
                    data-notif-item
                    data-open-url="/{{ $role }}/notifications/{{ $notification->notification_id }}/open"
                    data-type="{{ $notification->type }}"
                    data-unread="{{ $isUnread ? '1' : '0' }}"
                    data-title="{{ $notification->title }}"
                    data-body="{{ $notification->body }}"
                    data-time="{{ $when ? $when->format('d M Y, H:i') : '' }}"
                    data-ago="{{ $when ? $when->diffForHumans() : '' }}"
                    data-count="{{ (int) $notification->merged_count }}"
                    data-icon="{{ $icon }}"
                    data-icon-class="{{ $iconClass }}"
                    class="{{ $isUnread ? 'bg-blue-50/50' : '' }} flex cursor-pointer items-start gap-3 border-b border-slate-100 p-4 transition last:border-b-0 hover:bg-slate-50 md:p-5"
                >
                    <span
                        class="{{ $iconClass }} flex h-10 w-10 flex-none items-center justify-center rounded-xl"
                    ><i class="fa-solid {{ $icon }}"></i></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2">
                            <p
                                data-notif-title
                                class="{{ $isUnread ? 'font-bold text-slate-900' : 'font-semibold text-slate-700' }} break-words text-sm"
                            >{{ $notification->title }}@if ($notification->merged_count > 1)
                                    <span
                                        class="text-slate-400">({{ $notification->merged_count }})</span>
                                @endif
                            </p>
                        </div>
                        @if ($notification->body)
                            <p class="mt-0.5 break-words text-sm text-slate-500">
                                {{ $notification->body }}</p>
                        @endif
                        @if ($when)
                            <p
                                class="mt-1 text-xs text-slate-400"
                                title="{{ $when->format('d M Y, H:i') }}"
                            >{{ $when->diffForHumans() }}</p>
                        @endif
                    </div>
                    @if ($isUnread)
                        <span
                            data-notif-dot
                            class="mt-2 h-2.5 w-2.5 flex-none rounded-full bg-blue-600"
                            aria-label="Unread"
                        ></span>
                    @endif
                </div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">
                        <i class="fa-solid fa-bell-slash mb-2 text-2xl text-slate-300"></i>
                        <p>No notifications yet. Feedback, new messages and reminders will show up here.</p>
                    </div>
                @endforelse
            </div>

            <div>{{ $notifications->links() }}</div>
        </div>

        {{-- Popup (kept outside the data-live blocks so live refresh never removes it while open) --}}
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
                        <h3
                            id="notif-modal-title"
                            class="break-words text-lg font-bold text-slate-900"
                        ></h3>
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

        <script>
            (function() {
                var modal = document.getElementById('notif-modal');
                var mIcon = document.getElementById('notif-modal-icon');
                var mTitle = document.getElementById('notif-modal-title');
                var mTime = document.getElementById('notif-modal-time');
                var mBody = document.getElementById('notif-modal-body');
                var mOpen = document.getElementById('notif-modal-open');
                var lastFocus = null;

                function openModal(item) {
                    var count = parseInt(item.dataset.count, 10) || 0;
                    mIcon.className =
                        'flex h-12 w-12 flex-none items-center justify-center rounded-xl text-lg ' +
                        item.dataset.iconClass;
                    mIcon.innerHTML = '<i class="fa-solid ' + item.dataset.icon + '"></i>';
                    mTitle.textContent = item.dataset.title + (count > 1 ? ' (' + count + ')' : '');
                    mTime.textContent = [item.dataset.time, item.dataset.ago].filter(Boolean).join(
                        ' · ');
                    mBody.textContent = item.dataset.body || '';
                    mBody.classList.toggle('hidden', !item.dataset.body);

                    // Reminders are popup-only; other types keep an optional link to their page
                    if (item.dataset.type === 'reminder') {
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
                    if (lastFocus && lastFocus.focus) lastFocus.focus();
                }

                // Hitting the /open route marks it as read on the server; we just don't follow the redirect.
                function markRead(item) {
                    item.dataset.unread = '0';

                    fetch(item.dataset.openUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        redirect: 'manual',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).catch(function() {});

                    item.classList.remove('bg-blue-50/50');
                    var dot = item.querySelector('[data-notif-dot]');
                    if (dot) dot.remove();
                    var title = item.querySelector('[data-notif-title]');
                    if (title) {
                        title.classList.remove('font-bold', 'text-slate-900');
                        title.classList.add('font-semibold', 'text-slate-700');
                    }

                    updateUnreadCounts();
                }

                function updateUnreadCounts() {
                    var n = document.querySelectorAll('[data-notif-item][data-unread="1"]').length;

                    var text = document.getElementById('notif-unread-text');
                    if (text) text.textContent = n > 0 ? n + ' unread' : 'You are all caught up.';

                    var readAll = document.getElementById('notif-read-all-form');
                    if (readAll && n === 0) readAll.remove();

                    var badge = document.getElementById('notif-badge');
                    if (badge) {
                        badge.textContent = n > 99 ? '99+' : n;
                        badge.classList.toggle('hidden', n === 0);
                    }
                }

                // Delegated, so it keeps working after data-live refreshes replace the list
                document.addEventListener('click', function(e) {
                    var item = e.target.closest('[data-notif-item]');
                    if (item) {
                        openModal(item);
                        return;
                    }
                    if (e.target.id === 'notif-modal-backdrop' || e.target.closest(
                            '#notif-modal-x') || e.target.closest('#notif-modal-close')) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                        return;
                    }
                    if ((e.key === 'Enter' || e.key === ' ') && e.target.matches && e.target
                        .matches('[data-notif-item]')) {
                        e.preventDefault();
                        openModal(e.target);
                    }
                });
            })();
        </script>
    @endsection

@props(['role'])

{{-- Notification bell + dropdown panel --}}
<div
    class="relative"
    id="notif-wrap"
>
    <button
        type="button"
        id="notif-bell"
        data-count-url="/{{ $role }}/notifications/unread-count"
        data-list-url="/{{ $role }}/notifications"
        data-read-all-url="/{{ $role }}/notifications/read-all"
        aria-label="Notifications"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="notif-panel"
        class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
    >
        <i class="fa-solid fa-bell text-lg"></i>
        <span
            id="notif-badge"
            class="absolute -right-1 -top-1 hidden min-w-[18px] rounded-full bg-rose-600 px-1 text-center text-[10px] font-bold leading-[18px] text-white ring-2 ring-white"
        >0</span>
    </button>

    <div
        id="notif-panel"
        class="absolute right-0 top-full z-40 mt-2 hidden w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:w-96"
    >
        <div
            class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <div>
                <h3 class="text-sm font-bold text-slate-800">
                    Notifications</h3>
                <p
                    id="notif-panel-sub"
                    class="text-xs text-slate-500"
                ></p>
            </div>
            <button
                type="button"
                id="notif-panel-readall"
                class="hidden rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50"
            >Mark all as read</button>
        </div>

        <div
            id="notif-panel-list"
            class="max-h-[60vh] overflow-y-auto"
        ></div>

        <a
            href="/{{ $role }}/notifications"
            class="block border-t border-slate-100 bg-slate-50 px-4 py-3 text-center text-sm font-semibold text-blue-700 transition hover:bg-slate-100"
        >
            View all notifications
        </a>
    </div>
</div>

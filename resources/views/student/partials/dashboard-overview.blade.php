    {{-- HERO --}}
    <section
        class="relative overflow-hidden rounded-3xl px-6 py-6 text-white shadow-[0_14px_34px_rgba(47,91,255,0.20)] md:px-9 md:py-7"
        style="background: linear-gradient(120deg, #3B5BFF 0%, #5C7CFF 40%, #A9BAFF 75%, #D6DEFF 100%);"
    >
        <div
            class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-white/5"
            aria-hidden="true"
        ></div>

        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0 flex-1 basis-72">
                <div
                    class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-[#E4EAFF]">
                    <i class="fa-regular fa-calendar"></i> {{ $today->format('l, d F Y') }}
                </div>
                <h1 class="mb-2 mt-3 break-words text-3xl font-extrabold tracking-tight">Welcome
                    back, {{ $firstName }}!</h1>
                <p class="max-w-md text-sm leading-relaxed text-[#E4EAFF]">Keep up the great work.
                    Stay consistent with your logbook, attend your sessions and make the most of
                    your internship journey.</p>

                <div class="mt-4 flex flex-wrap gap-2.5">
                    <a
                        href="#new-entry"
                        data-write-date="{{ $todayKey }}"
                        class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-white px-4 text-sm font-bold text-[#1F45D6] shadow-sm transition hover:bg-[#F3F5FC]"
                    >
                        <i class="fa-solid fa-pen-to-square"></i> Write today's entry
                    </a>
                    <a
                        href="/student/calendar"
                        class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-4 text-sm font-bold text-white transition hover:bg-white/20"
                    >
                        <i class="fa-solid fa-calendar-days"></i> Open calendar
                    </a>
                </div>
            </div>

            <div class="hidden shrink-0 items-center gap-1.5 md:flex">
                <div
                    class="text-right text-[28px] leading-[1.05]"
                    style="font-family:'Caveat',cursive;transform:rotate(-6deg);color:#1E3A8A"
                >Better<br>skills,<br>brighter<br>future</div>
                <svg
                    width="200"
                    height="136"
                    viewBox="0 0 190 130"
                    aria-hidden="true"
                >
                    <ellipse
                        cx="95"
                        cy="118"
                        rx="80"
                        ry="8"
                        fill="#1B2A7A"
                        opacity=".35"
                    />
                    <rect
                        x="38"
                        y="30"
                        width="96"
                        height="62"
                        rx="7"
                        fill="#0F1B4D"
                    />
                    <rect
                        x="44"
                        y="36"
                        width="84"
                        height="50"
                        rx="4"
                        fill="#5B8CFF"
                    />
                    <circle
                        cx="86"
                        cy="61"
                        r="9"
                        fill="#fff"
                        opacity=".9"
                    />
                    <path
                        d="M24 94h124l-8 12H32z"
                        fill="#C7D4FF"
                    />
                    <rect
                        x="146"
                        y="88"
                        width="30"
                        height="28"
                        rx="6"
                        fill="#EEF2FF"
                    />
                    <path
                        d="M161 88c-10-8-12-20-6-30 6 10 14 12 8 30zM161 88c10-6 18-4 20-14-10 0-18 6-20 14z"
                        fill="#34D399"
                    />
                    <rect
                        x="12"
                        y="84"
                        width="22"
                        height="26"
                        rx="5"
                        fill="#0F1B4D"
                    />
                    <rect
                        x="16"
                        y="90"
                        width="14"
                        height="4"
                        rx="2"
                        fill="#7FA1FF"
                    />
                </svg>
            </div>
        </div>
    </section>

    {{-- STATS: each card tells the student something to act on --}}
    @php
        $statEntries = (int) ($totalLogs ?? 0);
        $statFeedback = (int) ($totalReviews ?? 0);
        $statPresent = (int) ($monthPresent ?? 0);
        $statOff = (int) ($monthLeave ?? 0);
        $statOpen = (int) ($openCount ?? 0);
        $statOverdue = (int) ($overdueCount ?? 0);
        $statUnread = (int) ($unreadMessages ?? 0);

        $statCard =
            'group flex items-center gap-3.5 rounded-[20px] border border-[#E3E8F2] bg-white px-4 py-4 shadow-[0_6px_18px_rgba(15,27,77,0.05)] transition hover:shadow-md';
    @endphp

    <div
        id="stats-grid"
        data-live
        class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4"
    >

        {{-- Logbook --}}
        <a
            href="/student/logbooks"
            class="{{ $statCard }} hover:border-[#9DB4FF]"
        >
            <span
                class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-[#E8EFFF] text-[#1F45D6]"
            ><i class="fa-solid fa-book"></i></span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-xs font-semibold text-[#5B6478]">Logbook entries</div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">{{ $statEntries }}</span>
                    <span class="text-sm font-semibold text-[#5B6478]">written</span>
                </div>
                <div class="mt-0.5 truncate text-xs font-medium text-[#5B6478]">
                    {{ $statFeedback }} feedback received
                </div>
            </div>
            <i
                class="fa-solid fa-chevron-right text-xs text-[#CBD3E3] transition group-hover:text-[#2F5BFF]"></i>
        </a>

        {{-- Attendance this month --}}
        <a
            href="/student/attendance"
            class="{{ $statCard }} hover:border-[#8FD6B3]"
        >
            <span
                class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-[#D1FAE5] text-[#047857]"
            ><i class="fa-solid fa-user-check"></i></span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-xs font-semibold text-[#5B6478]">Attendance this month
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">{{ $statPresent }}</span>
                    <span
                        class="text-sm font-semibold text-[#5B6478]">{{ $statPresent === 1 ? 'day' : 'days' }}
                        present</span>
                </div>
                <div class="mt-0.5 truncate text-xs font-medium text-[#5B6478]">
                    {{ $statOff }} {{ $statOff === 1 ? 'day' : 'days' }} off (MC / leave)
                </div>
            </div>
            <i
                class="fa-solid fa-chevron-right text-xs text-[#CBD3E3] transition group-hover:text-[#047857]"></i>
        </a>

        {{-- Open tasks (goes to the to-do list lower on this page) --}}
        <a
            href="#todo-list"
            class="{{ $statCard }} {{ $statOverdue > 0 ? 'border-[#FBCFCB] hover:border-[#F3A5B5]' : 'hover:border-[#FDE68A]' }}"
        >
            <span
                class="{{ $statOverdue > 0 ? 'bg-[#FFE4E6] text-[#BE123C]' : 'bg-[#FEF3C7] text-[#B45309]' }} flex h-12 w-12 flex-none items-center justify-center rounded-2xl"
            ><i class="fa-solid fa-list-check"></i></span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-xs font-semibold text-[#5B6478]">Open tasks</div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">{{ $statOpen }}</span>
                    <span class="text-sm font-semibold text-[#5B6478]">to do</span>
                </div>
                <div class="mt-0.5">
                    @if ($statOverdue > 0)
                        <span
                            class="inline-block whitespace-nowrap rounded-full bg-[#FFE4E6] px-2 py-0.5 text-[11px] font-bold text-[#9F1239]"
                        >{{ $statOverdue }} overdue</span>
                    @else
                        <span
                            class="inline-block whitespace-nowrap rounded-full bg-[#D1FAE5] px-2 py-0.5 text-[11px] font-bold text-[#065F46]"
                        >Nothing overdue</span>
                    @endif
                </div>
            </div>
            <i
                class="fa-solid fa-chevron-down text-xs text-[#CBD3E3] transition group-hover:text-[#B45309]"></i>
        </a>

        {{-- Unread messages --}}
        <a
            href="/student/messages"
            class="{{ $statCard }} {{ $statUnread > 0 ? 'border-[#FBCFCB] hover:border-[#F3A5B5]' : 'hover:border-[#B9A7F5]' }}"
        >
            <span
                class="{{ $statUnread > 0 ? 'bg-[#FFE4E6] text-[#BE123C]' : 'bg-[#F0EAFF] text-[#6D28D9]' }} flex h-12 w-12 flex-none items-center justify-center rounded-2xl"
            ><i class="fa-solid fa-envelope"></i></span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-xs font-semibold text-[#5B6478]">Unread messages</div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">{{ $statUnread }}</span>
                    <span class="text-sm font-semibold text-[#5B6478]">new</span>
                </div>
                <div
                    class="{{ $statUnread > 0 ? 'text-[#BE123C]' : 'text-[#5B6478]' }} mt-0.5 truncate text-xs font-medium">
                    {{ $statUnread > 0 ? 'Tap to read and reply' : 'You are all caught up' }}
                </div>
            </div>
            @if ($statUnread > 0)
                <span
                    class="h-2.5 w-2.5 flex-none rounded-full bg-[#BE123C]"
                    aria-label="Unread"
                ></span>
            @else
                <i
                    class="fa-solid fa-chevron-right text-xs text-[#CBD3E3] transition group-hover:text-[#6D28D9]"></i>
            @endif
        </a>
    </div>

    {{-- ATTENDANCE | QUICK ACTIONS + AI --}}
    <section class="flex flex-wrap items-stretch gap-5">
        <div class="flex min-w-0 flex-[1.3_1_420px] flex-col gap-4 [&>#attendance-card]:flex-1">
            @if ($extrasReady)
                @include('student.partials.attendance-card')
            @else
                <div
                    class="rounded-[22px] border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    Attendance and to-do tables are not set up yet. Ask the administrator to run the
                    database update.
                </div>
            @endif
        </div>

        <div class="flex min-w-0 flex-[1_1_360px] flex-col gap-4">
            <div class="{{ $card }} p-5">
                <h2 class="mb-3.5 text-base font-extrabold">Quick actions</h2>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <a
                        href="#new-entry"
                        data-write-date="{{ $todayKey }}"
                        class="flex items-center gap-3 rounded-2xl border border-[#E3E8F2] bg-[#F7F9FD] p-3 transition hover:border-[#9DB4FF] hover:bg-white hover:shadow-md"
                    >
                        <span
                            class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-[#2F5BFF] text-white"
                        ><i class="fa-solid fa-plus"></i></span>
                        <span class="text-[13px] font-bold leading-tight">New logbook entry</span>
                    </a>
                    <a
                        href="/student/calendar"
                        class="flex items-center gap-3 rounded-2xl border border-[#E3E8F2] bg-[#F7F9FD] p-3 transition hover:border-[#8FD6B3] hover:bg-white hover:shadow-md"
                    >
                        <span
                            class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-[#047857] text-white"
                        ><i class="fa-solid fa-calendar-days"></i></span>
                        <span class="text-[13px] font-bold leading-tight">View calendar</span>
                    </a>
                    <a
                        href="#new-entry"
                        data-focus="attachment"
                        class="flex items-center gap-3 rounded-2xl border border-[#E3E8F2] bg-[#F7F9FD] p-3 transition hover:border-[#B9A7F5] hover:bg-white hover:shadow-md"
                    >
                        <span
                            class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-[#6D28D9] text-white"
                        ><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                        <span class="text-[13px] font-bold leading-tight">Upload files</span>
                    </a>
                    <a
                        href="{{ $chatHref }}"
                        class="flex items-center gap-3 rounded-2xl border border-[#E3E8F2] bg-[#F7F9FD] p-3 transition hover:border-[#F3A5B5] hover:bg-white hover:shadow-md"
                    >
                        <span
                            class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-[#BE123C] text-white"
                        ><i class="fa-solid fa-comment"></i></span>
                        <span class="text-[13px] font-bold leading-tight">Chat with
                            supervisor</span>
                    </a>
                </div>
            </div>

            @if ($aiReady)
                <div
                    class="flex flex-col gap-2 rounded-[22px] border border-[#E3E8F2] bg-white px-5 py-4 shadow-[0_6px_18px_rgba(15,27,77,0.05)]">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-[#F0EAFF] text-[#6D28D9]"
                        ><i class="fa-solid fa-wand-magic-sparkles text-sm"></i></span>
                        <div class="min-w-0 flex-1 text-sm font-extrabold">AI Help &middot; Gemini
                        </div>
                        <div class="whitespace-nowrap text-[13px] font-bold text-[#334155]"><span
                                id="ai-left"
                                data-limit="{{ $aiLimit }}"
                                class="text-base font-extrabold"
                                style="color:{{ $aiColor }}"
                            >{{ $aiLeft }}</span> / {{ $aiLimit }} left</div>
                    </div>
                    <div class="h-2 overflow-hidden rounded-[5px] bg-[#EEE9FB]">
                        <div
                            id="ai-bar"
                            class="h-full rounded-[5px]"
                            style="width:{{ $aiPct }}%;background:{{ $aiColor }}"
                        ></div>
                    </div>
                    <div class="text-xs text-[#5B6478]">Resets every day at midnight</div>
                </div>
            @endif
        </div>
    </section>

    {{-- DEADLINES + CALENDAR | TO-DO --}}
    @include('student.partials.planner-card')

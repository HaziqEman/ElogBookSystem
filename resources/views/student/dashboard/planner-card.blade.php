@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $cells = $calendar['cells'];
    $chatHref = (bool) optional($student)->supervisor_id ? '/student/messages/supervisor' : '/student/messages';
@endphp

<section class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,0.95fr)] xl:gap-5">
    <div class="order-1 flex min-w-0 flex-col gap-4">
        @if ($extrasReady)
            @include('student.attendance.card')
        @else
            <div class="rounded-[22px] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Attendance and to-do tables are not set up yet. Ask the administrator to run the database update.
            </div>
        @endif

        <details open data-mobile-fold="planner" class="group {{ $card }} p-4 sm:p-5">
            <summary class="mb-3 flex cursor-pointer list-none items-center gap-3 rounded-2xl border border-[#E3E8F2] bg-[#F7F9FD] px-3 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2F5BFF] md:hidden">
                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-[#E0E7FF] text-[#3730A3]">
                    <i class="fa-solid fa-calendar-days"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-extrabold">My calendar</span>
                    <span class="block text-xs text-[#5B6478]">{{ $calendar['title'] }}</span>
                </span>
                <i class="fa-solid fa-chevron-down text-xs text-[#5B6478] transition-transform group-open:rotate-180"></i>
            </summary>

            <div id="calendar-card" data-live data-today="{{ $todayKey }}" class="min-w-0 overflow-hidden rounded-2xl border border-[#DCE3F0] bg-white shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-extrabold">Calendar</h2>
                    <div class="flex items-center gap-2">
                        <a href="/student/calendar?month={{ $calendar['prev'] }}" aria-label="Previous month" class="flex h-9 w-9 items-center justify-center rounded-xl border border-[#E3E8F2] bg-white transition hover:bg-[#F3F5FC]">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </a>
                        <div class="min-w-[116px] text-center text-sm font-extrabold">{{ $calendar['title'] }}</div>
                        <a href="/student/calendar?month={{ $calendar['next'] }}" aria-label="Next month" class="flex h-9 w-9 items-center justify-center rounded-xl border border-[#E3E8F2] bg-white transition hover:bg-[#F3F5FC]">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                    <a href="/student/calendar" class="flex min-h-8 items-center text-[13px] font-bold text-[#1F45D6] hover:underline">View all</a>
                </div>

                <div class="grid grid-cols-7 border-b border-[#E7ECF5] bg-[#F7F9FD] text-center text-[10px] font-bold text-[#64748B] sm:text-xs">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                        <div class="py-2.5 sm:py-3">{{ $weekday }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7">
                    @foreach ($cells as $cell)
                        @if ($cell['in_month'])
                            @php
                                $agendaItems = $calendar['agenda'][$cell['key']] ?? [];
                                $visibleItems = array_slice($agendaItems, 0, 2);
                                $remainingItems = max(count($agendaItems) - count($visibleItems), 0);
                            @endphp
                            <button
                                type="button"
                                data-cal-day="{{ $cell['key'] }}"
                                aria-label="{{ $cell['long'] }}"
                                aria-pressed="false"
                                class="cal-day {{ $cell['is_today'] ? 'is-today' : '' }} relative min-h-[4.5rem] border-b border-r border-[#E9EDF5] bg-white p-1.5 text-left align-top transition hover:bg-blue-50/70 sm:min-h-[6.75rem] sm:p-2"
                            >
                                @if ($cell['is_today'])
                                    <span class="absolute inset-x-0 top-0 h-0.5 bg-blue-600" aria-hidden="true"></span>
                                @endif
                                <span class="cal-date {{ $cell['is_today'] ? 'bg-blue-600 text-white' : 'text-slate-700' }} inline-flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-extrabold sm:h-7 sm:w-7 sm:text-xs">
                                    {{ $cell['label'] }}
                                </span>
                                @if ($visibleItems)
                                    <div class="mt-1 space-y-1">
                                        @foreach ($visibleItems as $item)
                                            @php
                                                $eventStyle = match ($item['type']) {
                                                    'log' => ['bg-blue-50 text-blue-700 ring-blue-100', 'fa-book'],
                                                    'due' => ['bg-indigo-50 text-indigo-700 ring-indigo-100', 'fa-list-check'],
                                                    'leave' => ['bg-amber-50 text-amber-700 ring-amber-100', 'fa-umbrella-beach'],
                                                    'attendance' => ['bg-emerald-50 text-emerald-700 ring-emerald-100', 'fa-circle-check'],
                                                    'note' => ['bg-violet-50 text-violet-700 ring-violet-100', 'fa-note-sticky'],
                                                    default => ['bg-violet-50 text-violet-700 ring-violet-100', 'fa-circle-exclamation'],
                                                };
                                            @endphp
                                            <span class="flex items-center gap-1 overflow-hidden rounded-md px-1 py-1 text-[9px] font-bold ring-1 ring-inset sm:px-1.5 sm:text-[10px] {{ $eventStyle[0] }}">
                                                <i class="fa-solid {{ $eventStyle[1] }} flex-none"></i>
                                                <span class="truncate">{{ $item['title'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($remainingItems > 0)
                                    <span class="mt-1 block truncate text-[9px] font-semibold text-[#64748B]">+{{ $remainingItems }} more</span>
                                @endif
                            </button>
                        @else
                            <div class="min-h-[4.5rem] border-b border-r border-[#E9EDF5] bg-slate-50/60 p-1.5 text-left text-[10px] font-extrabold text-slate-300 sm:min-h-[6.75rem] sm:p-2 sm:text-xs">{{ $cell['label'] }}</div>
                        @endif
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-[#E9EDF5] bg-[#FAFBFE] px-3 py-2.5 text-[10px] font-semibold text-[#64748B] sm:px-5 sm:text-xs">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-book text-blue-600"></i> Logbook</span>
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-600"></i> Attendance</span>
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-umbrella-beach text-amber-600"></i> Leave</span>
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-list-check text-indigo-600"></i> Task due</span>
                </div>

                <div class="hidden" aria-hidden="true">
                    @foreach ($cells as $cell)
                        @if ($cell['in_month'])
                            <div
                                data-cal-panel="{{ $cell['key'] }}"
                                data-cal-short="{{ \Carbon\Carbon::parse($cell['key'])->format('D j') }}"
                                data-cal-count="{{ count($calendar['agenda'][$cell['key']] ?? []) }}"
                                data-day-details="{{ json_encode($calendar['day_details'][$cell['key']] ?? []) }}"
                            ></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </details>
    </div>

    <aside class="order-2 flex min-w-0 flex-col gap-4">
        <details open data-mobile-fold="deadlines" class="group {{ $card }} p-4 sm:p-5">
            <summary class="mb-2 flex cursor-pointer list-none items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2F5BFF]">
                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-xl bg-[#E8EFFF] text-[#2F5BFF]">
                    <i class="fa-regular fa-clock"></i>
                </span>
                <h2 class="text-base font-extrabold">Upcoming deadlines</h2>
                <i class="fa-solid fa-chevron-down ml-auto text-xs text-[#5B6478] transition-transform group-open:rotate-180 md:hidden"></i>
            </summary>
            <div class="mb-3 flex justify-end">
                <a href="/student/calendar" class="whitespace-nowrap text-xs font-bold text-[#1F45D6] hover:underline">View all</a>
            </div>

            @if (count($deadlines) === 0)
                <p class="text-sm text-[#5B6478]">No upcoming deadlines.</p>
            @else
                <div class="divide-y divide-[#EDF0F7]">
                    @foreach ($deadlines as $deadline)
                        @php
                            $isSoon = $deadline['days'] <= 1;
                            $tone = $isSoon ? 'bg-[#FEF3C7] text-[#7C2D12]' : 'bg-[#E8EFFF] text-[#1F45D6]';
                            $when = $deadline['days'] <= 0
                                ? 'Today'
                                : ($deadline['days'] === 1 ? 'Tomorrow' : 'In '.$deadline['days'].' days');
                        @endphp
                        <div class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                            <div class="flex h-11 w-11 flex-none flex-col items-center justify-center rounded-xl {{ $tone }}">
                                <span class="text-[10px] font-bold uppercase">{{ $deadline['date']->format('M') }}</span>
                                <span class="text-base font-extrabold leading-none">{{ $deadline['date']->format('d') }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="break-words text-sm font-bold leading-snug">{{ $deadline['title'] }}</div>
                                <span class="mt-1 inline-block whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold {{ $tone }}">{{ $when }}</span>
                            </div>
                            <i class="fa-solid fa-chevron-right text-xs text-[#94A3B8]"></i>
                        </div>
                    @endforeach
                </div>
            @endif
        </details>

        <section class="{{ $card }} p-4 sm:p-5">
            <h2 class="mb-3 flex items-center gap-2 text-base font-extrabold">
                <i class="fa-solid fa-bolt text-[#2F5BFF]"></i>
                Quick actions
            </h2>
            <div class="grid grid-cols-2 gap-2">
                <a href="#new-entry" data-write-date="{{ $todayKey }}" class="flex min-h-[72px] flex-col items-center justify-center gap-2 rounded-2xl bg-[#E8EFFF] p-2 text-center text-xs font-bold text-[#1F45D6] transition hover:brightness-95">
                    <i class="fa-solid fa-book-open text-lg"></i>
                    New logbook entry
                </a>
                <a href="/student/calendar" class="flex min-h-[72px] flex-col items-center justify-center gap-2 rounded-2xl bg-[#D1FAE5] p-2 text-center text-xs font-bold text-[#047857] transition hover:brightness-95">
                    <i class="fa-solid fa-calendar-days text-lg"></i>
                    View calendar
                </a>
                <a href="#new-entry" data-focus="attachment" class="flex min-h-[72px] flex-col items-center justify-center gap-2 rounded-2xl bg-[#F0EAFF] p-2 text-center text-xs font-bold text-[#6D28D9] transition hover:brightness-95">
                    <i class="fa-solid fa-cloud-arrow-up text-lg"></i>
                    Upload files
                </a>
                <a href="{{ $chatHref }}" class="flex min-h-[72px] flex-col items-center justify-center gap-2 rounded-2xl bg-[#FFF1E6] p-2 text-center text-xs font-bold text-[#C2410C] transition hover:brightness-95">
                    <i class="fa-solid fa-comments text-lg"></i>
                    Chat with supervisor
                </a>
            </div>
        </section>

        <div
            id="dashboard-calendar-popup"
            role="dialog"
            aria-modal="true"
            aria-labelledby="dashboard-calendar-popup-title"
            tabindex="-1"
            class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/45 p-3 backdrop-blur-sm sm:p-5"
        >
            <div class="flex max-h-[92vh] w-full max-w-[500px] flex-col overflow-hidden rounded-3xl border border-[#E0E7F2] bg-white shadow-[0_24px_70px_rgba(15,27,77,0.28)]">
                <div class="flex items-start justify-between gap-3 border-b border-[#EDF0F7] px-4 py-3.5 sm:px-5">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-xl bg-[#E8EFFF] text-[#2F5BFF]">
                            <i class="fa-regular fa-calendar text-xl"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 id="dashboard-calendar-popup-title" data-popup-title class="text-base font-extrabold text-[#1E293B]"></h2>
                            <p data-popup-subtitle class="mt-0.5 text-xs text-[#64748B]"></p>
                        </div>
                    </div>
                    <button type="button" data-popup-close aria-label="Close day details" class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4 sm:p-5">
                    <div class="grid grid-cols-4 divide-x divide-[#D9DDF0] rounded-2xl bg-[#F6F7FF] px-2 py-3.5 text-center">
                        <div class="px-1">
                            <i class="fa-solid fa-book-open text-lg text-blue-600"></i>
                            <b data-popup-logs class="mt-1 block text-base leading-none text-[#1E293B]">0</b>
                            <span class="mt-1 block text-[10px] text-[#64748B]">Logbook</span>
                        </div>
                        <div class="px-1">
                            <i class="fa-solid fa-circle-check text-lg text-emerald-600"></i>
                            <b data-popup-attendance-count class="mt-1 block text-base leading-none text-[#1E293B]">0</b>
                            <span class="mt-1 block text-[10px] text-[#64748B]">Attendance</span>
                        </div>
                        <div class="px-1">
                            <i class="fa-solid fa-umbrella-beach text-lg text-amber-600"></i>
                            <b data-popup-leave class="mt-1 block text-base leading-none text-[#1E293B]">0</b>
                            <span class="mt-1 block text-[10px] text-[#64748B]">Leave</span>
                        </div>
                        <div class="px-1">
                            <i class="fa-solid fa-list-check text-lg text-violet-600"></i>
                            <b data-popup-tasks-count class="mt-1 block text-base leading-none text-[#1E293B]">0</b>
                            <span class="mt-1 block text-[10px] text-[#64748B]">Tasks</span>
                        </div>
                    </div>

                    <section data-popup-location-card class="rounded-2xl border border-[#E7ECF5] bg-white p-3">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-extrabold text-[#1E293B]"><i class="fa-solid fa-location-dot mr-1.5 text-blue-600"></i>Attendance Details</h3>
                            <span data-popup-attendance-status class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700"></span>
                        </div>
                        <div class="mb-3 flex flex-wrap items-end gap-x-5 gap-y-2">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#64748B]">Clock in</p>
                                <p data-popup-check-in class="text-sm font-extrabold text-[#1E293B]"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#64748B]">Clock out</p>
                                <p data-popup-check-out class="text-sm font-extrabold text-[#1E293B]"></p>
                            </div>
                            <span data-popup-work-mode class="hidden rounded-full bg-[#E8EFFF] px-2.5 py-1 text-[11px] font-bold text-[#1F45D6]"></span>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-[1.2fr_1fr] sm:items-center">
                            <iframe data-popup-map title="Attendance location map" loading="lazy" referrerpolicy="no-referrer" class="hidden h-32 w-full rounded-xl border border-[#E3E8F2] sm:h-36"></iframe>
                            <div class="min-w-0">
                                <h4 class="mb-1 text-xs font-bold text-[#334155]"><i class="fa-solid fa-location-dot mr-1 text-blue-600"></i>Location</h4>
                                <p data-popup-location-name class="break-words text-xs leading-5 text-[#64748B]"></p>
                                <a data-popup-map-link target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-[11px] font-bold text-[#3158D8] hover:underline">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Open map
                                </a>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-[#E7ECF5] bg-[#FAFBFE] p-3">
                            <h3 class="mb-2 text-xs font-extrabold text-[#1E293B]"><i class="fa-regular fa-note-sticky mr-1.5 text-sm text-violet-600"></i>Daily Notes</h3>
                            <p data-popup-note class="text-xs leading-5 text-[#475569]"></p>
                            <div data-popup-note-images class="mt-2 grid grid-cols-3 gap-1.5"></div>
                        </div>
                        <div class="rounded-2xl border border-[#E7ECF5] bg-[#FAFBFE] p-3">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <h3 class="text-xs font-extrabold text-[#1E293B]"><i class="fa-solid fa-list-check mr-1.5 text-sm text-blue-600"></i>Tasks for the Day</h3>
                                <span data-popup-task-progress class="text-[11px] font-bold text-[#64748B]"></span>
                            </div>
                            <div data-popup-tasks class="space-y-1.5"></div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-[#DDE5F2] bg-gradient-to-r from-[#F8FAFF] to-[#F3F6FF] p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h3 class="text-xs font-extrabold text-[#1E293B]"><i class="fa-solid fa-book-open mr-1.5 text-sm text-[#3158D8]"></i>Logbook Preview</h3>
                            <a data-popup-logbook-link class="whitespace-nowrap text-[11px] font-bold text-[#3158D8] hover:underline">View Details <i class="fa-solid fa-arrow-right ml-0.5"></i></a>
                        </div>
                        <div data-popup-logbook class="space-y-2"></div>
                    </section>
                </div>

                <div class="border-t border-[#EDF0F7] bg-[#FAFBFE] p-3 sm:px-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-[#EEF3FF] px-3 py-2.5">
                        <p class="min-w-0 flex-1 text-xs leading-4 text-[#475569]">
                            <i class="fa-solid fa-lightbulb mr-1 text-blue-600"></i>
                            Need to manage this date? Go to the full calendar for more options.
                        </p>
                        <a data-popup-full-calendar class="inline-flex min-h-9 items-center gap-2 rounded-xl bg-[#3158D8] px-3.5 text-xs font-bold text-white transition hover:bg-blue-700">
                            View Full Calendar <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <section class="{{ $card }} p-4 sm:p-5">
            <div class="mb-2 flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 text-base font-extrabold">
                    <i class="fa-solid fa-wave-square text-[#2F5BFF]"></i>
                    Recent activities
                </h2>
                <a href="/student/logbooks" class="whitespace-nowrap text-xs font-bold text-[#1F45D6] hover:underline">View all</a>
            </div>
            @forelse ($recentActivities as $activity)
                <a href="{{ $activity['href'] }}" class="flex items-center gap-3 border-t border-[#EDF0F7] py-2.5 first:border-0">
                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl {{ $activity['tone'] }}">
                        <i class="fa-solid {{ $activity['icon'] }}"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-[#1E293B]">{{ $activity['label'] }}</span>
                        <span class="mt-0.5 block text-[10px] text-[#64748B]">
                            {{ $activity['date']->isToday() ? 'Today, '.$activity['date']->format('g:i A') : $activity['date']->format('M j, Y') }}
                        </span>
                    </span>
                </a>
            @empty
                <p class="border-t border-[#EDF0F7] pt-3 text-sm text-[#5B6478]">Your recent activity will appear here.</p>
            @endforelse
        </section>

    </aside>
</section>

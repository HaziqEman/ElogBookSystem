    {{-- HERO --}}
    <section class="flex flex-wrap items-center justify-between gap-3 overflow-hidden rounded-3xl bg-gradient-to-r from-[#2F5BFF] to-[#4F46E5] px-6 py-7 text-white md:px-9">
        <div class="min-w-0 flex-1 basis-72">
            <div class="text-[13px] font-medium text-[#DCE4FF]">{{ $today->format('l, d F Y') }}</div>
            <h1 class="mb-2 mt-1.5 break-words text-3xl font-extrabold tracking-tight">Welcome back, {{ $firstName }}!</h1>
            <p class="max-w-md text-sm leading-relaxed text-[#E4EAFF]">Keep up the great work. Stay consistent with your logbook, attend your sessions and make the most of your internship journey.</p>
        </div>
        <div class="hidden shrink-0 items-center gap-1.5 md:flex">
            <div class="text-right text-[28px] leading-[1.05] text-white" style="font-family:'Caveat',cursive;transform:rotate(-6deg)">Better<br>skills,<br>brighter<br>future</div>
            <svg width="200" height="136" viewBox="0 0 190 130" aria-hidden="true">
                <ellipse cx="95" cy="118" rx="80" ry="8" fill="#1B2A7A" opacity=".45"/>
                <rect x="38" y="30" width="96" height="62" rx="7" fill="#0F1B4D"/>
                <rect x="44" y="36" width="84" height="50" rx="4" fill="#5B8CFF"/>
                <circle cx="86" cy="61" r="9" fill="#fff" opacity=".9"/>
                <path d="M24 94h124l-8 12H32z" fill="#C7D4FF"/>
                <rect x="146" y="88" width="30" height="28" rx="6" fill="#EEF2FF"/>
                <path d="M161 88c-10-8-12-20-6-30 6 10 14 12 8 30zM161 88c10-6 18-4 20-14-10 0-18 6-20 14z" fill="#34D399"/>
                <rect x="12" y="84" width="22" height="26" rx="5" fill="#0F1B4D"/>
                <rect x="16" y="90" width="14" height="4" rx="2" fill="#7FA1FF"/>
            </svg>
        </div>
    </section>

    {{-- ATTENDANCE | QUICK ACTIONS + AI --}}
    <section class="flex flex-wrap items-stretch gap-5">
        <div class="flex min-w-0 flex-[1.3_1_420px] flex-col gap-4 [&>#attendance-card]:flex-1">
            @if($extrasReady)
                @include('student.partials.attendance-card')
            @else
                <div class="rounded-[22px] border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    Attendance and to-do tables are not set up yet. Ask the administrator to run the database update.
                </div>
            @endif
        </div>

        <div class="flex min-w-0 flex-[1_1_360px] flex-col gap-4">
            <div class="p-5 {{ $card }}">
                <h2 class="mb-3.5 text-base font-extrabold">Quick actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="#new-entry" data-write-date="{{ $todayKey }}" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#D3E0FF] bg-[#E8EFFF] p-3.5 transition hover:border-[#2F5BFF]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#2F5BFF] text-white"><i class="fa-solid fa-plus"></i></span>
                        <span class="text-[13px] font-bold leading-tight">New logbook entry</span>
                    </a>
                    <a href="/student/calendar" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#BFEBD3] bg-[#E3F7EC] p-3.5 transition hover:border-[#047857]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#047857] text-white"><i class="fa-solid fa-calendar-days"></i></span>
                        <span class="text-[13px] font-bold leading-tight">View calendar</span>
                    </a>
                    <a href="#new-entry" data-focus="attachment" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#DDD0FB] bg-[#F0EAFF] p-3.5 transition hover:border-[#6D28D9]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#6D28D9] text-white"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                        <span class="text-[13px] font-bold leading-tight">Upload files</span>
                    </a>
                    <a href="{{ $chatHref }}" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#FBCFCB] bg-[#FFE9E7] p-3.5 transition hover:border-[#BE123C]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#BE123C] text-white"><i class="fa-solid fa-comment"></i></span>
                        <span class="text-[13px] font-bold leading-tight">Chat with supervisor</span>
                    </a>
                </div>
            </div>

            @if($aiReady)
                <div class="flex flex-col gap-2 rounded-[20px] border border-[#E3E8F2] bg-white px-4 py-3.5 shadow-[0_6px_18px_rgba(15,27,77,0.05)]">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-[10px] bg-[#F0EAFF] text-[#6D28D9]"><i class="fa-solid fa-wand-magic-sparkles text-sm"></i></span>
                        <div class="min-w-0 flex-1 text-sm font-extrabold">AI Help &middot; Gemini</div>
                        <div class="whitespace-nowrap text-[13px] font-bold text-[#334155]"><span id="ai-left" data-limit="{{ $aiLimit }}" class="text-base font-extrabold" style="color:{{ $aiColor }}">{{ $aiLeft }}</span> / {{ $aiLimit }} left</div>
                    </div>
                    <div class="h-2 overflow-hidden rounded-[5px] bg-[#EEE9FB]">
                        <div id="ai-bar" class="h-full rounded-[5px]" style="width:{{ $aiPct }}%;background:{{ $aiColor }}"></div>
                    </div>
                    <div class="text-xs text-[#5B6478]">Resets every day at midnight</div>
                </div>
            @endif
        </div>
    </section>

    {{-- STATS (full-width row) --}}
    <div id="stats-grid" data-live class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex items-center gap-3.5 rounded-[20px] border border-[#D3E0FF] bg-[#E8EFFF] px-[18px] py-4">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#1F45D6]"><i class="fa-solid fa-file-lines"></i></span>
                    <div><div class="text-xs font-semibold text-[#334155]">Total entries</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalLogs }}</div></div>
                </div>
                <div class="flex items-center gap-3.5 rounded-[20px] border border-[#BFEBD3] bg-[#E3F7EC] px-[18px] py-4">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#047857]"><i class="fa-solid fa-paperclip"></i></span>
                    <div><div class="text-xs font-semibold text-[#334155]">Files uploaded</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalAttachments }}</div></div>
                </div>
                <a href="/student/feedback" class="flex items-center gap-3.5 rounded-[20px] border border-[#DDD0FB] bg-[#F0EAFF] px-[18px] py-4 transition hover:border-[#6D28D9]">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#6D28D9]"><i class="fa-solid fa-comment-dots"></i></span>
                    <div><div class="text-xs font-semibold text-[#334155]">Reviews</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalReviews }}</div></div>
                </a>
                <a href="/student/messages" class="flex items-center gap-3.5 rounded-[20px] border border-[#FBCFCB] bg-[#FFE9E7] px-[18px] py-4 transition hover:border-[#BE123C]">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#BE123C]"><i class="fa-solid fa-envelope"></i></span>
                    <div><div class="text-xs font-semibold text-[#334155]">Unread messages</div><div class="text-[28px] font-extrabold leading-tight">{{ $unreadMessages }}</div></div>
                </a>
    </div>

    {{-- CALENDAR | TO-DO + UPCOMING DEADLINES --}}
    <section class="flex flex-wrap items-stretch gap-5">
        <div id="calendar-card" data-live data-today="{{ $todayKey }}" class="flex min-w-0 flex-[1_1_520px] flex-col px-6 py-6 {{ $card }}">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-extrabold">My calendar</h2>
                <div class="flex items-center gap-2.5">
                    <a href="/student/calendar?month={{ $calendar['prev'] }}" aria-label="Previous month" class="flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E3E8F2] bg-white transition hover:bg-[#F3F5FC]"><i class="fa-solid fa-chevron-left text-xs"></i></a>
                    <div class="min-w-[116px] text-center text-[15px] font-extrabold">{{ $calendar['title'] }}</div>
                    <a href="/student/calendar?month={{ $calendar['next'] }}" aria-label="Next month" class="flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E3E8F2] bg-white transition hover:bg-[#F3F5FC]"><i class="fa-solid fa-chevron-right text-xs"></i></a>
                </div>
                <a href="/student/calendar" class="flex min-h-8 items-center text-[13px] font-bold text-[#1F45D6] hover:underline">Full calendar</a>
            </div>

            <div class="mb-1.5 grid grid-cols-7 gap-1.5 text-center text-xs font-bold text-[#5B6478]">
                <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
            </div>
            <div class="grid grid-cols-7 gap-1.5">
                @foreach($calendar['cells'] as $cell)
                    @if($cell['in_month'])
                        <button type="button" data-cal-day="{{ $cell['key'] }}" aria-label="{{ $cell['long'] }}" class="cal-day {{ $cell['is_today'] ? 'is-today' : '' }} flex h-14 flex-col items-center justify-center gap-1 rounded-xl border border-[#EEF0FA] bg-[#F7F9FD] text-sm font-bold text-[#1E293B] transition hover:border-[#9DB4FF]">
                            <span>{{ $cell['label'] }}</span>
                            <span class="flex h-1.5 items-center gap-[3px]">
                                @if($cell['has_log'])<span class="cal-dot h-1.5 w-1.5 rounded-full bg-[#2F5BFF]"></span>@endif
                                @if($cell['has_due'])<span class="cal-dot h-1.5 w-1.5 rounded-full bg-[#C2410C]"></span>@endif
                            </span>
                        </button>
                    @else
                        <div class="flex h-14 items-center justify-center rounded-xl text-sm font-bold text-[#8A92B8]">{{ $cell['label'] }}</div>
                    @endif
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap gap-3.5 text-xs font-semibold text-[#5B6478]">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2F5BFF]"></span>Log entry</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#C2410C]"></span>Task due</span>
            </div>

            {{-- Day agenda: fixed height, the list scrolls inside so the card never grows --}}
            <div class="mt-4 h-[230px] rounded-2xl bg-[#F7F9FD] p-4">
                @foreach($calendar['cells'] as $cell)
                    @if($cell['in_month'])
                        @php $items = $calendar['agenda'][$cell['key']] ?? []; @endphp
                        <div data-cal-panel="{{ $cell['key'] }}" class="hidden h-full">
                            <div class="flex h-full flex-col">
                                <div class="flex flex-none items-baseline justify-between gap-2">
                                    <div class="text-[15px] font-extrabold">{{ $cell['long'] }}</div>
                                    <div class="text-xs font-semibold text-[#5B6478]">{{ count($items) === 0 ? 'Free day' : count($items).(count($items) === 1 ? ' item' : ' items') }}</div>
                                </div>
                                <div class="mt-3 flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto pr-1 [scrollbar-width:thin]">
                                    @forelse($items as $item)
                                        @php $bar = ['todo' => 'bg-[#2F5BFF]', 'log' => 'bg-[#047857]', 'due' => 'bg-[#C2410C]'][$item['type']] ?? 'bg-[#94A3B8]'; @endphp
                                        <div class="flex flex-none items-center justify-between gap-3 rounded-xl border border-[#E3E8F2] bg-white px-3.5 py-2.5">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <span class="h-8 w-1 flex-none rounded {{ $bar }}"></span>
                                                <div class="min-w-0">
                                                    <div class="break-words text-sm font-bold">{{ $item['title'] }}</div>
                                                    <div class="break-words text-xs text-[#5B6478]">{{ $item['meta'] }}</div>
                                                </div>
                                            </div>
                                            @if(! empty($item['action']))
                                                <a href="{{ $item['action']['href'] }}" @if(! empty($item['action']['date'])) data-write-date="{{ $item['action']['date'] }}" @endif class="flex-none rounded-[10px] bg-[#2F5BFF] px-3.5 py-2 text-xs font-bold text-white transition hover:bg-[#1F45D6]">{{ $item['action']['label'] }}</a>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="flex flex-none items-center gap-3 rounded-xl border border-[#E3E8F2] bg-white px-3.5 py-2.5">
                                            <span class="h-8 w-1 flex-none rounded bg-[#CBD3E3]"></span>
                                            <div>
                                                <div class="text-sm font-bold">Nothing scheduled</div>
                                                <div class="text-xs text-[#5B6478]">No log entry or task due on this day.</div>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="flex min-w-0 flex-[1_1_440px] flex-col gap-5">
            @include('student.partials.todo-card')

            <div id="deadlines-card" data-live class="px-[22px] py-5 {{ $card }}">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-base font-extrabold">Upcoming deadlines</h2>
                    <a href="/student/calendar" class="text-[13px] font-bold text-[#1F45D6] hover:underline">View all</a>
                </div>
                <div class="flex flex-col gap-2.5">
                    @forelse($deadlines as $deadline)
                        @php
                            $soon = $deadline['days'] <= 1;
                            $tone = $soon ? 'bg-[#FEF3C7] text-[#7C2D12]' : 'bg-[#E8EFFF] text-[#1F45D6]';
                            $chip = $deadline['days'] <= 0 ? 'Today' : ($deadline['days'] === 1 ? 'Tomorrow' : 'In '.$deadline['days'].' days');
                        @endphp
                        <div class="flex items-center gap-3.5">
                            <div class="flex h-[52px] w-12 flex-none flex-col items-center justify-center gap-0.5 rounded-xl {{ $tone }}">
                                <span class="text-[11px] font-bold uppercase">{{ $deadline['date']->format('M') }}</span>
                                <span class="text-lg font-extrabold leading-none">{{ $deadline['date']->format('d') }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="break-words text-sm font-bold">{{ $deadline['title'] }}</div>
                                <div class="break-words text-xs text-[#5B6478]">{{ $deadline['meta'] }}</div>
                            </div>
                            <span class="flex-none whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold {{ $tone }}">{{ $chip }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-[#5B6478]">No upcoming deadlines.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

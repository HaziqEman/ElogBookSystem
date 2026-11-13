{{--
    resources/views/student/partials/planner-card.blade.php

    One card:
      LEFT   : My calendar (month grid)
      RIGHT  : To-do list with 4 tabs: selected day | All | Overdue | Upcoming
      BOTTOM : Upcoming deadlines (one row)

    Replaces the old calendar card, to-do card and deadlines card.

    Hooks used by student-dashboard.js:
      #calendar-card, #todo-list, #deadlines-card   (data-live, siblings, never nested)
      data-cal-day, data-cal-panel, .cal-day, .cal-dot
      data-todo-tab, data-todo-view, data-todo-row (data-state), data-todo-empty
      data-day-label, data-day-count
--}}

@php
    /* ---------- Shared styles ---------- */
    $shell  = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $navBtn = 'flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E3E8F2] bg-white transition hover:bg-[#F3F5FC]';
    $link   = 'text-[13px] font-bold text-[#1F45D6] hover:underline';
    $input  = 'min-h-11 min-w-0 flex-1 rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none';
    $tabBtn = 'flex h-9 min-w-0 flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-[9px] px-1 text-[13px] font-bold transition';
    $listH  = 'h-[252px]';                              // same height for the task list and the day panel

    /* ---------- Calendar ---------- */
    $cells     = $calendar['cells'];
    $weeks     = (int) ceil(count($cells) / 7);          // 5 or 6 rows, the grid stretches to fill the card
    $agendaBar = ['todo' => 'bg-[#2F5BFF]', 'log' => 'bg-[#047857]', 'due' => 'bg-[#C2410C]'];
    $todayCount = count($calendar['agenda'][$todayKey] ?? []);
    $todayShort = \Carbon\Carbon::parse($todayKey)->format('D j');

    /* ---------- To-do tabs (the day tab is added in the markup) ---------- */
    $tabs = [
        'all'      => ['label' => 'All',      'count' => $openCount + $doneCount],
        'overdue'  => ['label' => 'Overdue',  'count' => $overdueCount],
        'upcoming' => ['label' => 'Upcoming', 'count' => $upcomingCount],
    ];

    /* State + badge for one to-do row: [state, badge text, badge classes] */
    $todoBadge = function ($todo) use ($todayKey) {
        $due      = $todo->due_date ? $todo->due_date->toDateString() : null;
        $tomorrow = \Carbon\Carbon::parse($todayKey)->addDay()->toDateString();

        if ($todo->is_done) {
            return ['done', 'Done', 'bg-[#D1FAE5] text-[#065F46]'];
        }
        if ($due && $due < $todayKey) {
            $days = (int) \Carbon\Carbon::parse($due)->diffInDays(\Carbon\Carbon::parse($todayKey));
            return ['overdue', 'Overdue ' . $days . 'd', 'bg-[#FFE4E6] text-[#9F1239]'];
        }
        if ($due === $todayKey) {
            return ['upcoming', 'Today', 'bg-[#FEF3C7] text-[#7C2D12]'];
        }
        if ($due === $tomorrow) {
            return ['upcoming', 'Tomorrow', 'bg-[#FEF3C7] text-[#7C2D12]'];
        }
        if ($due) {
            return ['upcoming', 'Upcoming', 'bg-[#E8EFFF] text-[#1F45D6]'];
        }
        return ['upcoming', 'No date', 'bg-[#EEF1F7] text-[#5B6478]'];
    };
@endphp

<section class="p-6 {{ $shell }}">
    {{-- ========================= TOP: UPCOMING DEADLINES ========================= --}}
    <div id="deadlines-card" data-live>
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-extrabold">Upcoming deadlines</h2>
            <a href="/student/calendar" class="{{ $link }}">View all</a>
        </div>

        @if(count($deadlines) === 0)
            <p class="text-sm text-[#5B6478]">No upcoming deadlines.</p>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($deadlines as $deadline)
                    @php
                        $isSoon = $deadline['days'] <= 1;
                        $tone = $isSoon
                            ? 'bg-[#FEF3C7] text-[#7C2D12]'
                            : 'bg-[#E8EFFF] text-[#1F45D6]';

                        $when = $deadline['days'] <= 0
                            ? 'Today'
                            : ($deadline['days'] === 1
                                ? 'Tomorrow'
                                : 'In ' . $deadline['days'] . ' days');
                    @endphp

                    <div class="flex items-center gap-3 rounded-2xl border border-[#E3E8F2] bg-[#F7F9FD] p-3">
                        <div class="flex h-[52px] w-12 flex-none flex-col items-center justify-center gap-0.5 rounded-xl {{ $tone }}">
                            <span class="text-[11px] font-bold uppercase">{{ $deadline['date']->format('M') }}</span>
                            <span class="text-lg font-extrabold leading-none">{{ $deadline['date']->format('d') }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="break-words text-sm font-bold leading-snug">
                                {{ $deadline['title'] }}
                            </div>
                            <span class="mt-1 inline-block whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-bold {{ $tone }}">
                                {{ $when }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ========================= SECTION SPACING ========================= --}}
    <div aria-hidden="true" class="my-8 border-t border-[#E3E8F2]"></div>

    {{-- ========================= CALENDAR + TODO ========================= --}}
    <div class="grid grid-cols-1 items-stretch gap-6 pt-2 xl:grid-cols-2 xl:gap-0">

        {{-- ============================== LEFT: CALENDAR ============================== --}}
        <div
            id="calendar-card"
            data-live
            data-today="{{ $todayKey }}"
            class="flex min-w-0 flex-col xl:pr-6"
        >

            {{-- Header: title, month switch, full calendar link --}}
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-extrabold">My calendar</h2>
                <div class="flex items-center gap-2.5">
                    <a href="/student/calendar?month={{ $calendar['prev'] }}" aria-label="Previous month" class="{{ $navBtn }}"><i class="fa-solid fa-chevron-left text-xs"></i></a>
                    <div class="min-w-[116px] text-center text-[15px] font-extrabold">{{ $calendar['title'] }}</div>
                    <a href="/student/calendar?month={{ $calendar['next'] }}" aria-label="Next month" class="{{ $navBtn }}"><i class="fa-solid fa-chevron-right text-xs"></i></a>
                </div>
                <a href="/student/calendar" class="flex min-h-8 items-center {{ $link }}">Full calendar</a>
            </div>

            {{-- Weekday names --}}
            <div class="mb-1.5 grid grid-cols-7 gap-1.5 text-center text-xs font-bold text-[#5B6478]">
                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                    <div>{{ $weekday }}</div>
                @endforeach
            </div>

            {{-- Month grid: flex-1 so it stretches to the height of the to-do column (no empty gap) --}}
            <div class="grid flex-1 grid-cols-7 gap-1.5" style="grid-template-rows: repeat({{ $weeks }}, minmax(48px, 1fr));">
                @foreach($cells as $cell)
                    @if($cell['in_month'])
                        <button type="button" data-cal-day="{{ $cell['key'] }}" aria-label="{{ $cell['long'] }}"
                                class="cal-day {{ $cell['is_today'] ? 'is-today' : '' }} flex h-full flex-col items-center justify-center gap-1 rounded-xl border border-[#EEF0FA] bg-[#F7F9FD] text-sm font-bold text-[#1E293B] transition hover:border-[#9DB4FF]">
                            <span>{{ $cell['label'] }}</span>
                            <span class="flex h-1.5 items-center gap-[3px]">
                                @if($cell['has_log'])<span class="cal-dot h-1.5 w-1.5 rounded-full bg-[#2F5BFF]"></span>@endif
                                @if($cell['has_due'])<span class="cal-dot h-1.5 w-1.5 rounded-full bg-[#C2410C]"></span>@endif
                            </span>
                        </button>
                    @else
                        <div class="flex h-full items-center justify-center rounded-xl text-sm font-bold text-[#8A92B8]">{{ $cell['label'] }}</div>
                    @endif
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="mt-3 flex flex-wrap gap-3.5 text-xs font-semibold text-[#5B6478]">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2F5BFF]"></span>Log entry</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#C2410C]"></span>Task due</span>
            </div>
        </div>

        {{-- ============================ RIGHT: TO-DO + DAY ============================ --}}
        <div id="todo-list" data-live class="flex min-w-0 flex-col border-t border-[#EDF0F7] pt-6 xl:border-l xl:border-t-0 xl:pl-6 xl:pt-0">

            <div class="mb-3 flex min-h-9 items-center justify-between">
                <h2 class="text-lg font-extrabold">To-do list</h2>
                <span class="text-xs font-bold text-[#5B6478]">{{ $openCount }} open</span>
            </div>
            <p class="mb-3 text-[11px] text-[#5B6478]">Your own tasks are private. Your lecturer only sees how many you finish, and your supervisor only sees the tasks they assigned.</p>

            @if($extrasReady)
                {{-- Tabs: the JS switches between the day panel and the task list --}}
                <div class="mb-3 flex gap-1 rounded-xl bg-[#F7F9FD] p-1" role="group" aria-label="Day and task filters">
                    <button type="button" data-todo-tab="day" aria-pressed="false" class="{{ $tabBtn }} text-[#5B6478]">
                        <span data-day-label>{{ $todayShort }}</span>
                        <span data-day-count class="rounded-lg bg-[#E8EFFF] px-1.5 py-0.5 text-[11px] font-bold text-[#1F45D6]">{{ $todayCount }}</span>
                    </button>

                    @foreach($tabs as $key => $tab)
                        <button type="button" data-todo-tab="{{ $key }}" aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}"
                                class="{{ $tabBtn }} {{ $key === 'all' ? 'bg-white text-[#1E293B] shadow-sm' : 'text-[#5B6478]' }}">
                            {{ $tab['label'] }}
                            <span class="rounded-lg px-1.5 py-0.5 text-[11px] font-bold {{ $key === 'overdue' ? 'bg-[#FFE4E6] text-[#9F1239]' : 'bg-[#E8EFFF] text-[#1F45D6]' }}">{{ $tab['count'] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- ---------- View 1: selected-day panels (shown by the day tab) ---------- --}}
                <div data-todo-view="day" class="hidden">
                    @foreach($cells as $cell)
                        @continue(! $cell['in_month'])
                        @php
                            $items     = $calendar['agenda'][$cell['key']] ?? [];
                            $itemCount = count($items);
                        @endphp

                        <div data-cal-panel="{{ $cell['key'] }}"
                             data-cal-short="{{ \Carbon\Carbon::parse($cell['key'])->format('D j') }}"
                             data-cal-count="{{ $itemCount }}"
                             class="hidden {{ $listH }} rounded-2xl bg-[#F7F9FD] p-4">
                            <div class="flex h-full flex-col">
                                <div class="flex flex-none items-baseline justify-between gap-2">
                                    <div class="text-[15px] font-extrabold">{{ $cell['long'] }}</div>
                                    <div class="text-xs font-semibold text-[#5B6478]">
                                        {{ $itemCount === 0 ? 'Free day' : $itemCount . ($itemCount === 1 ? ' item' : ' items') }}
                                    </div>
                                </div>

                                <div class="mt-3 flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto pr-1 [scrollbar-width:thin]">
                                    @forelse($items as $item)
                                        <div class="flex flex-none items-center justify-between gap-3 rounded-xl border border-[#E3E8F2] bg-white px-3.5 py-2.5">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <span class="h-8 w-1 flex-none rounded {{ $agendaBar[$item['type']] ?? 'bg-[#94A3B8]' }}"></span>
                                                <div class="min-w-0">
                                                    <div class="break-words text-sm font-bold">{{ $item['title'] }}</div>
                                                    <div class="break-words text-xs text-[#5B6478]">{{ $item['meta'] }}</div>
                                                </div>
                                            </div>
                                            @if(! empty($item['action']))
                                                <a href="{{ $item['action']['href'] }}"
                                                   @if(! empty($item['action']['date'])) data-write-date="{{ $item['action']['date'] }}" @endif
                                                   class="flex-none rounded-[10px] bg-[#2F5BFF] px-3.5 py-2 text-xs font-bold text-white transition hover:bg-[#1F45D6]">{{ $item['action']['label'] }}</a>
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
                    @endforeach
                </div>

                {{-- ---------- View 2: task list (All / Overdue / Upcoming) ---------- --}}
                <div data-todo-view="list">
                    {{-- Fixed height and scrolls, so many tasks never make the card taller --}}
                    <div class="flex {{ $listH }} flex-col gap-2 overflow-y-auto pr-1 [scrollbar-width:thin]">
                        @foreach($openTodos->concat($doneTodos) as $todo)
                            @php
                                [$state, $badgeText, $badgeClass] = $todoBadge($todo);
                                $isDone   = $state === 'done';
                                $assigned = ! empty($todo->supervisor_id);
                            @endphp

                            <div data-todo-row data-state="{{ $state }}">
                                <div class="flex min-h-[52px] items-center gap-3 rounded-xl border border-[#E3E8F2] bg-white px-3 py-2">

                                    {{-- Tick box --}}
                                    <form method="POST" action="/student/todos/{{ $todo->todo_id }}/toggle" data-async>
                                        @csrf
                                        @if($isDone)
                                            <button type="submit" aria-label="Mark as not done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] bg-[#047857] text-white"><i class="fa-solid fa-check text-[11px]"></i></button>
                                        @else
                                            <button type="submit" aria-label="Mark as done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] border-2 border-[#94A3B8] bg-white text-transparent transition hover:border-[#047857] hover:text-[#047857]"><i class="fa-solid fa-check text-[11px]"></i></button>
                                        @endif
                                    </form>

                                    {{-- Title (+ supervisor tag) --}}
                                    <div class="min-w-0 flex-1">
                                        <div class="break-words text-sm font-medium {{ $isDone ? 'text-[#64748B] line-through' : '' }}">{{ $todo->title }}</div>
                                        @if($assigned)
                                            <span class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-[#E0E7FF] px-2 py-0.5 text-[10px] font-bold text-[#3730A3]"><i class="fa-solid fa-building-user"></i> From {{ optional($todo->supervisor)->name ?? 'your supervisor' }}</span>
                                        @endif
                                    </div>

                                    {{-- Status badge + due date --}}
                                    <div class="flex flex-none flex-col items-end gap-1">
                                        <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-bold {{ $badgeClass }}">{{ $badgeText }}</span>
                                        <span class="whitespace-nowrap text-[11px] font-medium text-[#5B6478]">{{ $todo->due_date ? 'Due ' . $todo->due_date->format('d M') : 'No due date' }}</span>
                                    </div>

                                    {{-- Lock (assigned by supervisor) or delete --}}
                                    @if($assigned)
                                        <span title="Assigned by your supervisor" class="text-[#CBD3E3]"><i class="fa-solid fa-lock"></i></span>
                                    @else
                                        <form method="POST" action="/student/todos/{{ $todo->todo_id }}" data-async onsubmit="return confirm('Delete this task?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Delete task" class="text-[#CBD3E3] transition hover:text-[#BE123C]"><i class="fa-solid fa-xmark"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <p data-todo-empty class="{{ ($openTodos->count() + $doneTodos->count()) > 0 ? 'hidden' : '' }} py-3 text-center text-sm text-[#5B6478]">No tasks here.</p>
                    </div>

                    @if($openCount > $openTodos->count())
                        <p class="mt-2 text-xs text-[#5B6478]">+ {{ $openCount - $openTodos->count() }} more open tasks</p>
                    @endif
                </div>

                {{-- ---------- Add a task, with an optional due date (always visible) ---------- --}}
                <form method="POST" action="/student/todos" data-async class="mt-4 space-y-2 border-t border-[#EDF0F7] pt-4">
                    @csrf
                    <div class="flex gap-2">
                        <input type="text" name="title" required maxlength="150" placeholder="Add a task" aria-label="New task" class="{{ $input }}">
                        <button type="submit" class="min-h-11 rounded-xl bg-[#2F5BFF] px-5 text-sm font-bold text-white transition hover:bg-[#1F45D6] disabled:opacity-60">Add</button>
                    </div>
                    <label class="flex items-center gap-2.5 text-xs font-semibold text-[#334155]">
                        <span class="flex flex-none items-center gap-1.5 text-[#5B6478]"><i class="fa-regular fa-calendar text-[#1F45D6]"></i> Due date <span class="font-normal">(optional)</span></span>
                        <input type="date" name="due_date" aria-label="Due date (optional)" class="{{ $input }} min-h-10 py-2 text-[#334155]">
                    </label>
                </form>
            @else
                <p class="text-sm text-amber-800">The to-do table is not set up yet.</p>
            @endif
        </div>
    </div>

    
</section>

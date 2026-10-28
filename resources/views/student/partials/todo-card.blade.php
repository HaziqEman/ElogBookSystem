@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $tabCount = ['all' => $openCount + $doneCount, 'overdue' => $overdueCount, 'upcoming' => $upcomingCount];
@endphp

<div id="todo-list" data-live class="min-w-0 px-[22px] py-5 {{ $card }}">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-base font-extrabold">To-do list</h2>
        <span class="text-xs font-bold text-[#5B6478]">{{ $openCount }} open</span>
    </div>
    <p class="mb-3 text-[11px] text-[#5B6478]">Your own tasks are private. Your lecturer only sees how many you finish, and your supervisor only sees the tasks they assigned.</p>

    @if($extrasReady)
        {{-- Tabs (filter the list in the browser) --}}
        <div class="mb-3 flex gap-1.5 rounded-xl bg-[#F7F9FD] p-1" role="group" aria-label="Filter tasks">
            @foreach(['all' => 'All', 'overdue' => 'Overdue', 'upcoming' => 'Upcoming'] as $key => $label)
                <button type="button" data-todo-tab="{{ $key }}" aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}" class="flex h-9 flex-1 items-center justify-center gap-2 rounded-[9px] text-[13px] font-bold transition {{ $key === 'all' ? 'bg-white text-[#1E293B] shadow-sm' : 'text-[#5B6478]' }}">
                    {{ $label }}
                    <span class="rounded-lg px-1.5 py-0.5 text-[11px] font-bold {{ $key === 'overdue' ? 'bg-[#FFE4E6] text-[#9F1239]' : 'bg-[#E8EFFF] text-[#1F45D6]' }}">{{ $tabCount[$key] }}</span>
                </button>
            @endforeach
        </div>

        {{-- List: scrolls inside, so many tasks never make the card taller --}}
        <div class="flex max-h-[252px] flex-col gap-2 overflow-y-auto pr-1 [scrollbar-width:thin]">
            @foreach($openTodos->concat($doneTodos) as $todo)
                @php
                    $isDone = (bool) $todo->is_done;
                    $due = $todo->due_date ? $todo->due_date->toDateString() : null;
                    $overdue = ! $isDone && $due && $due < $todayKey;
                    $assigned = ! empty($todo->supervisor_id);
                    $state = $isDone ? 'done' : ($overdue ? 'overdue' : 'upcoming');

                    if ($isDone) {
                        $chip = 'Done';
                        $chipClass = 'bg-[#D1FAE5] text-[#065F46]';
                    } elseif ($overdue) {
                        $chip = 'Overdue '.(int) \Carbon\Carbon::parse($due)->diffInDays(\Carbon\Carbon::parse($todayKey)).'d';
                        $chipClass = 'bg-[#FFE4E6] text-[#9F1239]';
                    } elseif ($due === $todayKey) {
                        $chip = 'Today';
                        $chipClass = 'bg-[#FEF3C7] text-[#7C2D12]';
                    } elseif ($due === \Carbon\Carbon::parse($todayKey)->addDay()->toDateString()) {
                        $chip = 'Tomorrow';
                        $chipClass = 'bg-[#FEF3C7] text-[#7C2D12]';
                    } elseif ($due) {
                        $chip = 'Upcoming';
                        $chipClass = 'bg-[#E8EFFF] text-[#1F45D6]';
                    } else {
                        $chip = 'No date';
                        $chipClass = 'bg-[#EEF1F7] text-[#5B6478]';
                    }
                @endphp
                <div data-todo-row data-state="{{ $state }}">
                    <div class="flex min-h-[52px] items-center gap-3 rounded-xl border border-[#E3E8F2] bg-white px-3 py-2">
                        <form method="POST" action="/student/todos/{{ $todo->todo_id }}/toggle" data-async>
                            @csrf
                            @if($isDone)
                                <button type="submit" aria-label="Mark as not done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] bg-[#047857] text-white"><i class="fa-solid fa-check text-[11px]"></i></button>
                            @else
                                <button type="submit" aria-label="Mark as done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] border-2 border-[#94A3B8] bg-white text-transparent transition hover:border-[#047857] hover:text-[#047857]"><i class="fa-solid fa-check text-[11px]"></i></button>
                            @endif
                        </form>

                        <div class="min-w-0 flex-1">
                            <div class="break-words text-sm font-medium {{ $isDone ? 'text-[#64748B] line-through' : '' }}">{{ $todo->title }}</div>
                            @if($assigned)
                                <span class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-[#E0E7FF] px-2 py-0.5 text-[10px] font-bold text-[#3730A3]"><i class="fa-solid fa-building-user"></i> From {{ optional($todo->supervisor)->name ?? 'your supervisor' }}</span>
                            @endif
                        </div>

                        <div class="flex flex-none flex-col items-end gap-1">
                            <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-bold {{ $chipClass }}">{{ $chip }}</span>
                            <span class="whitespace-nowrap text-[11px] font-medium text-[#5B6478]">{{ $due ? 'Due '.$todo->due_date->format('d M') : 'No due date' }}</span>
                        </div>

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

        {{-- Add a task, with a due date the student can set --}}
        <form method="POST" action="/student/todos" data-async class="mt-4 space-y-2 border-t border-[#EDF0F7] pt-4">
            @csrf
            <div class="flex gap-2">
                <input type="text" name="title" required maxlength="150" placeholder="Add a task" aria-label="New task" class="min-h-11 min-w-0 flex-1 rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                <button type="submit" class="min-h-11 rounded-xl bg-[#2F5BFF] px-5 text-sm font-bold text-white transition hover:bg-[#1F45D6] disabled:opacity-60">Add</button>
            </div>
            <label class="flex items-center gap-2.5 text-xs font-semibold text-[#334155]">
                <span class="flex flex-none items-center gap-1.5 text-[#5B6478]"><i class="fa-regular fa-calendar text-[#1F45D6]"></i> Due date <span class="font-normal">(optional)</span></span>
                <input type="date" name="due_date" aria-label="Due date (optional)" class="min-h-10 min-w-0 flex-1 rounded-xl border border-[#CBD3E3] px-3 py-2 text-sm text-[#334155] focus:border-[#2F5BFF] focus:outline-none">
            </label>
        </form>
    @else
        <p class="text-sm text-amber-800">The to-do table is not set up yet.</p>
    @endif
</div>

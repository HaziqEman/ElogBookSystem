@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
@endphp

<div id="todo-list" data-live class="min-w-0 flex-[2_1_340px] px-[22px] py-5 {{ $card }}">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-base font-extrabold">To-do list</h2>
        <span class="text-xs font-bold text-[#5B6478]">{{ $openCount }} open</span>
    </div>
    <p class="mb-3 text-[11px] text-[#5B6478]">Your own tasks are private. Your lecturer only sees how many you finish, and your supervisor only sees the tasks they assigned.</p>

    @if($extrasReady)
        <form method="POST" action="/student/todos" data-async class="mb-2 space-y-2">
            @csrf
            <div class="flex gap-2">
                <input type="text" name="title" required maxlength="150" placeholder="Add a task" aria-label="New task" class="min-h-11 min-w-0 flex-1 rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                <button type="submit" class="min-h-11 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6] disabled:opacity-60">Add</button>
            </div>
            <input type="date" name="due_date" aria-label="Due date (optional)" class="w-full rounded-xl border border-[#E3E8F2] px-3 py-2 text-xs text-[#5B6478] focus:border-[#2F5BFF] focus:outline-none">
        </form>

        @forelse($openTodos as $todo)
            @php
                $due = $todo->due_date ? $todo->due_date->toDateString() : null;
                $overdue = $due && $due < $todayKey;
                $assigned = ! empty($todo->supervisor_id);
            @endphp
            <div class="flex min-h-12 items-center gap-3 border-t border-[#EDF0F7]">
                <form method="POST" action="/student/todos/{{ $todo->todo_id }}/toggle" data-async>
                    @csrf
                    <button type="submit" aria-label="Mark as done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] border-2 border-[#94A3B8] bg-white text-transparent transition hover:border-[#047857] hover:text-[#047857]"><i class="fa-solid fa-check text-[11px]"></i></button>
                </form>
                <div class="min-w-0 flex-1">
                    <div class="break-words text-sm font-medium">{{ $todo->title }}</div>
                    @if($assigned)
                        <span class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-[#E0E7FF] px-2 py-0.5 text-[10px] font-bold text-[#3730A3]"><i class="fa-solid fa-building-user"></i> From {{ optional($todo->supervisor)->name ?? 'your supervisor' }}</span>
                    @endif
                </div>
                @if($due)
                    <div class="whitespace-nowrap text-xs font-semibold {{ $overdue ? 'text-[#BE123C]' : 'text-[#5B6478]' }}">{{ $overdue ? 'Overdue ' : '' }}{{ $due === $todayKey ? 'Today' : $todo->due_date->format('d M') }}</div>
                @endif
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
        @empty
            <p class="border-t border-[#EDF0F7] pt-3 text-sm text-[#5B6478]">No open tasks. Add one above.</p>
        @endforelse

        @if($openCount > $openTodos->count())
            <p class="border-t border-[#EDF0F7] pt-2 text-xs text-[#5B6478]">+ {{ $openCount - $openTodos->count() }} more open tasks</p>
        @endif

        @if($doneCount > 0)
            <details class="border-t border-[#EDF0F7] pt-2">
                <summary class="cursor-pointer text-xs font-bold text-[#5B6478]">Completed ({{ $doneCount }})</summary>
                @foreach($doneTodos as $todo)
                    <div class="flex min-h-11 items-center gap-3">
                        <form method="POST" action="/student/todos/{{ $todo->todo_id }}/toggle" data-async>
                            @csrf
                            <button type="submit" aria-label="Mark as not done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] bg-[#047857] text-white"><i class="fa-solid fa-check text-[11px]"></i></button>
                        </form>
                        <div class="min-w-0 flex-1 break-words text-sm font-medium text-[#64748B] line-through">{{ $todo->title }}</div>
                        @if(! empty($todo->supervisor_id))
                            <span title="Assigned by your supervisor" class="text-[#CBD3E3]"><i class="fa-solid fa-lock"></i></span>
                        @else
                            <form method="POST" action="/student/todos/{{ $todo->todo_id }}" data-async onsubmit="return confirm('Delete this task?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Delete task" class="text-[#CBD3E3] transition hover:text-[#BE123C]"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </details>
        @endif
    @else
        <p class="text-sm text-amber-800">The to-do table is not set up yet.</p>
    @endif
</div>
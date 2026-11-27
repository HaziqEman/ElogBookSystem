@php
    use Illuminate\Support\Str;

    $cardClass =
        'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $todayKey = $today->toDateString();

    $taskTiles = [
        [
            'label' => 'Open',
            'value' => $taskCounts['open'],
            'tile' => 'bg-[#FEF3C7] text-[#7C2D12]',
            'filter' => 'open',
        ],
        [
            'label' => 'Overdue',
            'value' => $taskCounts['overdue'],
            'tile' => 'bg-[#FFE4E6] text-[#9F1239]',
            'filter' => 'overdue',
        ],
        [
            'label' => 'Done',
            'value' => $taskCounts['done'],
            'tile' => 'bg-[#D1FAE5] text-[#065F46]',
            'filter' => 'done',
        ],
    ];
@endphp

<div id="assigned-tasks" class="{{ $cardClass }} p-4 sm:p-6">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 sm:mb-4">
        <h2 class="flex items-center gap-2.5 text-base font-extrabold text-[#0F1B4D] sm:gap-3 sm:text-lg">
            <span
                class="flex h-8 w-8 flex-none items-center justify-center rounded-xl bg-[#F0EAFF] text-[#6D28D9] sm:h-9 sm:w-9"
            >
                <i
                    class="fa-solid fa-list-check text-sm"
                    aria-hidden="true"
                ></i>
            </span>
            Tasks you assigned
        </h2>
        <a
            href="{{ route('supervisor.tasks.index', ['filter' => 'all']) }}"
            class="text-[13px] font-bold text-[#1F45D6] hover:underline"
        >
            All tasks
        </a>
    </div>

    {{-- Counts --}}
    <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
        @foreach ($taskTiles as $tile)
            <a
                href="{{ request()->fullUrlWithQuery(['task_filter' => $tile['filter']]) }}#assigned-tasks"
                aria-current="{{ $taskFilter === $tile['filter'] ? 'page' : 'false' }}"
                @class([
                    $tile['tile'].' rounded-2xl px-3 py-2.5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2F5BFF] sm:px-4 sm:py-3',
                    'ring-2 ring-[#2F5BFF]/40' => $taskFilter === $tile['filter'],
                ])
            >
                <div class="text-[11px] font-bold uppercase tracking-wider opacity-80">
                    {{ $tile['label'] }}</div>
                <div class="text-2xl font-extrabold leading-tight">{{ $tile['value'] }}</div>
            </a>
        @endforeach
    </div>

    {{-- Tasks that need attention --}}
    <ul class="mt-3 divide-y divide-[#EDF0F7] sm:mt-4">
        @forelse ($taskPreview as $task)
            @php
                $due = $task->due_date?->toDateString();
                $isDone = $task->is_done;
                $isOverdue = !$isDone && $due && $due < $todayKey;
                $owner = $task->student;
            @endphp
            <li class="flex items-center gap-3 py-2.5 sm:py-3">
                <span @class([
                    'flex h-9 w-9 flex-none items-center justify-center rounded-xl text-sm',
                    'bg-[#D1FAE5] text-[#047857]' => $isDone,
                    'bg-[#FFE4E6] text-[#BE123C]' => $isOverdue,
                    'bg-[#FEF3C7] text-[#B45309]' => !$isDone && !$isOverdue,
                ])>
                    <i
                        class="fa-regular {{ $isDone ? 'fa-circle-check' : ($isOverdue ? 'fa-clock' : 'fa-circle') }}"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-bold text-[#0F1B4D]">{{ $task->title }}</div>
                    <div class="truncate text-xs text-[#5B6478]">
                        {{ $owner?->name ?? 'Unknown student' }}</div>
                </div>

                <span @class([
                    'flex-none whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold',
                    'bg-[#D1FAE5] text-[#065F46]' => $isDone,
                    'bg-[#FFE4E6] text-[#9F1239]' => $isOverdue,
                    'bg-[#EEF1F7] text-[#5B6478]' => !$isDone && !$isOverdue,
                ])>
                    @if ($isDone)
                        Done
                    @elseif ($due)
                        {{ $isOverdue ? 'Overdue · ' : 'Due ' }}{{ $task->due_date->format('d M') }}
                    @else
                        No deadline
                    @endif
                </span>
            </li>
        @empty
            <li
                class="rounded-2xl border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-4 py-6 text-center text-sm text-[#5B6478] sm:px-5 sm:py-8"
            >
                <i
                    class="fa-solid fa-circle-check mb-2 block text-2xl text-[#34D399]"
                    aria-hidden="true"
                ></i>
                @if ($taskFilter === 'open')
                    No current open tasks. Assign a task to give your students a clear next step.
                @elseif ($taskFilter === 'overdue')
                    No overdue tasks.
                @else
                    No completed tasks yet.
                @endif
            </li>
        @endforelse
    </ul>

    <a
        href="{{ route('supervisor.tasks.index', ['filter' => 'all']) }}"
        class="mt-3 flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC] sm:mt-4"
    >
        <i
            class="fa-solid fa-plus text-xs"
            aria-hidden="true"
        ></i>
        Assign a task
    </a>
</div>

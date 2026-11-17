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
        ],
        [
            'label' => 'Overdue',
            'value' => $taskCounts['overdue'],
            'tile' => 'bg-[#FFE4E6] text-[#9F1239]',
        ],
        [
            'label' => 'Done',
            'value' => $taskCounts['done'],
            'tile' => 'bg-[#D1FAE5] text-[#065F46]',
        ],
    ];
@endphp

<div class="{{ $cardClass }} p-6">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-3 text-lg font-extrabold text-[#0F1B4D]">
            <span
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#F0EAFF] text-[#6D28D9]"
            >
                <i
                    class="fa-solid fa-list-check text-sm"
                    aria-hidden="true"
                ></i>
            </span>
            Tasks you assigned
        </h2>
        <a
            href="{{ route('supervisor.tasks.index') }}"
            class="text-[13px] font-bold text-[#1F45D6] hover:underline"
        >
            All tasks
        </a>
    </div>

    {{-- Counts --}}
    <div class="grid grid-cols-3 gap-3">
        @foreach ($taskTiles as $tile)
            <div class="{{ $tile['tile'] }} rounded-2xl px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wider opacity-80">
                    {{ $tile['label'] }}</div>
                <div class="text-2xl font-extrabold leading-tight">{{ $tile['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Tasks that need attention --}}
    <ul class="mt-4 divide-y divide-[#EDF0F7]">
        @forelse ($taskPreview as $task)
            @php
                $due = $task->due_date?->toDateString();
                $isOverdue = $due && $due < $todayKey;
                $owner = $task->student;
            @endphp
            <li class="flex items-center gap-3 py-3">
                <span @class([
                    'flex h-9 w-9 flex-none items-center justify-center rounded-xl text-sm',
                    'bg-[#FFE4E6] text-[#BE123C]' => $isOverdue,
                    'bg-[#FEF3C7] text-[#B45309]' => !$isOverdue,
                ])>
                    <i
                        class="fa-regular {{ $isOverdue ? 'fa-clock' : 'fa-circle' }}"
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
                    'bg-[#FFE4E6] text-[#9F1239]' => $isOverdue,
                    'bg-[#EEF1F7] text-[#5B6478]' => !$isOverdue,
                ])>
                    @if ($due)
                        {{ $isOverdue ? 'Overdue · ' : 'Due ' }}{{ $task->due_date->format('d M') }}
                    @else
                        No deadline
                    @endif
                </span>
            </li>
        @empty
            <li
                class="rounded-2xl border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-5 py-8 text-center text-sm text-[#5B6478]">
                <i
                    class="fa-solid fa-circle-check mb-2 block text-2xl text-[#34D399]"
                    aria-hidden="true"
                ></i>
                Nothing outstanding. Assign a task to give your students a clear next step.
            </li>
        @endforelse
    </ul>

    <a
        href="{{ route('supervisor.tasks.index') }}"
        class="mt-4 flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]"
    >
        <i
            class="fa-solid fa-plus text-xs"
            aria-hidden="true"
        ></i>
        Assign a task
    </a>
</div>

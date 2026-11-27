@extends('layouts.app')

@section('content')
    @include('supervisor.shared.portal-font')

    @php
        use Illuminate\Support\Str;

        $card =
            'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
        $input =
            'w-full rounded-xl border border-[#CBD3E3] bg-white px-3 py-2.5 text-sm text-[#0F1B4D] placeholder:text-[#94A3B8] focus:border-[#2F5BFF] focus:outline-none focus:ring-2 focus:ring-[#2F5BFF]/20';

        $chip = [
            'Done' => 'bg-[#D1FAE5] text-[#065F46]',
            'Overdue' => 'bg-[#FFE4E6] text-[#9F1239]',
            'Open' => 'bg-[#FEF3C7] text-[#7C2D12]',
        ];

        $palette = ['#0F9D8A', '#2F5BFF', '#BE123C', '#6B7280', '#7C3AED', '#C2410C'];
        $avatarColor = fn($student) => $student
            ? $palette[crc32((string) $student->student_id) % count($palette)]
            : '#94A3B8';
        $initials = fn(?string $name) => Str::of((string) $name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->implode('') ?:
        '?';

        // Full class strings so Tailwind's JIT can detect them.
        $kpiCards = $ready
            ? [
                [
                    'label' => 'Open',
                    'filter' => 'open',
                    'value' => $counts['open'],
                    'icon' => 'fa-list-check',
                    'tile' => 'bg-[#FFF3D6] text-[#B45309]',
                    'alert' => false,
                ],
                [
                    'label' => 'Overdue',
                    'filter' => 'overdue',
                    'value' => $counts['overdue'],
                    'icon' => 'fa-triangle-exclamation',
                    'tile' => 'bg-[#FFE9E7] text-[#BE123C]',
                    'alert' => $counts['overdue'] > 0,
                ],
                [
                    'label' => 'Done',
                    'filter' => 'done',
                    'value' => $counts['done'],
                    'icon' => 'fa-circle-check',
                    'tile' => 'bg-[#DDF5E8] text-[#047857]',
                    'alert' => false,
                ],
            ]
            : [];
    @endphp

    <div class="portal-font space-y-4 sm:space-y-6">

        {{-- Flash messages --}}
        @if (session('success'))
            <div
                data-flash="success"
                role="status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
            >
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                data-flash="error"
                role="alert"
                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                data-flash="error"
                role="alert"
                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Hero (same pattern as the dashboard) --}}
        <section
            class="relative overflow-hidden rounded-[24px] bg-gradient-to-br from-[#4F6BFF] via-[#5E7CFF] to-[#B7C4FF] px-5 py-5 text-white shadow-[0_14px_30px_rgba(79,107,255,0.25)] sm:rounded-[28px] sm:px-8 sm:py-7"
        >
            <div
                class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-24 right-40 h-56 w-56 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4 sm:gap-6">
                <div class="max-w-3xl">
                    <h1 class="text-2xl font-extrabold leading-tight tracking-tight sm:text-[30px]">Tasks for your
                        students</h1>
                    <p class="mt-1.5 text-sm text-white/90 sm:text-[15px]">
                        Assign a task with a deadline. Students see it in their to-do list, get a
                        notification and a
                        reminder, and you see when they finish it. You only see the tasks you assigned.
                        Students' own
                        tasks stay private.
                    </p>
                </div>

                <div
                    class="hidden h-24 w-24 flex-none items-center justify-center rounded-full bg-white/20 text-4xl lg:flex"
                    aria-hidden="true"
                >
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
        </section>

        @if (!$ready)
            <div
                role="alert"
                class="rounded-[22px] border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800"
            >
                The task feature is not set up yet. Ask the administrator to run the database update.
            </div>
        @else
            <div class="grid min-w-0 items-start gap-4 sm:gap-6 lg:grid-cols-3">

                {{-- Assign a task --}}
                <div class="{{ $card }} min-w-0 p-4 sm:p-6">
                    <h2 class="mb-4 flex items-center gap-3 text-lg font-extrabold text-[#0F1B4D]">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]"
                        >
                            <i
                                class="fa-solid fa-plus text-sm"
                                aria-hidden="true"
                            ></i>
                        </span>
                        Assign a task
                    </h2>

                    @if ($students->isEmpty())
                        <p class="text-sm text-[#5B6478]">No students are assigned to you yet. Please
                            ask the administrator.</p>
                    @else
                        <form
                            id="task-form"
                            method="POST"
                            action="{{ route('supervisor.tasks.store') }}"
                            data-async
                            class="space-y-4"
                        >
                            @csrf

                            <fieldset>
                                <legend class="mb-1.5 text-xs font-bold text-[#5B6478]">Students
                                </legend>
                                <label
                                    class="mb-2 flex items-center gap-2 text-sm font-bold text-[#1F45D6]"
                                >
                                    <input
                                        type="checkbox"
                                        id="task-all"
                                        class="h-4 w-4 rounded border-[#CBD3E3] text-[#2F5BFF] focus:ring-[#2F5BFF]/30"
                                    >
                                    Select all
                                </label>
                                <div
                                    class="max-h-48 space-y-1 overflow-y-auto rounded-xl border border-[#E3E8F2] bg-[#F7F9FD] p-2">
                                    @foreach ($students as $student)
                                        <label
                                            class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 text-sm text-[#0F1B4D] transition hover:bg-white"
                                        >
                                            <input
                                                type="checkbox"
                                                name="student_ids[]"
                                                value="{{ $student->student_id }}"
                                                class="h-4 w-4 flex-none rounded border-[#CBD3E3] text-[#2F5BFF] focus:ring-[#2F5BFF]/30"
                                            >
                                            <span
                                                class="flex h-7 w-7 flex-none items-center justify-center rounded-full text-[10px] font-extrabold text-white"
                                                style="background: {{ $avatarColor($student) }}"
                                            >
                                                {{ $initials($student->name) }}
                                            </span>
                                            <span class="min-w-0 truncate font-semibold">
                                                {{ $student->name }}
                                                <span
                                                    class="text-xs font-medium text-[#94A3B8]">{{ $student->matric_no }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-bold text-[#5B6478]"
                                    for="task-title"
                                >Task</label>
                                <input
                                    type="text"
                                    id="task-title"
                                    name="title"
                                    required
                                    maxlength="150"
                                    placeholder="e.g. Submit the weekly report"
                                    class="{{ $input }}"
                                >
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-bold text-[#5B6478]"
                                    for="task-due"
                                >Deadline (optional)</label>
                                <input
                                    type="date"
                                    id="task-due"
                                    name="due_date"
                                    min="{{ $today }}"
                                    class="{{ $input }}"
                                >
                            </div>

                            <button
                                type="submit"
                                class="flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#2F5BFF] px-5 text-sm font-bold text-white transition hover:bg-[#1F45D6] disabled:opacity-60"
                            >
                                <i
                                    class="fa-solid fa-paper-plane"
                                    aria-hidden="true"
                                ></i>
                                Assign task
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Summary + task table --}}
                <div
                    id="task-list"
                    data-live
                    class="min-w-0 space-y-4 sm:space-y-5 lg:col-span-2"
                >
                    <div class="grid grid-cols-3 gap-2 sm:gap-3.5">
                        @foreach ($kpiCards as $kpi)
                            <a
                                href="{{ route('supervisor.tasks.index', ['filter' => $kpi['filter']]) }}"
                                aria-current="{{ $filter === $kpi['filter'] ? 'page' : 'false' }}"
                                @class([
                                'group rounded-[20px] border bg-white p-3 shadow-[0_6px_18px_rgba(15,27,77,0.05)] transition hover:-translate-y-0.5 hover:shadow-[0_10px_24px_rgba(15,27,77,0.10)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2F5BFF] sm:px-[18px] sm:py-4',
                                'border-[#E3E8F2]' => !$kpi['alert'],
                                'border-[#FBCFCB]' => $kpi['alert'],
                                'ring-2 ring-[#2F5BFF]/30' => $filter === $kpi['filter'],
                            ])>
                                <div class="flex flex-col items-start gap-1.5 sm:flex-row sm:items-center sm:gap-3">
                                    <span
                                        class="{{ $kpi['tile'] }} flex h-8 w-8 flex-none items-center justify-center rounded-xl sm:h-11 sm:w-11 sm:rounded-2xl"
                                    >
                                        <i
                                            class="fa-solid {{ $kpi['icon'] }}"
                                            aria-hidden="true"
                                        ></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="truncate text-[10px] font-semibold text-[#5B6478] sm:text-xs">
                                            {{ $kpi['label'] }}</div>
                                        <div
                                            class="text-xl font-extrabold leading-tight text-[#0F1B4D] sm:text-[28px]">
                                            {{ $kpi['value'] }}</div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="{{ $card }} min-w-0 p-4 sm:p-6">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <h2 class="flex items-center gap-3 text-lg font-extrabold text-[#0F1B4D]">
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#F0EAFF] text-[#6D28D9]"
                                >
                                    <i
                                        class="fa-solid fa-clipboard-list text-sm"
                                        aria-hidden="true"
                                    ></i>
                                </span>
                                Assigned tasks
                            </h2>
                            <div class="flex items-center gap-2">
                                @if ($filter !== 'all')
                                    <a
                                        href="{{ route('supervisor.tasks.index', ['filter' => 'all']) }}"
                                        class="rounded-full border border-[#CBD3E3] bg-white px-3 py-1 text-xs font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]"
                                    >All tasks</a>
                                @endif
                                <span class="rounded-full bg-[#EEF1F7] px-3 py-1 text-xs font-bold text-[#5B6478]">
                                    {{ $tasks->total() }} {{ Str::plural('task', $tasks->total()) }}
                                </span>
                            </div>
                        </div>

                        <div class="max-w-full overflow-x-auto rounded-2xl border border-[#EDF0F7]">
                            <table class="w-full min-w-[680px] text-left text-sm">
                                <caption class="sr-only">Tasks assigned to your students</caption>
                                <thead
                                    class="bg-[#F7F9FD] text-xs font-extrabold uppercase tracking-wider text-[#5B6478]"
                                >
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-4 py-3"
                                        >Student</th>
                                        <th
                                            scope="col"
                                            class="px-4 py-3"
                                        >Task</th>
                                        <th
                                            scope="col"
                                            class="px-4 py-3"
                                        >Deadline</th>
                                        <th
                                            scope="col"
                                            class="px-4 py-3"
                                        >Status</th>
                                        <th
                                            scope="col"
                                            class="px-4 py-3"
                                        >Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#EDF0F7]">
                                    @forelse ($tasks as $task)
                                        @php
                                            $due = $task->due_date?->toDateString();
                                            $status = $task->is_done
                                                ? 'Done'
                                                : ($due && $due < $today
                                                    ? 'Overdue'
                                                    : 'Open');
                                            $owner = $task->student;
                                        @endphp
                                        <tr class="transition hover:bg-[#F9FAFE]">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-xs font-extrabold text-white"
                                                        style="background: {{ $avatarColor($owner) }}"
                                                    >
                                                        {{ $initials($owner?->name) }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <div class="font-bold text-[#0F1B4D]">
                                                            {{ $owner?->name ?? 'Unknown student' }}
                                                        </div>
                                                        <div class="text-xs text-[#5B6478]">
                                                            {{ $owner?->matric_no }}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td
                                                class="max-w-[16rem] break-words px-4 py-3 font-semibold text-[#0F1B4D]">
                                                {{ $task->title }}</td>

                                            <td class="whitespace-nowrap px-4 py-3 text-[#334155]">
                                                @if ($due)
                                                    {{ $task->due_date->format('d M Y') }}
                                                @else
                                                    <span class="text-xs text-[#94A3B8]">No
                                                        deadline</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3">
                                                <span
                                                    class="{{ $chip[$status] }} rounded-full px-2.5 py-1 text-xs font-bold"
                                                >{{ $status }}</span>
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap items-start gap-2">
                                                    <details>
                                                        <summary
                                                            class="flex min-h-9 cursor-pointer list-none items-center gap-1.5 rounded-xl border border-[#CBD3E3] bg-white px-4 text-xs font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC] [&::-webkit-details-marker]:hidden"
                                                        >
                                                            <i
                                                                class="fa-solid fa-pen text-[10px]"
                                                                aria-hidden="true"
                                                            ></i> Edit
                                                        </summary>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('supervisor.tasks.update', $task->todo_id) }}"
                                                            data-async
                                                            class="mt-2 w-60 space-y-2 rounded-2xl border border-[#E3E8F2] bg-white p-3 shadow-[0_6px_18px_rgba(15,27,77,0.08)]"
                                                        >
                                                            @csrf
                                                            @method('PUT')
                                                            <label
                                                                class="sr-only"
                                                                for="edit-title-{{ $task->todo_id }}"
                                                            >Task</label>
                                                            <input
                                                                type="text"
                                                                id="edit-title-{{ $task->todo_id }}"
                                                                name="title"
                                                                required
                                                                maxlength="150"
                                                                value="{{ $task->title }}"
                                                                class="{{ $input }}"
                                                            >
                                                            <label
                                                                class="sr-only"
                                                                for="edit-due-{{ $task->todo_id }}"
                                                            >Deadline</label>
                                                            <input
                                                                type="date"
                                                                id="edit-due-{{ $task->todo_id }}"
                                                                name="due_date"
                                                                value="{{ $due }}"
                                                                class="{{ $input }}"
                                                            >
                                                            <button
                                                                type="submit"
                                                                class="min-h-9 w-full rounded-xl bg-[#2F5BFF] px-3 text-xs font-bold text-white transition hover:bg-[#1F45D6] disabled:opacity-60"
                                                            >
                                                                Save
                                                            </button>
                                                        </form>
                                                    </details>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('supervisor.tasks.destroy', $task->todo_id) }}"
                                                        data-async
                                                        onsubmit="return confirm('Remove this task?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="flex min-h-9 items-center gap-1.5 rounded-xl border border-[#FBCFCB] bg-[#FFF1F0] px-4 text-xs font-bold text-[#BE123C] transition hover:bg-[#FFE4E6] disabled:opacity-60"
                                                        >
                                                            <i
                                                                class="fa-regular fa-trash-can text-[10px]"
                                                                aria-hidden="true"
                                                            ></i> Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td
                                                colspan="5"
                                                class="px-4 py-10 text-center text-sm text-[#5B6478]"
                                            >
                                                <i
                                                    class="fa-solid fa-clipboard-check mb-2 block text-3xl text-[#34D399]"
                                                    aria-hidden="true"
                                                ></i>
                                                @if ($filter === 'open')
                                                    No current open tasks.
                                                @elseif ($filter === 'overdue')
                                                    No overdue tasks.
                                                @elseif ($filter === 'done')
                                                    No completed tasks yet.
                                                @else
                                                    No tasks yet. Assign the first one on the left.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $tasks->links() }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('change', function(event) {
            if (event.target && event.target.id === 'task-all') {
                document.querySelectorAll('input[name="student_ids[]"]').forEach(function(box) {
                    box.checked = event.target.checked;
                });
            }
        });

        document.addEventListener('async:success', function(event) {
            if (event.target && event.target.id === 'task-form') event.target.reset();
        });
    </script>
@endsection

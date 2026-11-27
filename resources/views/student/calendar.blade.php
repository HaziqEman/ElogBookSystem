@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Str;
    @endphp

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px] 2xl:grid-cols-[minmax(0,1fr)_360px]">
            <main class="min-w-0 space-y-4">
                <section
                    class="relative overflow-hidden rounded-3xl px-5 py-4 text-white shadow-[0_14px_34px_rgba(47,91,255,0.18)] sm:px-7"
                    style="background: linear-gradient(120deg, #3B5BFF 0%, #5C7CFF 42%, #A9BAFF 78%, #D6DEFF 100%);"
                >
                    <span aria-hidden="true" class="pointer-events-none absolute -right-10 -top-14 h-44 w-44 rounded-full bg-white/10"></span>
                    <div class="relative flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-extrabold sm:text-2xl">
                                <i class="fa-regular fa-calendar mr-1"></i>
                                {{ $month->format('F Y') }}
                            </h1>
                            <p class="mt-1 text-xs text-[#E4EAFF] sm:text-sm">
                                Your logbook entries, attendance and tasks at a glance.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="hidden gap-2 sm:flex">
                                <div class="rounded-xl bg-white/15 px-3 py-2 text-center">
                                    <b class="block text-lg leading-none">{{ $monthLogs }}</b>
                                    <span class="text-[10px]">Entries</span>
                                </div>
                                <div class="rounded-xl bg-white/15 px-3 py-2 text-center">
                                    <b class="block text-lg leading-none">{{ $monthPresent }}</b>
                                    <span class="text-[10px]">Days present</span>
                                </div>
                                <div class="rounded-xl bg-white/15 px-3 py-2 text-center">
                                    <b class="block text-lg leading-none">{{ $monthTasks }}</b>
                                    <span class="text-[10px]">Open tasks</span>
                                </div>
                            </div>
                            <a
                                href="/student/calendar?month={{ $prevMonth }}"
                                aria-label="Previous month"
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 transition hover:bg-white/25"
                            ><i class="fa-solid fa-chevron-left"></i></a>
                            @unless ($isCurrentMonth)
                                <a href="/student/calendar" class="rounded-xl bg-white px-3 py-2 text-xs font-bold text-blue-700">Today</a>
                            @endunless
                            <a
                                href="/student/calendar?month={{ $nextMonth }}"
                                aria-label="Next month"
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 transition hover:bg-white/25"
                            ><i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-[#E3E8F2] bg-white p-3 shadow-sm sm:p-4">
                    <div class="flex flex-wrap items-end gap-2.5">
                        <label class="min-w-[130px] flex-1 text-[11px] font-bold text-[#5B6478]">
                            Type
                            <select data-calendar-type class="mt-1 min-h-9 w-full rounded-xl border border-[#D8DFEC] bg-white px-3 text-xs font-semibold text-[#25324B]">
                                <option value="all">All Types</option>
                                <option value="logbook">Logbook</option>
                                <option value="task">Tasks</option>
                                <option value="leave">Leave</option>
                                <option value="attendance">Attendance</option>
                                <option value="note">Notes</option>
                            </select>
                        </label>
                        <label class="min-w-[130px] flex-1 text-[11px] font-bold text-[#5B6478]">
                            Status
                            <select data-calendar-status class="mt-1 min-h-9 w-full rounded-xl border border-[#D8DFEC] bg-white px-3 text-xs font-semibold text-[#25324B]">
                                <option value="all">All Status</option>
                                <option value="open">Open</option>
                                <option value="completed">Completed</option>
                            </select>
                        </label>
                        <label class="min-w-[130px] flex-1 text-[11px] font-bold text-[#5B6478]">
                            Search
                            <input
                                type="search"
                                data-calendar-search
                                placeholder="Search notes and tasks..."
                                class="mt-1 min-h-9 w-full rounded-xl border border-[#D8DFEC] px-3 text-xs font-medium text-[#25324B] placeholder:text-slate-400"
                            >
                        </label>
                        <button
                            type="button"
                            data-calendar-clear
                            class="min-h-9 rounded-xl border border-[#D8DFEC] px-3 text-xs font-bold text-[#3158D8] hover:bg-blue-50"
                        ><i class="fa-solid fa-rotate-left mr-1"></i> Clear</button>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-[#DCE3F0] bg-white shadow-sm">
                    <div class="grid grid-cols-7 border-b border-[#E7ECF5] bg-[#F7F9FD] text-center text-[10px] font-bold text-[#64748B] sm:text-xs">
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                            <div class="py-2.5 sm:py-3">{{ $dayName }}</div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-7">
                        @foreach ($weeks as $week)
                            @foreach ($week as $day)
                                @php
                                    $attendance = $day['attendance'];
                                    $payload = $day['payload'];
                                    $cellClass = ! $day['in_month']
                                        ? 'bg-slate-50/60'
                                        : ($day['is_today'] ? 'bg-blue-50/70' : ($day['is_weekend'] ? 'bg-slate-50/50' : 'bg-white'));
                                    $isLeave = $attendance && $attendance->status === \App\Models\Attendance::STATUS_LEAVE;
                                @endphp
                                <button
                                    type="button"
                                    @if ($day['in_month'])
                                        data-calendar-day
                                        data-date="{{ $day['date']->toDateString() }}"
                                        data-day="{{ json_encode($payload) }}"
                                        aria-label="Open {{ $payload['label'] }}"
                                    @else
                                        disabled
                                    @endif
                                    class="{{ $cellClass }} {{ $day['in_month'] ? 'hover:bg-blue-50/70' : 'cursor-default' }} relative min-h-[5rem] border-b border-r border-[#E9EDF5] p-1.5 text-left align-top transition sm:min-h-[6.75rem] sm:p-2"
                                >
                                    @if ($day['is_today'])
                                        <span class="absolute inset-x-0 top-0 h-0.5 bg-blue-600" aria-hidden="true"></span>
                                    @endif
                                    <span class="{{ $day['is_today'] ? 'bg-blue-600 text-white' : ($day['in_month'] ? 'text-slate-700' : 'text-slate-300') }} inline-flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-extrabold sm:h-7 sm:w-7 sm:text-xs">
                                        {{ $day['date']->day }}
                                    </span>

                                    @if ($day['in_month'])
                                        <div class="mt-1 space-y-1">
                                            @if ($day['logs'] > 0)
                                                <span
                                                    data-calendar-event
                                                    data-event-type="logbook"
                                                    data-event-state="{{ collect($payload['logs'])->every(fn ($log) => $log['locked']) ? 'completed' : 'open' }}"
                                                    data-event-search="{{ implode(' ', array_column($payload['logs'], 'summary')) }}"
                                                    class="flex items-center gap-1 overflow-hidden rounded-md bg-blue-50 px-1 py-1 text-[9px] font-bold text-blue-700 ring-1 ring-inset ring-blue-100 sm:px-1.5 sm:text-[10px]"
                                                ><i class="fa-solid fa-book flex-none"></i><span class="truncate">{{ $day['logs'] }} <span class="hidden sm:inline">{{ Str::plural('entry', $day['logs']) }}</span></span></span>
                                            @endif
                                            @if (filled($payload['note']) || count($payload['images']))
                                                <span
                                                    data-calendar-event
                                                    data-event-type="note"
                                                    data-event-state="completed"
                                                    data-event-search="{{ ($payload['note'] ?? '').' '.implode(' ', array_column($payload['images'], 'name')) }}"
                                                    class="flex items-center gap-1 overflow-hidden rounded-md bg-violet-50 px-1 py-1 text-[9px] font-bold text-violet-700 ring-1 ring-inset ring-violet-100 sm:px-1.5 sm:text-[10px]"
                                                ><i class="fa-regular fa-note-sticky flex-none"></i><span class="truncate">Note</span></span>
                                            @endif
                                            @if ($attendance)
                                                <span
                                                    data-calendar-event
                                                    data-event-type="{{ $isLeave ? 'leave' : 'attendance' }}"
                                                    data-event-state="completed"
                                                    data-event-search="{{ ($attendance->note ?? '').' '.($isLeave ? 'leave' : 'present') }}"
                                                    class="flex items-center gap-1 overflow-hidden rounded-md {{ $isLeave ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-emerald-50 text-emerald-700 ring-emerald-100' }} px-1 py-1 text-[9px] font-bold ring-1 ring-inset sm:px-1.5 sm:text-[10px]"
                                                ><i class="fa-solid {{ $isLeave ? 'fa-umbrella-beach' : 'fa-circle-check' }} flex-none"></i><span class="truncate">{{ $isLeave ? 'Leave' : ($attendance->checkInLocal() ?: 'Present') }}</span></span>
                                            @endif
                                            @foreach ($payload['tasks'] as $task)
                                                <span
                                                    data-calendar-event
                                                    data-event-type="task"
                                                    data-event-state="{{ $task['done'] ? 'completed' : 'open' }}"
                                                    data-event-search="{{ $task['title'] }}"
                                                    class="flex items-center gap-1 overflow-hidden rounded-md {{ $task['done'] ? 'bg-slate-100 text-slate-500' : 'bg-indigo-50 text-indigo-700' }} px-1 py-1 text-[9px] font-bold ring-1 ring-inset ring-indigo-100 sm:px-1.5 sm:text-[10px]"
                                                ><i class="fa-solid fa-list-check flex-none"></i><span class="truncate">{{ $task['title'] }}</span></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-[#E9EDF5] bg-[#FAFBFE] px-3 py-2.5 text-[10px] font-semibold text-[#64748B] sm:px-5 sm:text-xs">
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-book text-blue-600"></i> Logbook</span>
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-emerald-600"></i> Attendance</span>
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-umbrella-beach text-amber-600"></i> Leave</span>
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-list-check text-indigo-600"></i> Task</span>
                    </div>
                </section>
            </main>

            <aside
                id="calendar-daily-panel"
                class="min-w-0 overflow-hidden rounded-2xl border border-[#E0E7F2] bg-white shadow-[0_10px_28px_rgba(30,58,138,0.10)] lg:sticky lg:top-4"
            >
                <div class="flex max-h-[calc(100vh-2rem)] min-h-[42rem] flex-col">
                    <div class="flex items-start justify-between border-b border-[#EDF0F7] px-4 py-4">
                        <div class="min-w-0">
                            <h2 id="calendar-day-title" class="text-sm font-extrabold text-[#1E293B]"></h2>
                            <p class="mt-1 text-[11px] text-[#64748B]">Add your notes, tasks and attachments for this day.</p>
                        </div>
                        <button
                            type="button"
                            data-calendar-close
                            aria-label="Close daily details"
                            class="ml-2 flex h-7 w-7 flex-none items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100"
                        ><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
                        <section>
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <h3 class="text-xs font-extrabold text-[#1E293B]"><i class="fa-regular fa-note-sticky mr-1.5 text-blue-600"></i>Your Notes</h3>
                                <button type="submit" form="calendar-note-form" class="rounded-lg bg-[#3158D8] px-3 py-1.5 text-[10px] font-bold text-white hover:bg-blue-700">Save Note</button>
                            </div>
                            <form id="calendar-note-form" action="/student/calendar/daily-notes" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="note_date" data-selected-date value="{{ $selectedDate }}">
                                <textarea
                                    name="body"
                                    data-daily-note
                                    maxlength="5000"
                                    rows="4"
                                    placeholder="Write your notes here..."
                                    class="w-full resize-y rounded-xl border border-[#DCE3F0] p-3 text-xs leading-5 text-[#334155] placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                ></textarea>
                                <label class="mt-2 flex min-h-14 cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-blue-200 bg-blue-50/60 px-3 text-center text-[10px] font-semibold text-blue-700 hover:bg-blue-50">
                                    <i class="fa-solid fa-cloud-arrow-up text-base"></i>
                                    <span>Upload images <span data-image-count class="block font-normal text-slate-500">Select up to 5 images, max 10 MB each</span></span>
                                    <input type="file" name="images[]" accept="image/*" multiple class="sr-only">
                                </label>
                                <p data-image-selection-error class="mt-1 hidden text-[10px] font-semibold text-rose-600" role="alert"></p>
                            </form>
                            <div data-daily-images class="mt-2 grid grid-cols-3 gap-2"></div>
                        </section>

                        <section>
                            <h3 class="mb-2 text-xs font-extrabold text-[#1E293B]"><i class="fa-solid fa-book-open mr-1.5 text-blue-600"></i>Logbook Entries</h3>
                            <div data-daily-logs class="space-y-1.5"></div>
                        </section>

                        <section>
                            <h3 class="mb-2 text-xs font-extrabold text-[#1E293B]"><i class="fa-solid fa-calendar-check mr-1.5 text-emerald-600"></i>Attendance</h3>
                            <div data-daily-attendance></div>
                        </section>

                        <section>
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-xs font-extrabold text-[#1E293B]"><i class="fa-solid fa-list-check mr-1.5 text-blue-600"></i>Tasks</h3>
                                <span data-task-count class="rounded-lg bg-blue-50 px-2 py-1 text-[10px] font-bold text-blue-700"></span>
                            </div>
                            <div data-daily-tasks class="space-y-1.5"></div>
                            <form action="/student/todos" method="POST" class="mt-2 flex gap-2">
                                @csrf
                                <input type="hidden" name="due_date" data-task-date value="{{ $selectedDate }}">
                                <input
                                    name="title"
                                    required
                                    maxlength="150"
                                    placeholder="Add a task for this date"
                                    class="min-w-0 flex-1 rounded-lg border border-[#DCE3F0] px-2.5 py-2 text-[11px] focus:border-blue-400 focus:outline-none"
                                >
                                <button type="submit" class="flex-none rounded-lg bg-[#3158D8] px-3 text-xs font-bold text-white hover:bg-blue-700"><i class="fa-solid fa-plus mr-1"></i>Add</button>
                            </form>
                        </section>

                        <section>
                            <h3 class="mb-2 text-xs font-extrabold text-[#1E293B]"><i class="fa-solid fa-chart-column mr-1.5 text-blue-600"></i>Day Summary</h3>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-xl bg-blue-50 px-2 py-3">
                                    <i class="fa-solid fa-book-open text-sm text-blue-600"></i>
                                    <b data-summary-logs class="mt-1 block text-lg leading-none text-blue-700">0</b>
                                    <span class="text-[9px] text-slate-500">Logbook Entry</span>
                                </div>
                                <div class="rounded-xl bg-emerald-50 px-2 py-3">
                                    <i class="fa-regular fa-circle-check text-sm text-emerald-600"></i>
                                    <b data-summary-tasks class="mt-1 block text-lg leading-none text-emerald-700">0</b>
                                    <span class="text-[9px] text-slate-500">Tasks</span>
                                </div>
                                <div class="rounded-xl bg-amber-50 px-2 py-3">
                                    <i class="fa-solid fa-umbrella-beach text-sm text-amber-600"></i>
                                    <b data-summary-leave class="mt-1 block text-lg leading-none text-amber-700">0</b>
                                    <span class="text-[9px] text-slate-500">Leave</span>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="mb-2 text-xs font-extrabold text-[#1E293B]"><i class="fa-solid fa-filter mr-1.5 text-blue-600"></i>Filter View</h3>
                            <div class="grid grid-cols-4 gap-1.5 text-[10px] font-bold">
                                <button type="button" data-calendar-type-short="all" class="rounded-lg bg-[#3158D8] px-2 py-2 text-white">All</button>
                                <button type="button" data-calendar-type-short="logbook" class="rounded-lg bg-slate-100 px-2 py-2 text-slate-600">Logbook</button>
                                <button type="button" data-calendar-type-short="task" class="rounded-lg bg-slate-100 px-2 py-2 text-slate-600">Task</button>
                                <button type="button" data-calendar-type-short="leave" class="rounded-lg bg-slate-100 px-2 py-2 text-slate-600">Leave</button>
                            </div>
                        </section>
                    </div>
                    <div class="flex gap-2 border-t border-[#EDF0F7] bg-[#FAFBFE] p-3">
                        <button type="button" data-calendar-close class="min-h-10 flex-1 rounded-xl border border-[#CBD3E3] bg-white text-xs font-bold text-slate-600 hover:bg-slate-50">Close</button>
                        <button type="submit" form="calendar-note-form" class="min-h-10 flex-1 rounded-xl bg-[#3158D8] text-xs font-bold text-white hover:bg-blue-700">Save Changes</button>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <div
        id="calendar-image-lightbox"
        role="dialog"
        aria-modal="true"
        aria-label="Image preview"
        tabindex="-1"
        class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
    >
        <div class="relative flex max-h-full max-w-full flex-col items-center">
            <button
                type="button"
                data-lightbox-close
                aria-label="Close image preview"
                class="absolute -right-2 -top-2 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-sm text-slate-600 shadow-md transition hover:bg-white hover:text-slate-900 sm:-right-3 sm:-top-3"
            ><i class="fa-solid fa-xmark"></i></button>
            <img data-lightbox-image src="" alt="" class="max-h-[82vh] max-w-[92vw] rounded-xl object-contain shadow-2xl">
            <p data-lightbox-caption class="mt-2 max-w-[92vw] truncate rounded-full bg-black/45 px-3 py-1 text-xs text-white"></p>
        </div>
    </div>

    <script>
        (() => {
            const panel = document.getElementById('calendar-daily-panel');
            const title = document.getElementById('calendar-day-title');
            const noteInput = panel.querySelector('[data-daily-note]');
            const noteDate = panel.querySelector('[data-selected-date]');
            const noteForm = panel.querySelector('#calendar-note-form');
            const imageInput = noteForm.querySelector('input[name="images[]"]');
            const taskDate = panel.querySelector('[data-task-date]');
            const tasksBox = panel.querySelector('[data-daily-tasks]');
            const imagesBox = panel.querySelector('[data-daily-images]');
            const imageCount = panel.querySelector('[data-image-count]');
            const imageError = panel.querySelector('[data-image-selection-error]');
            const logsBox = panel.querySelector('[data-daily-logs]');
            const attendanceBox = panel.querySelector('[data-daily-attendance]');
            const lightbox = document.getElementById('calendar-image-lightbox');
            const lightboxImage = lightbox.querySelector('[data-lightbox-image]');
            const lightboxCaption = lightbox.querySelector('[data-lightbox-caption]');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const esc = value => String(value ?? '').replace(/[&<>"']/g, char =>
                ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
            let pendingImages = [];
            let savedImages = [];
            let activeDate = null;

            function updateImageHint() {
                imageCount.textContent = pendingImages.length
                    ? `${pendingImages.length} image${pendingImages.length === 1 ? '' : 's'} selected · up to 5, max 10 MB each`
                    : 'Select up to 5 images, max 10 MB each';
            }

            function syncPendingFiles() {
                if (typeof DataTransfer === 'undefined') {
                    imageError.textContent = 'Your browser cannot manage image previews. Please use a recent browser.';
                    imageError.classList.remove('hidden');

                    return false;
                }

                const transfer = new DataTransfer();
                pendingImages.forEach(image => transfer.items.add(image.file));
                imageInput.files = transfer.files;

                return true;
            }

            function clearPendingImages() {
                pendingImages.forEach(image => URL.revokeObjectURL(image.previewUrl));
                pendingImages = [];
                imageInput.value = '';
                imageError.textContent = '';
                imageError.classList.add('hidden');
                updateImageHint();
            }

            function renderTasks(tasks) {
                tasksBox.innerHTML = tasks.length ? tasks.map(task => `
                    <div class="flex items-center gap-2 rounded-lg border border-slate-100 px-2.5 py-2 ${task.done ? 'bg-slate-50' : 'bg-white'}">
                        <form action="/student/todos/${task.id}/toggle" method="POST" class="flex-none">
                            <input type="hidden" name="_token" value="${esc(csrf)}">
                            <button type="submit" aria-label="${task.done ? 'Reopen' : 'Complete'} task ${esc(task.title)}" class="flex h-4 w-4 items-center justify-center rounded border ${task.done ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white'}">
                                ${task.done ? '<i class="fa-solid fa-check text-[9px]"></i>' : ''}
                            </button>
                        </form>
                        <span class="min-w-0 flex-1 break-words text-[11px] ${task.done ? 'text-slate-400 line-through' : 'font-semibold text-slate-700'}">${esc(task.title)}${task.assigned ? '<small class="ml-1 text-[9px] text-indigo-500">Supervisor</small>' : ''}</span>
                        ${task.assigned ? '' : `<form action="/student/todos/${task.id}" method="POST" class="flex-none">
                            <input type="hidden" name="_token" value="${esc(csrf)}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" aria-label="Delete task ${esc(task.title)}" class="px-1 text-rose-500 hover:text-rose-700"><i class="fa-regular fa-trash-can"></i></button>
                        </form>`}
                    </div>`).join('') : '<p class="rounded-lg bg-slate-50 px-3 py-3 text-center text-[10px] text-slate-400">No tasks for this day.</p>';
                panel.querySelector('[data-task-count]').textContent = tasks.length + (tasks.length === 1 ? ' task' : ' tasks');
            }

            function renderImages(images, date) {
                const savedMarkup = images.map(image => `
                    <div class="relative aspect-square overflow-hidden rounded-lg border border-slate-200">
                        <button type="button" data-image-preview="${esc(image.url)}" data-image-name="${esc(image.name)}" aria-label="Preview ${esc(image.name)}" class="block h-full w-full text-left">
                            <img src="${esc(image.url)}" alt="${esc(image.name)}" loading="lazy" class="h-full w-full object-cover">
                            <span class="absolute inset-x-0 bottom-0 truncate bg-slate-900/65 px-1.5 py-1 text-[9px] text-white">${esc(image.name)}</span>
                        </button>
                        <form action="/student/calendar/daily-note-images/${image.id}" method="POST" class="absolute right-1 top-1">
                            <input type="hidden" name="_token" value="${esc(csrf)}">
                            <input type="hidden" name="_method" value="DELETE">
                            <input type="hidden" name="note_date" value="${esc(date)}">
                            <button type="submit" aria-label="Delete saved image ${esc(image.name)}" title="Delete saved image" class="flex h-5 w-5 items-center justify-center rounded-full bg-white/75 text-[9px] text-slate-500 shadow-sm transition hover:bg-white hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
                        </form>
                    </div>`).join('');
                const pendingMarkup = pendingImages.map((image, index) => `
                    <div class="relative aspect-square overflow-hidden rounded-lg border border-blue-300 ring-1 ring-blue-100">
                        <button type="button" data-image-preview="${esc(image.previewUrl)}" data-image-name="${esc(image.file.name)}" aria-label="Preview ${esc(image.file.name)}" class="block h-full w-full text-left">
                            <img src="${esc(image.previewUrl)}" alt="Pending upload: ${esc(image.file.name)}" class="h-full w-full object-cover">
                            <span class="absolute inset-x-0 bottom-0 truncate bg-slate-900/65 px-1.5 py-1 text-[9px] text-white">${esc(image.file.name)}</span>
                        </button>
                        <span class="absolute left-1 top-1 rounded bg-blue-600 px-1.5 py-0.5 text-[8px] font-bold text-white">Pending</span>
                        <button type="button" data-remove-pending-image="${index}" aria-label="Remove ${esc(image.file.name)} from pending uploads" title="Remove image" class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white/75 text-[9px] text-slate-500 shadow-sm transition hover:bg-white hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
                    </div>`).join('');
                imagesBox.innerHTML = savedMarkup + pendingMarkup;
            }

            function closeLightbox() {
                lightbox.classList.replace('flex', 'hidden');
                lightboxImage.src = '';
                document.body.classList.remove('overflow-hidden');
            }

            function openLightbox(src, name) {
                lightboxImage.src = src;
                lightboxImage.alt = name;
                lightboxCaption.textContent = name;
                lightbox.classList.replace('hidden', 'flex');
                document.body.classList.add('overflow-hidden');
                lightbox.focus();
            }

            function renderLogs(logs) {
                logsBox.innerHTML = logs.length ? logs.map(log => `
                    <a href="/student/logbooks" class="block rounded-lg border border-blue-100 bg-blue-50/70 px-2.5 py-2 hover:bg-blue-50">
                        <span class="text-[11px] font-bold text-blue-700">${esc(log.title)}</span>
                        <span class="ml-1 text-[9px] font-semibold ${log.locked ? 'text-emerald-600' : 'text-amber-600'}">${log.locked ? 'Validated' : 'In progress'}</span>
                        <span class="mt-1 block break-words text-[10px] leading-4 text-slate-600">${esc(log.summary)}</span>
                    </a>`).join('') : '<p class="rounded-lg bg-slate-50 px-3 py-2 text-[10px] text-slate-400">No logbook entries for this day.</p>';
            }

            function openDay(day, scrollToPanel = false) {
                if (activeDate !== day.date) {
                    clearPendingImages();
                }
                activeDate = day.date;
                title.textContent = day.label;
                noteDate.value = day.date;
                taskDate.value = day.date;
                noteInput.value = day.note || '';
                renderLogs(day.logs || []);
                renderTasks(day.tasks || []);
                savedImages = day.images || [];
                renderImages(savedImages, day.date);
                attendanceBox.innerHTML = day.attendance
                    ? `<div class="rounded-lg ${day.attendance.is_leave ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800'} px-3 py-2 text-[11px] font-semibold">
                        ${esc(day.attendance.status)}${day.attendance.in ? ` · Clocked in ${esc(day.attendance.in)}` : ''}
                        ${day.attendance.note ? `<span class="mt-1 block font-normal text-slate-600">${esc(day.attendance.note)}</span>` : ''}
                    </div>`
                    : '<p class="rounded-lg bg-slate-50 px-3 py-2 text-[10px] text-slate-400">No attendance record for this day.</p>';
                panel.querySelector('[data-summary-logs]').textContent = (day.logs || []).length;
                panel.querySelector('[data-summary-tasks]').textContent = (day.tasks || []).length;
                panel.querySelector('[data-summary-leave]').textContent = day.attendance?.is_leave ? 1 : 0;
                document.querySelectorAll('[data-calendar-day]').forEach(cell => {
                    cell.classList.toggle('ring-2', cell.dataset.date === day.date);
                    cell.classList.toggle('ring-inset', cell.dataset.date === day.date);
                    cell.classList.toggle('ring-blue-500', cell.dataset.date === day.date);
                });
                const url = new URL(window.location.href);
                url.searchParams.set('selected', day.date);
                window.history.replaceState({}, '', url);
                panel.classList.remove('hidden');
                if (scrollToPanel && window.innerWidth < 1024) {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            document.querySelectorAll('[data-calendar-day]').forEach(cell => {
                const select = () => openDay(JSON.parse(cell.dataset.day), true);
                cell.addEventListener('click', select);
                cell.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        select();
                    }
                });
            });

            document.querySelectorAll('[data-calendar-close]').forEach(button => {
                button.addEventListener('click', () => panel.classList.toggle('hidden'));
            });

            imageInput.addEventListener('change', () => {
                const chosen = Array.from(imageInput.files || []);
                const existing = new Set(pendingImages.map(image =>
                    `${image.file.name}:${image.file.size}:${image.file.lastModified}`));
                const errors = [];

                chosen.forEach(file => {
                    const key = `${file.name}:${file.size}:${file.lastModified}`;
                    if (existing.has(key)) return;
                    if (!file.type.startsWith('image/')) {
                        errors.push(`${file.name}: choose an image file.`);
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        errors.push(`${file.name}: image must be 10 MB or smaller.`);
                        return;
                    }
                    if (pendingImages.length >= 5) {
                        errors.push('You can select up to 5 images at a time.');
                        return;
                    }

                    existing.add(key);
                    pendingImages.push({
                        file,
                        previewUrl: URL.createObjectURL(file)
                    });
                });

                if (!syncPendingFiles()) return;
                imageError.textContent = errors.join(' ');
                imageError.classList.toggle('hidden', errors.length === 0);
                updateImageHint();
                renderImages(savedImages, activeDate);
            });

            imagesBox.addEventListener('click', event => {
                const button = event.target.closest('[data-remove-pending-image]');
                if (button) {
                    const index = Number(button.dataset.removePendingImage);
                    if (!Number.isInteger(index) || !pendingImages[index]) return;

                    URL.revokeObjectURL(pendingImages[index].previewUrl);
                    pendingImages.splice(index, 1);
                    syncPendingFiles();
                    updateImageHint();
                    renderImages(savedImages, activeDate);

                    return;
                }

                const preview = event.target.closest('[data-image-preview]');
                if (preview) {
                    openLightbox(preview.dataset.imagePreview, preview.dataset.imageName);
                }
            });

            lightbox.addEventListener('click', event => {
                if (event.target === lightbox || event.target.closest('[data-lightbox-close]')) {
                    closeLightbox();
                }
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !lightbox.classList.contains('hidden')) {
                    closeLightbox();
                }
            });

            noteForm.addEventListener('submit', event => {
                if (!syncPendingFiles()) {
                    event.preventDefault();
                }
            });

            const typeFilter = document.querySelector('[data-calendar-type]');
            const statusFilter = document.querySelector('[data-calendar-status]');
            const searchInput = document.querySelector('[data-calendar-search]');
            const typeShortcuts = document.querySelectorAll('[data-calendar-type-short]');
            function filterEvents() {
                const type = typeFilter.value;
                const status = statusFilter.value;
                const query = searchInput.value.trim().toLowerCase();
                document.querySelectorAll('[data-calendar-event]').forEach(event => {
                    const matchesType = type === 'all' || event.dataset.eventType === type;
                    const matchesStatus = status === 'all' || event.dataset.eventState === status;
                    const matchesSearch = !query || event.dataset.eventSearch.toLowerCase().includes(query);
                    event.classList.toggle('hidden', !(matchesType && matchesStatus && matchesSearch));
                });
                document.querySelectorAll('[data-calendar-day]').forEach(cell => {
                    const hasEvents = Array.from(cell.querySelectorAll('[data-calendar-event]'))
                        .some(event => !event.classList.contains('hidden'));
                    cell.classList.toggle('opacity-40', query.length > 0 && !hasEvents);
                });
                typeShortcuts.forEach(button => {
                    const active = button.dataset.calendarTypeShort === type;
                    button.classList.toggle('bg-[#3158D8]', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('bg-slate-100', !active);
                    button.classList.toggle('text-slate-600', !active);
                });
            }
            typeFilter.addEventListener('change', filterEvents);
            statusFilter.addEventListener('change', filterEvents);
            searchInput.addEventListener('input', filterEvents);
            typeShortcuts.forEach(button => button.addEventListener('click', () => {
                typeFilter.value = button.dataset.calendarTypeShort;
                filterEvents();
            }));
            document.querySelector('[data-calendar-clear]').addEventListener('click', () => {
                typeFilter.value = 'all';
                statusFilter.value = 'all';
                searchInput.value = '';
                filterEvents();
            });

            const initialCell = Array.from(document.querySelectorAll('[data-calendar-day]'))
                .find(cell => cell.dataset.date === @json($selectedDate))
                || document.querySelector('[data-calendar-day]');
            if (initialCell) openDay(JSON.parse(initialCell.dataset.day));
        })();
    </script>
@endsection

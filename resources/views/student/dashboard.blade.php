@extends('layouts.app')

@section('content')
@php
    $fmtDate = function ($value) {
        if (! $value) {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $badge = function (string $status) {
        if (in_array($status, ['Approved', 'Validated'], true)) {
            return 'bg-emerald-100 text-emerald-800';
        }
        if (in_array($status, ['Rejected', 'Revision Requested'], true)) {
            return 'bg-rose-100 text-rose-800';
        }
        return 'bg-amber-100 text-amber-800';
    };

    $todayKey = $today->toDateString();
@endphp

<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-r from-blue-700 to-indigo-800 p-5 md:p-6 text-white shadow-md">
        <p class="text-xs text-blue-200">{{ $today->format('l, d F Y') }}</p>
        <h3 class="mt-1 text-xl font-bold">Welcome back, {{ optional($student)->name ?? 'Student' }}!</h3>
        <p class="mt-1 text-xs text-blue-100">Keep your logbook, attendance and tasks up to date with your evaluation timeline.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="rounded-lg bg-blue-50 p-2.5 text-blue-600"><i class="fa-solid fa-signature"></i></div>
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Entries</p>
                <p class="text-xl font-bold">{{ $totalLogs }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="rounded-lg bg-emerald-50 p-2.5 text-emerald-600"><i class="fa-solid fa-paperclip"></i></div>
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Files</p>
                <p class="text-xl font-bold">{{ $totalAttachments }}</p>
            </div>
        </div>
        <a href="/student/feedback" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs transition hover:border-blue-300">
            <div class="rounded-lg bg-indigo-50 p-2.5 text-indigo-600"><i class="fa-solid fa-comment-dots"></i></div>
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Reviews</p>
                <p class="text-xl font-bold">{{ $totalReviews }}</p>
            </div>
        </a>
        <a href="/student/messages" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs transition hover:border-blue-300">
            <div class="rounded-lg bg-slate-100 p-2.5 text-slate-700"><i class="fa-solid fa-comments"></i></div>
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Messages</p>
                <p class="truncate text-sm font-bold">Open chat</p>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        {{-- Side column: attendance and to-do (first on phones) --}}
        <div class="order-1 space-y-6 lg:order-2">
            @if($extrasReady)
                @include('student.partials.attendance-card')

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="flex items-center gap-2 text-base font-bold text-slate-800">
                            <i class="fa-solid fa-list-check text-indigo-600"></i> To-do list
                        </h3>
                        <span class="text-xs text-slate-400">{{ $openCount }} open</span>
                    </div>

                    <form method="POST" action="/student/todos" class="mt-3 space-y-2">
                        @csrf
                        <input type="text" name="title" required maxlength="150" placeholder="Add a task..." class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        <div class="flex gap-2">
                            <input type="date" name="due_date" class="min-w-0 flex-1 rounded-lg border border-slate-200 p-2 text-sm focus:border-blue-500 focus:outline-none">
                            <button type="submit" class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
                    </form>

                    <ul class="mt-4 space-y-2">
                        @forelse($openTodos as $todo)
                            @php
                                $due = $todo->due_date ? $todo->due_date->toDateString() : null;
                                $overdue = $due && $due < $todayKey;
                                $dueClass = ! $due
                                    ? ''
                                    : ($overdue ? 'bg-rose-100 text-rose-700' : ($due === $todayKey ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'));
                            @endphp
                            <li class="flex items-start gap-2 rounded-lg border border-slate-100 bg-slate-50/60 p-2.5">
                                <form method="POST" action="/student/todos/{{ $todo->todo_id }}/toggle">
                                    @csrf
                                    <button type="submit" title="Mark as done" class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-transparent transition hover:border-emerald-500 hover:text-emerald-500">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                    </button>
                                </form>
                                <div class="min-w-0 flex-1">
                                    <p class="break-words text-sm text-slate-700">{{ $todo->title }}</p>
                                    @if($due)
                                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $dueClass }}">
                                            {{ $overdue ? 'Overdue - ' : '' }}{{ $due === $todayKey ? 'Today' : $todo->due_date->format('d M') }}
                                        </span>
                                    @endif
                                </div>
                                <form method="POST" action="/student/todos/{{ $todo->todo_id }}" onsubmit="return confirm('Delete this task?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete" class="text-slate-300 transition hover:text-rose-500">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                            </li>
                        @empty
                            <li class="text-sm text-slate-400">No open tasks. Add one above.</li>
                        @endforelse
                    </ul>

                    @if($openCount > $openTodos->count())
                        <p class="mt-2 text-xs text-slate-400">+ {{ $openCount - $openTodos->count() }} more open tasks</p>
                    @endif

                    @if($doneCount > 0)
                        <details class="mt-3">
                            <summary class="cursor-pointer text-xs font-semibold text-slate-500 hover:text-slate-700">Completed ({{ $doneCount }})</summary>
                            <ul class="mt-2 space-y-2">
                                @foreach($doneTodos as $todo)
                                    <li class="flex items-start gap-2 rounded-lg p-2">
                                        <form method="POST" action="/student/todos/{{ $todo->todo_id }}/toggle">
                                            @csrf
                                            <button type="submit" title="Mark as not done" class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500 text-white">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                            </button>
                                        </form>
                                        <p class="min-w-0 flex-1 break-words text-sm text-slate-400 line-through">{{ $todo->title }}</p>
                                        <form method="POST" action="/student/todos/{{ $todo->todo_id }}" onsubmit="return confirm('Delete this task?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete" class="text-slate-300 transition hover:text-rose-500">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>

                <a href="/student/calendar" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 shadow-xs transition hover:border-blue-300">
                    <span><i class="fa-solid fa-calendar-days mr-2 text-blue-600"></i> Open calendar</span>
                    <i class="fa-solid fa-chevron-right text-xs text-slate-400"></i>
                </a>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    Attendance and to-do tables are not set up yet. Ask the administrator to run the database update.
                </div>
            @endif
        </div>

        {{-- Main column: new entry and history --}}
        <div class="order-2 space-y-6 lg:order-1 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs md:p-6">
                <h3 class="flex items-center gap-2 border-b border-slate-100 pb-3 text-base font-bold text-slate-800">
                    <i class="fa-solid fa-circle-plus text-blue-600"></i> New Logbook Entry
                </h3>
                <form id="logbook-form" action="/student/logbook/store" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Date of Training</label>
                            <input type="date" name="activity_date" value="{{ old('activity_date') }}" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Week Number</label>
                            <input type="number" name="week_no" value="{{ old('week_no') }}" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Activity Description</label>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="text-[11px] text-slate-400">Need help drafting a polished entry?</span>
                            <button type="button" id="ai-help-btn" class="shrink-0 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> <span id="ai-help-label">AI Help</span>
                            </button>
                        </div>
                        <textarea id="description-field" rows="5" name="description" placeholder="Detail tasks performed..." class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">{{ old('description') }}</textarea>
                        <p id="ai-help-status" class="mt-2 text-xs text-slate-400"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Upload Attachment (image, PDF or Word, max 5 MB)</label>
                        <input type="file" name="attachment" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700">
                    </div>
                    <button id="submit-entry-btn" type="submit" class="w-full rounded-lg bg-blue-600 p-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">Submit Entry</button>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-700">Log History</h3>
                    <span class="text-xs text-slate-400">Latest {{ $logbooks->count() }} of {{ $totalLogs }}</span>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($logbooks as $logbook)
                        @php
                            $lec = $logbook->status ?? 'Pending';
                            $sup = $logbook->supervisor_status ?? 'Pending';
                            $firstFile = $logbook->attachments->first();
                            $extraFiles = max($logbook->attachments->count() - 1, 0);
                        @endphp
                        <div class="p-4 md:p-5 {{ $logbook->needsRevision() ? 'bg-rose-50/40' : '' }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">Week {{ $logbook->week_no }}</span>
                                <span class="text-xs text-slate-400">{{ $fmtDate($logbook->activity_date) }}</span>
                                <span class="ml-auto">
                                    @if($logbook->isEditableByStudent())
                                        <a href="/student/logbook/edit/{{ $logbook->logbook_id }}" class="inline-flex items-center whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $logbook->needsRevision() ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                            {{ $logbook->needsRevision() ? 'Revise' : 'Edit' }}
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400"><i class="fa-solid fa-lock"></i> Locked</span>
                                    @endif
                                </span>
                            </div>

                            <p class="mt-2 line-clamp-3 break-words text-sm text-slate-600">{{ $logbook->description }}</p>

                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                @if($firstFile)
                                    <a href="{{ $firstFile->url }}" target="_blank" title="{{ $firstFile->file_name }}" class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-slate-700 transition hover:bg-slate-200">
                                        <i class="fa-solid fa-paperclip text-slate-400"></i>
                                        <span class="max-w-[10rem] truncate sm:max-w-[16rem]">{{ $firstFile->file_name }}</span>
                                    </a>
                                    @if($extraFiles > 0)
                                        <span class="text-slate-400">+{{ $extraFiles }} more</span>
                                    @endif
                                @else
                                    <span class="rounded-full bg-slate-50 px-2.5 py-1 text-slate-400">No file</span>
                                @endif

                                <span class="rounded-full px-2.5 py-1 font-semibold {{ $badge($lec) }}">Lecturer: {{ $lec }}</span>
                                @if(optional($student)->supervisor_id)
                                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $badge($sup) }}">Supervisor: {{ $sup }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-sm text-slate-500">No logbook entries yet. Write your first one above.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.__aiHelpBound) return;
    window.__aiHelpBound = true;

    var COOLDOWN_SECONDS = 10;

    function init() {
        var btn = document.getElementById('ai-help-btn');
        var label = document.getElementById('ai-help-label');
        var field = document.getElementById('description-field');
        var status = document.getElementById('ai-help-status');
        var form = document.getElementById('logbook-form');
        var submitBtn = document.getElementById('submit-entry-btn');
        if (!btn || !field || !status) return;

        var busy = false;

        function setStatus(text, color) {
            status.textContent = text;
            status.style.color = color;
        }

        function startCooldown() {
            var left = COOLDOWN_SECONDS;
            btn.disabled = true;
            label.textContent = 'Wait ' + left + 's';
            var timer = setInterval(function () {
                left -= 1;
                if (left <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    label.textContent = 'AI Help';
                } else {
                    label.textContent = 'Wait ' + left + 's';
                }
            }, 1000);
        }

        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            if (busy || btn.disabled) return;

            busy = true;
            btn.disabled = true;
            label.textContent = 'Working...';
            setStatus('Generating a draft...', '#64748b');

            try {
                var weekInput = document.querySelector('input[name="week_no"]');
                var dateInput = document.querySelector('input[name="activity_date"]');
                var tokenInput = document.querySelector('input[name="_token"]');

                var response = await fetch('/student/logbook/ai-help', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenInput ? tokenInput.value : '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        week_no: weekInput ? weekInput.value : '',
                        activity_date: dateInput ? dateInput.value : '',
                        description: field.value
                    })
                });

                if (response.status === 429) {
                    throw new Error('Too many requests. Please wait a moment and try again.');
                }

                var data = null;
                try { data = await response.json(); } catch (parseError) { data = null; }

                if (!response.ok) {
                    throw new Error((data && data.message) || ('HTTP ' + response.status));
                }
                if (!data || !data.text) {
                    throw new Error('No text field in response');
                }

                field.value = data.text;
                field.focus();
                setStatus(data.message || 'Draft inserted successfully.', '#10b981');
            } catch (error) {
                setStatus('Error: ' + (error.message || 'Unable to generate text.'), '#ef4444');
            } finally {
                busy = false;
                startCooldown();
            }
        });

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            });
            window.addEventListener('pageshow', function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Entry';
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endsection
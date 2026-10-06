@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white p-6 rounded-2xl shadow-md">
        <h3 class="text-xl font-bold">Welcome Back to UITM E-LOGBOOK PORTAL, {{ optional($student)->name ?? 'Student' }}!</h3>
        <p class="text-blue-100 text-xs mt-1">Keep track of your internship deliverables and ensure your metrics stay aligned with your evaluation timeline.</p>
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

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl"><i class="fa-solid fa-signature text-xl"></i></div>
            <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Logbook Entries</p><h3 class="text-2xl font-bold mt-0.5">{{ $totalLogs }}</h3></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl"><i class="fa-solid fa-paperclip text-xl"></i></div>
            <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Attachments</p><h3 class="text-2xl font-bold mt-0.5">{{ $totalAttachments }} Files</h3></div>
        </div>
        <a href="/student/feedback" class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-4 transition hover:border-blue-300">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl"><i class="fa-solid fa-comment-dots text-xl"></i></div>
            <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Reviews Received</p><h3 class="text-2xl font-bold mt-0.5">{{ $totalReviews }}</h3></div>
        </a>
        <a href="/student/messages" class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-4 transition hover:border-blue-300">
            <div class="p-3 bg-slate-50 text-slate-700 rounded-xl"><i class="fa-solid fa-comments text-xl"></i></div>
            <div><p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Messages</p><h3 class="text-2xl font-bold mt-0.5">Chat with Lecturer</h3></div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <h3 class="font-bold text-base text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2"><i class="fa-solid fa-circle-plus text-blue-600"></i> New Logbook Entry</h3>
            <form id="logbook-form" action="/student/logbook/store" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Date of Training</label>
                    <input type="date" name="activity_date" value="{{ old('activity_date') }}" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Week Number</label>
                    <input type="number" name="week_no" value="{{ old('week_no') }}" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Activity Description</label>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="text-[11px] text-slate-400">Need help drafting a polished entry?</span>
                        <button type="button" id="ai-help-btn" class="shrink-0 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 disabled:opacity-60 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> <span id="ai-help-label">AI Help</span>
                        </button>
                    </div>
                    <textarea id="description-field" rows="5" name="description" placeholder="Detail tasks performed..." class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">{{ old('description') }}</textarea>
                    <p id="ai-help-status" class="mt-2 text-xs text-slate-400"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Upload Attachments</label>
                    <input type="file" name="attachment" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700">
                </div>
                <button id="submit-entry-btn" type="submit" class="w-full text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed p-2.5 rounded-lg shadow-sm transition">Submit Entry</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-xs lg:col-span-2 overflow-hidden">
            <div class="p-4 bg-slate-50 font-bold text-sm text-slate-700 border-b border-slate-100">Log History Logs</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left border-collapse text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Week</th>
                            <th class="px-4 py-3">Entry</th>
                            <th class="px-4 py-3">File</th>
                            <th class="px-4 py-3">Lecturer</th>
                            <th class="px-4 py-3">Supervisor</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logbooks as $logbook)
                            @php
                                $lec = $logbook->status ?? 'Pending';
                                $lecBadge = $lec === 'Approved' ? 'bg-emerald-100 text-emerald-800' : ($lec === 'Rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800');
                                $sup = $logbook->supervisor_status ?? 'Pending';
                                $supBadge = $sup === 'Validated' ? 'bg-emerald-100 text-emerald-800' : ($sup === 'Revision Requested' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800');
                            @endphp
                            <tr class="{{ $logbook->needsRevision() ? 'bg-rose-50/40' : '' }}">
                                <td class="p-4 font-bold whitespace-nowrap">Week {{ $logbook->week_no }}</td>
                                <td class="p-4 text-slate-600 min-w-[12rem] break-words">{{ \Illuminate\Support\Str::limit($logbook->description, 160) }}</td>
                                <td class="p-4">
                                    @if($logbook->attachments->count())
                                        <a href="/{{ $logbook->attachments[0]->url }}" target="_blank" class="text-blue-600 underline break-all">{{ $logbook->attachments[0]->file_name }}</a>
                                    @else
                                        <span class="px-2 py-0.5 text-xs rounded bg-slate-100 text-slate-600 whitespace-nowrap">No file</span>
                                    @endif
                                </td>
                                <td class="p-4"><span class="px-2 py-0.5 text-xs rounded whitespace-nowrap {{ $lecBadge }}">{{ $lec }}</span></td>
                                <td class="p-4">
                                    @if(optional($student)->supervisor_id)
                                        <span class="px-2 py-0.5 text-xs rounded whitespace-nowrap {{ $supBadge }}">{{ $sup }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">Not assigned</span>
                                    @endif
                                </td>
                                <td class="p-4">
                                    @if($logbook->isEditableByStudent())
                                        <a href="/student/logbook/edit/{{ $logbook->logbook_id }}" class="inline-flex items-center whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $logbook->needsRevision() ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                            {{ $logbook->needsRevision() ? 'Revise' : 'Edit' }}
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Locked</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-4 text-slate-500">No logbook entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
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
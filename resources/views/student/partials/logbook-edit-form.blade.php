@if($logbook->feedbacks->count())
    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="text-sm font-bold text-slate-800">Reviewer feedback</h3>
        <div class="mt-2 space-y-2">
            @foreach($logbook->feedbacks->sortByDesc('feedback_id') as $feedback)
                @php
                    $fromSupervisor = ! empty($feedback->supervisor_id);
                    $who = $fromSupervisor
                        ? (optional($feedback->supervisor)->name ?? 'Company Supervisor')
                        : (optional($feedback->lecturer)->name ?? 'Lecturer');
                @endphp
                <div class="rounded-lg bg-white p-3 text-sm text-slate-600">
                    <p class="text-xs text-slate-400">
                        {{ $who }} ({{ $fromSupervisor ? 'Company Supervisor' : 'Faculty Evaluator' }})
                        - {{ $feedback->feedback_date ? \Carbon\Carbon::parse($feedback->feedback_date)->format('d M Y') : '' }}
                    </p>
                    <p class="mt-1 break-words">{{ $feedback->comment }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif

<p class="text-sm text-amber-700">Saving sends this entry back to your company supervisor for review.</p>

<form action="/student/logbook/update/{{ $logbook->logbook_id }}" method="POST" enctype="multipart/form-data" data-async class="mt-3 space-y-3">
    @csrf
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-600">Date of Training</label>
        <input type="date" name="activity_date" required value="{{ old('activity_date', $logbook->activity_date ? \Carbon\Carbon::parse($logbook->activity_date)->format('Y-m-d') : '') }}" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-600">Week Number</label>
        <input type="number" name="week_no" required value="{{ old('week_no', $logbook->week_no) }}" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-600">Activity Description</label>
        <textarea name="description" rows="7" required class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">{{ old('description', $logbook->description) }}</textarea>
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-600">Attachments</label>
        @if($logbook->attachments->count())
            <div class="mb-2 space-y-1">
                @foreach($logbook->attachments as $attachment)
                    <a href="{{ $attachment->url }}" target="_blank" rel="noopener" class="block break-all text-sm text-blue-600 underline">{{ $attachment->file_name }}</a>
                @endforeach
            </div>
        @endif
        <input type="file" name="attachment" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700">
        <p class="mt-1 text-xs text-slate-400">Choose a file only if you want to add another attachment.</p>
    </div>
    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60">Save and resubmit</button>
        <a href="/student/dashboard" id="edit-cancel" class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</a>
    </div>
</form>
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

<form action="/student/logbook/update/{{ $logbook->logbook_id }}" method="POST" enctype="multipart/form-data" novalidate data-async data-edit-logbook-form data-error-prefix="Edit failed: " class="mt-3 space-y-3">
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
        <label class="mb-1 block text-xs font-semibold text-slate-600">Activity Description <span class="text-red-600">*</span></label>
        <div class="mb-2 flex items-center justify-between gap-2">
            <span class="text-[11px] text-slate-500">Need help drafting a polished entry?</span>
            <button type="button" data-ai-help-button class="shrink-0 rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60">
                <i class="fa-solid fa-wand-magic-sparkles"></i> <span data-ai-help-label>AI Help</span>
            </button>
        </div>
        <textarea name="description" rows="7" required minlength="10" data-ai-description data-edit-description class="w-full rounded-lg border p-2.5 text-sm focus:outline-none {{ $errors->has('description') ? 'border-red-400 focus:border-red-500' : 'border-slate-200 focus:border-blue-500' }}">{{ old('description', $logbook->description) }}</textarea>
        @error('description')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
        <p data-ai-help-status class="mt-2 text-xs text-slate-500"></p>
        <p data-edit-description-error class="hidden mt-1 text-xs font-semibold text-red-600"></p>
    </div>
    <div data-ai-help-form>
        <label class="mb-1 block text-xs font-semibold text-slate-600">Your role at the company <span class="font-normal text-slate-500">(AI Help uses it to choose the wording)</span></label>
        <input type="text" data-ai-role maxlength="150" value="{{ old('role', auth('student')->user()->internship_role ?? '') }}" placeholder="e.g. Junior web developer, Accounts assistant" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">
    </div>
    <div data-ai-help-form>
        <label class="mb-1 block text-xs font-semibold text-slate-600">Optional file for AI Help (image or PDF)</label>
        <label data-ai-read-file-box class="mb-2 flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
            <input type="checkbox" data-ai-read-file class="mt-0.5 h-4 w-4">
            <span><strong>Let AI Help read the first selected file</strong>. It will be sent to Google's Gemini service. Do not select confidential company information.</span>
        </label>
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-600">Attachments</label>
        @if($logbook->attachments->count())
            <ul class="mb-3 space-y-2" data-attachment-list>
                @foreach($logbook->attachments as $attachment)
                    <li data-attachment-row class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <a href="{{ $attachment->url }}" target="_blank" rel="noopener" class="break-all text-sm text-blue-600 underline">{{ $attachment->file_name }}</a>
                        <button type="button" data-delete-attachment="/student/logbook/{{ $logbook->logbook_id }}/attachments/{{ $attachment->attachment_id }}/delete" class="shrink-0 rounded-md border border-rose-200 px-2.5 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">Remove</button>
                    </li>
                @endforeach
            </ul>
        @endif
        <p data-no-attachments class="{{ $logbook->attachments->count() ? 'hidden ' : '' }}mb-2 text-xs text-slate-500">No files attached yet.</p>
        <input type="file" name="attachments[]" data-ai-file multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700">
        <p class="mt-1 text-xs text-slate-400">Select up to 5 files to add (5 MB maximum each). Existing files will be kept.</p>
    </div>
    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60">Save and resubmit</button>
        <a href="/student/dashboard" id="edit-cancel" class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</a>
    </div>
</form>
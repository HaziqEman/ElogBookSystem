@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="/student/dashboard" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:underline">
        <i class="fa-solid fa-arrow-left"></i> Back to dashboard
    </a>

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

    @if($logbook->feedbacks->count())
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <h3 class="text-base font-bold text-slate-800">Reviewer feedback</h3>
            <div class="mt-3 space-y-2">
                @foreach($logbook->feedbacks->sortByDesc('feedback_id') as $feedback)
                    @php
                        $fromSupervisor = ! empty($feedback->supervisor_id);
                        $who = $fromSupervisor
                            ? (optional($feedback->supervisor)->name ?? 'Company Supervisor')
                            : (optional($feedback->lecturer)->name ?? 'Lecturer');
                    @endphp
                    <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
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

    <div class="rounded-xl border border-slate-200 bg-white p-5 md:p-6 shadow-xs">
        <h2 class="text-xl font-bold text-slate-800">Edit logbook entry</h2>
        <p class="mt-1 text-sm text-amber-700">Saving sends this entry back to your lecturer and company supervisor for review.</p>

        <form action="/student/logbook/update/{{ $logbook->logbook_id }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Date of Training</label>
                <input type="date" name="activity_date" required value="{{ old('activity_date', $logbook->activity_date ? \Carbon\Carbon::parse($logbook->activity_date)->format('Y-m-d') : '') }}" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Week Number</label>
                <input type="number" name="week_no" required value="{{ old('week_no', $logbook->week_no) }}" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Activity Description</label>
                <textarea name="description" rows="8" required class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">{{ old('description', $logbook->description) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Attachments</label>
                @if($logbook->attachments->count())
                    <div class="mb-2 space-y-1">
                        @foreach($logbook->attachments as $attachment)
                            <a href="/{{ $attachment->file_path }}" target="_blank" class="block break-all text-sm text-blue-600 underline">{{ $attachment->file_name }}</a>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="attachment" class="w-full text-sm border border-slate-200 rounded-lg p-2.5 file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700">
                <p class="mt-1 text-xs text-slate-400">Choose a file only if you want to add another attachment.</p>
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Save and resubmit</button>
                <a href="/student/dashboard" class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
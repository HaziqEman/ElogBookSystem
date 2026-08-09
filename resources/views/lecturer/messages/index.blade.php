@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Your Conversations</h2>
                <p class="text-sm text-slate-500">Select a conversation with one of your students.</p>
            </div>
            <span class="text-sm text-slate-500">Lecturer: {{ $lecturer->name }}</span>
        </div>
    </div>

    <div class="grid gap-4">
        @forelse($conversations as $conversation)
            <a href="/lecturer/messages/{{ $conversation->conversation_id }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300">
                <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
                    <span>{{ $conversation->student->name ?? 'Unknown Student' }}</span>
                    <span>{{ optional($conversation->updated_at)->diffForHumans() }}</span>
                </div>
                <p class="mt-3 text-slate-500 text-sm">Student ID: {{ $conversation->student->student_id ?? 'N/A' }}</p>
            </a>
        @empty
            <div class="rounded-2xl bg-slate-50 p-6 text-slate-500">No conversations found. Students can start a chat from their dashboard.</div>
        @endforelse
    </div>
</div>
@endsection

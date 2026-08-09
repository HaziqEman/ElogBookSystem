@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Chat with {{ $conversation->student->name }}</h2>
                <p class="text-sm text-slate-500">This conversation is with your assigned student.</p>
            </div>
            <div class="text-sm text-slate-500">Student ID: {{ $conversation->student->student_id }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
        <div class="space-y-4">
            @foreach($conversation->messages as $message)
                <div class="rounded-2xl p-4 {{ $message->sender_type === '\\App\\Models\\Lecturer' ? 'bg-blue-50 self-end' : 'bg-slate-100' }}">
                    <div class="flex items-center justify-between gap-3 text-xs text-slate-500 mb-2">
                        <span>{{ $message->sender->name ?? 'Unknown' }}</span>
                        <span>{{ optional($message->created_at)->format('d M Y H:i') }}</span>
                    </div>
                    <div class="text-sm text-slate-800">{{ $message->message }}</div>
                </div>
            @endforeach

            @if($conversation->messages->isEmpty())
                <div class="rounded-2xl bg-slate-50 p-4 text-slate-500">No messages yet. Send a reply to start the discussion.</div>
            @endif
        </div>

        <form action="/lecturer/messages/{{ $conversation->conversation_id }}" method="POST" class="mt-6">
            @csrf
            <div class="space-y-3">
                <textarea name="message" rows="4" class="w-full rounded-xl border border-slate-200 p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-200" placeholder="Type your reply..."></textarea>
                @error('message')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">Send Reply</button>
            </div>
        </form>
    </div>
</div>
@endsection

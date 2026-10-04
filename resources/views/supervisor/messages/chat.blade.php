@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <a href="/supervisor/messages" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:underline">
        <i class="fa-solid fa-arrow-left"></i> All conversations
    </a>

    <div class="bg-white p-5 md:p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Chat with {{ $conversation->student->name }}</h2>
                <p id="participant-status" class="text-sm text-slate-500">Loading presence...</p>
                <p class="text-sm text-slate-500">This conversation is with your assigned student.</p>
            </div>
            <div class="text-sm text-slate-500">Matric: {{ $conversation->student->matric_no }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 md:p-6">
        <div id="messages-list" class="space-y-4">
            @forelse($messages as $message)
                @php
                    $isDeletedForEveryone = $message->deleted_at !== null;
                    $isSender = $message->sender_type === \App\Models\Supervisor::class && (int) $message->sender_id === (int) $supervisor->supervisor_id;
                @endphp
                <div data-message-id="{{ $message->message_id }}" class="message-item rounded-2xl p-4 {{ $message->sender_type === \App\Models\Supervisor::class ? 'bg-blue-50 self-end' : 'bg-slate-100' }}">
                    <div class="flex items-center justify-between gap-3 text-xs text-slate-500 mb-2">
                        <span>{{ $message->sender->name ?? 'Unknown' }}</span>
                        <span>{{ optional($message->created_at)->format('d M Y H:i') }}</span>
                    </div>
                    <div class="text-sm text-slate-800">
                        @if($isDeletedForEveryone)
                            <em>This message was deleted</em>
                        @else
                            {{ $message->message }}
                        @endif
                    </div>
                    @if(! $isDeletedForEveryone)
                        <button type="button" class="delete-message-button mt-2 text-xs text-slate-500 hover:text-slate-800" data-message-id="{{ $message->message_id }}" data-sender="{{ $isSender ? 'true' : 'false' }}">Delete</button>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl bg-slate-50 p-4 text-slate-500">No messages yet. Send a message to start the discussion.</div>
            @endforelse
        </div>

        <form id="message-form" action="/supervisor/messages/{{ $conversation->conversation_id }}" method="POST" class="mt-6">
            @csrf
            <div class="space-y-3">
                <textarea name="message" rows="4" class="w-full rounded-xl border border-slate-200 p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-200" placeholder="Type your message..."></textarea>
                @error('message')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">Send Message</button>
            </div>
        </form>
    </div>

    <script>
        window.CONVERSATION_ID = {{ $conversation->conversation_id }};
        window.CURRENT_USER = { type: 'App\\Models\\Supervisor', role: 'supervisor', id: {{ $supervisor->supervisor_id }} };
    </script>

    @include('partials.chat-client')
</div>
@endsection

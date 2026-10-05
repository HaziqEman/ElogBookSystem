@extends('layouts.app')

@section('content')
@php
    $isLecturer = $partnerRole === 'lecturer';
    $tabOn = 'bg-blue-600 text-white border-blue-600';
    $tabOff = 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
@endphp
<div class="space-y-6">
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="bg-white p-5 md:p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <a href="/student/messages" class="rounded-full border px-4 py-1.5 text-xs font-semibold transition {{ $isLecturer ? $tabOn : $tabOff }}">Lecturer</a>
            @if($student->supervisor_id)
                <a href="/student/messages/supervisor" class="rounded-full border px-4 py-1.5 text-xs font-semibold transition {{ ! $isLecturer ? $tabOn : $tabOff }}">Company Supervisor</a>
            @else
                <span class="text-xs text-slate-400">No company supervisor assigned yet</span>
            @endif
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Chat with {{ $partner->name }}</h2>
                <p id="participant-status" class="text-sm text-slate-500">Loading presence...</p>
                <p class="text-sm text-slate-500">
                    {{ $isLecturer ? 'This conversation is with your assigned lecturer.' : 'This conversation is with your company supervisor.' }}
                </p>
            </div>
            <div class="text-sm text-slate-500">
                @if($isLecturer)
                    Lecturer ID: {{ $partner->lecturer_id }}
                @else
                    {{ $partner->company_name }}
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 md:p-6">
        <div id="messages-list" class="space-y-4">
            @forelse($messages as $message)
                @php
                    $isDeletedForEveryone = $message->deleted_at !== null;
                    $isSender = $message->sender_type === App\Models\Student::class && (int) $message->sender_id === (int) $student->student_id;
                @endphp
                <div data-message-id="{{ $message->message_id }}" class="message-item rounded-2xl p-4 {{ $message->sender_type === App\Models\Student::class ? 'bg-blue-50 self-end' : 'bg-slate-100' }}">
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
                <div class="rounded-2xl bg-slate-50 p-4 text-slate-500">No messages yet. Send the first message to start the conversation.</div>
            @endforelse
        </div>

        <form id="message-form" action="{{ $formAction }}" method="POST" class="mt-6">
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
        window.CHAT_PARTNER_ROLE = '{{ $partnerRole }}';
        window.CURRENT_USER = { type: 'App\\Models\\Student', role: 'student', id: {{ $student->student_id }} };
    </script>

    @include('partials.chat-client')
</div>
@endsection
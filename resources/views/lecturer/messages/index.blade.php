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
            <a data-conversation-id="{{ $conversation->conversation_id }}" href="/lecturer/messages/{{ $conversation->conversation_id }}" class="conversation-item rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300">
                <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
                    <span class="conversation-student-name">{{ $conversation->student->name ?? 'Unknown Student' }}</span>
                    <span class="conversation-updated">{{ optional($conversation->updated_at)->diffForHumans() }}</span>
                </div>
                <p class="mt-3 text-slate-500 text-sm">Student ID: {{ $conversation->student->student_id ?? 'N/A' }}</p>
            </a>
        @empty
            <div class="rounded-2xl bg-slate-50 p-6 text-slate-500">No conversations found. Students can start a chat from their dashboard.</div>
        @endforelse
    </div>
    
    <script>
        (function(){
            const lecturerId = {{ $lecturer->lecturer_id }};
            if (! lecturerId || typeof window.Echo === 'undefined') return;

            const channel = window.Echo.private(`private-lecturer.${lecturerId}`);
            const container = document.querySelector('.grid.gap-4');

            channel.listen('MessageSent', (e) => {
                const payload = e.message;
                if (! payload || ! container) return;

                const convId = payload.conversation_id;
                const existing = container.querySelector(`[data-conversation-id='${convId}']`);

                if (existing) {
                    // update timestamp text
                    const ts = existing.querySelector('.conversation-updated');
                    if (ts) ts.textContent = payload.created_at;

                    // move to top
                    container.prepend(existing);

                    // mark as updated (temporary highlight)
                    existing.classList.add('border-blue-300');
                    setTimeout(() => existing.classList.remove('border-blue-300'), 3000);
                } else {
                    // Prepend a minimal conversation item for new conversation
                    const a = document.createElement('a');
                    a.href = `/lecturer/messages/${convId}`;
                    a.setAttribute('data-conversation-id', convId);
                    a.className = 'conversation-item rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300';
                    a.innerHTML = `<div class="flex items-center justify-between gap-3 text-sm text-slate-600"><span class="conversation-student-name">${payload.sender_name ?? 'Student'}</span><span class="conversation-updated">${payload.created_at}</span></div><p class="mt-3 text-slate-500 text-sm">New conversation</p>`;
                    container.prepend(a);
                }
            });
        })();
    </script>
</div>
@endsection

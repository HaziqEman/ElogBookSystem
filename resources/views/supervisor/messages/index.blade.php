@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-5 md:p-6 rounded-xl border border-slate-200 shadow-xs">
        <h2 class="text-xl font-bold text-slate-800">Your Conversations</h2>
        <p class="text-sm text-slate-500">Select one of your assigned students to open or start a chat.</p>
    </div>

    <div id="conversation-list" class="grid gap-4">
        @forelse($students as $student)
            @php
                $conversation = $conversations->get($student->student_id);
                $href = $conversation
                    ? '/supervisor/messages/'.$conversation->conversation_id
                    : '/supervisor/messages/with/'.$student->student_id;
            @endphp
            <a data-student-id="{{ $student->student_id }}" href="{{ $href }}" class="conversation-item rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300">
                <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
                    <span class="font-semibold text-slate-800">{{ $student->name }}</span>
                    <span class="conversation-updated">{{ $conversation ? optional($conversation->updated_at)->diffForHumans() : 'No messages yet' }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 text-sm text-slate-500">
                    <span>Matric: {{ $student->matric_no }}</span>
                    @if($conversation && $conversation->unread_count > 0)
                        <span class="rounded-full bg-rose-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $conversation->unread_count }} new</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-2xl bg-slate-50 p-6 text-slate-500">No students are assigned to you yet. Please ask the administrator.</div>
        @endforelse
    </div>

    <script>
        (function(){
            const supervisorId = {{ $supervisor->supervisor_id }};
            const container = document.getElementById('conversation-list');
            if (! supervisorId || ! container || typeof window.Echo === 'undefined') return;

            window.Echo.private(`private-supervisor.${supervisorId}`).listen('MessageSent', (e) => {
                const payload = e.message;
                if (! payload || payload.sender_type !== 'App\\Models\\Student') return;

                const item = container.querySelector(`[data-student-id='${payload.sender_id}']`);
                if (! item) return;

                const ts = item.querySelector('.conversation-updated');
                if (ts) ts.textContent = payload.created_at ?? 'just now';

                container.prepend(item);
                item.classList.add('border-blue-300');
                setTimeout(() => item.classList.remove('border-blue-300'), 3000);
            });
        })();
    </script>
</div>
@endsection
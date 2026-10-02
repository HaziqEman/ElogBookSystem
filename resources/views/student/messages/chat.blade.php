@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Chat with {{ $lecturer->name }}</h2>
                <p id="participant-status" class="text-sm text-slate-500">Loading presence...</p>
                <p class="text-sm text-slate-500">This conversation is with your assigned lecturer.</p>
            </div>
            <div class="text-sm text-slate-500">Lecturer ID: {{ $lecturer->lecturer_id }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
        <div id="messages-list" class="space-y-4">
            @forelse($messages as $message)
                @php
                    $isDeletedForEveryone = $message->deleted_at !== null;
                    $isSender = $message->sender_type === App\Models\Student::class && $message->sender_id === $student->student_id;
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

        <form id="message-form" action="/student/messages" method="POST" class="mt-6">
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
        window.CURRENT_USER = { type: 'App\\Models\\Student', role: 'student', id: {{ $student->student_id }} };
    </script>

    <div id="delete-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/50 px-4 py-6">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Delete message?</h2>
            <p class="mt-2 text-sm text-slate-600">Choose whether to remove this message from your chat or delete it for everyone.</p>
            <div class="mt-6 space-y-3">
                <button id="delete-for-me-button" type="button" class="w-full rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-200">Delete for me</button>
                <button id="delete-for-everyone-button" type="button" class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700">Delete for everyone</button>
                <button id="delete-cancel-button" type="button" class="w-full rounded-xl bg-white border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            </div>
        </div>
    </div>

    <div id="delete-confirm-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/50 px-4 py-6">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Delete for everyone?</h2>
            <p class="mt-2 text-sm text-slate-600">This message will be deleted for both participants.</p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <button id="confirm-cancel-button" type="button" class="w-full rounded-xl bg-white border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button id="confirm-delete-button" type="button" class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const form = document.getElementById('message-form');
            const list = document.getElementById('messages-list');
            const deleteModal = document.getElementById('delete-modal');
            const confirmModal = document.getElementById('delete-confirm-modal');
            const deleteForMeButton = document.getElementById('delete-for-me-button');
            const deleteForEveryoneButton = document.getElementById('delete-for-everyone-button');
            const deleteCancelButton = document.getElementById('delete-cancel-button');
            const confirmCancelButton = document.getElementById('confirm-cancel-button');
            const confirmDeleteButton = document.getElementById('confirm-delete-button');
            let activeMessageId = null;
            let activeMessageIsSender = false;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const conversationId = window.CONVERSATION_ID;

            const openDeleteModal = (messageId, isSender) => {
                activeMessageId = messageId;
                activeMessageIsSender = isSender;
                deleteForEveryoneButton.style.display = isSender ? 'block' : 'none';
                deleteModal.classList.remove('hidden');
            };

            const closeDeleteModal = () => {
                deleteModal.classList.add('hidden');
            };

            const openConfirmModal = () => {
                confirmModal.classList.remove('hidden');
            };

            const closeConfirmModal = () => {
                confirmModal.classList.add('hidden');
            };

            const isCurrentUserSender = (payload) => {
                return window.CURRENT_USER && window.CURRENT_USER.type === payload.sender_type && Number(window.CURRENT_USER.id) === Number(payload.sender_id);
            };

            const attachDeleteButtonListener = (button) => {
                button.addEventListener('click', () => {
                    const messageId = button.dataset.messageId;
                    const isSender = button.dataset.sender === 'true';
                    openDeleteModal(messageId, isSender);
                });
            };

            const createMessageElement = (payload) => {
                const div = document.createElement('div');
                const isSender = isCurrentUserSender(payload);
                const isOutgoing = isSender;

                div.className = `rounded-2xl p-4 ${isOutgoing ? 'bg-blue-50 self-end' : 'bg-slate-100'}`;
                div.dataset.messageId = payload.message_id;

                const header = document.createElement('div');
                header.className = 'flex items-center justify-between gap-3 text-xs text-slate-500 mb-2';
                const sender = document.createElement('span');
                sender.textContent = payload.sender_name ?? 'You';
                const timestamp = document.createElement('span');
                timestamp.textContent = payload.created_at ?? '';
                header.append(sender, timestamp);

                const messageBody = document.createElement('div');
                messageBody.className = 'text-sm text-slate-800';
                messageBody.textContent = payload.message ?? '';
                div.append(header, messageBody);

                if (isSender) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'delete-message-button mt-2 text-xs text-slate-500 hover:text-slate-800';
                    button.dataset.messageId = payload.message_id;
                    button.dataset.sender = 'true';
                    button.textContent = 'Delete';
                    div.appendChild(button);
                    attachDeleteButtonListener(button);
                }

                return div;
            };

            const removeMessageElement = (messageId) => {
                const item = document.querySelector(`[data-message-id='${messageId}']`);
                if (item) {
                    item.remove();
                }
            };

            const markMessageDeleted = (messageId) => {
                const item = document.querySelector(`[data-message-id='${messageId}']`);
                if (! item) return;
                const body = item.querySelector('.text-sm.text-slate-800');
                if (body) {
                    const deletedMessage = document.createElement('em');
                    deletedMessage.textContent = 'This message was deleted';
                    body.replaceChildren(deletedMessage);
                }
                const button = item.querySelector('.delete-message-button');
                if (button) button.remove();
            };

            const performDeleteForMe = async () => {
                if (! activeMessageId) return;
                const response = await fetch(`/conversations/${conversationId}/messages/${activeMessageId}/delete-for-me`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });

                if (response.ok) {
                    removeMessageElement(activeMessageId);
                }
            };

            const performDeleteForEveryone = async () => {
                if (! activeMessageId) return;
                const response = await fetch(`/conversations/${conversationId}/messages/${activeMessageId}/delete-for-everyone`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });

                if (response.ok) {
                    markMessageDeleted(activeMessageId);
                }
            };

            document.querySelectorAll('.delete-message-button').forEach((button) => {
                button.addEventListener('click', () => {
                    const messageId = button.dataset.messageId;
                    const isSender = button.dataset.sender === 'true';
                    openDeleteModal(messageId, isSender);
                });
            });

            deleteCancelButton.addEventListener('click', closeDeleteModal);
            confirmCancelButton.addEventListener('click', closeConfirmModal);

            deleteForMeButton.addEventListener('click', async () => {
                await performDeleteForMe();
                closeDeleteModal();
            });

            deleteForEveryoneButton.addEventListener('click', () => {
                openConfirmModal();
            });

            confirmDeleteButton.addEventListener('click', async () => {
                await performDeleteForEveryone();
                closeConfirmModal();
                closeDeleteModal();
            });

            if (form) {
                form.addEventListener('submit', async function(e){
                    e.preventDefault();

                    const textarea = form.querySelector('textarea[name="message"]');
                    const btn = form.querySelector('button[type="submit"]');
                    if (! textarea || ! list) return;

                    const message = textarea.value.trim();
                    if (! message) return;

                    btn.disabled = true;

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ message })
                        });

                        if (! res.ok) {
                            return;
                        }

                        const data = await res.json();
                        const payload = data.message;
                        const div = createMessageElement(payload);
                        list.appendChild(div);
                        list.scrollTop = list.scrollHeight;
                        textarea.value = '';
                    } finally {
                        btn.disabled = false;
                    }
                });
            }
        })();
    </script>
</div>
@endsection
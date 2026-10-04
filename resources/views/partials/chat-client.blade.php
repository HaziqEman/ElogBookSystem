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

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const conversationId = window.CONVERSATION_ID;

        const openDeleteModal = (messageId, isSender) => {
            activeMessageId = messageId;
            deleteForEveryoneButton.style.display = isSender ? 'block' : 'none';
            deleteModal.classList.remove('hidden');
        };

        const closeDeleteModal = () => deleteModal.classList.add('hidden');
        const openConfirmModal = () => confirmModal.classList.remove('hidden');
        const closeConfirmModal = () => confirmModal.classList.add('hidden');

        const isCurrentUserSender = (payload) => {
            return window.CURRENT_USER
                && window.CURRENT_USER.type === payload.sender_type
                && Number(window.CURRENT_USER.id) === Number(payload.sender_id);
        };

        const attachDeleteButtonListener = (button) => {
            button.addEventListener('click', () => {
                openDeleteModal(button.dataset.messageId, button.dataset.sender === 'true');
            });
        };

        const createMessageElement = (payload) => {
            const div = document.createElement('div');
            const isSender = isCurrentUserSender(payload);

            div.className = `rounded-2xl p-4 ${isSender ? 'bg-blue-50 self-end' : 'bg-slate-100'}`;
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
            if (item) item.remove();
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

        const postDelete = (kind) => fetch(`/conversations/${conversationId}/messages/${activeMessageId}/${kind}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        });

        const performDeleteForMe = async () => {
            if (! activeMessageId) return;
            const response = await postDelete('delete-for-me');
            if (response.ok) removeMessageElement(activeMessageId);
        };

        const performDeleteForEveryone = async () => {
            if (! activeMessageId) return;
            const response = await postDelete('delete-for-everyone');
            if (response.ok) markMessageDeleted(activeMessageId);
        };

        document.querySelectorAll('.delete-message-button').forEach(attachDeleteButtonListener);

        deleteCancelButton.addEventListener('click', closeDeleteModal);
        confirmCancelButton.addEventListener('click', closeConfirmModal);

        deleteForMeButton.addEventListener('click', async () => {
            await performDeleteForMe();
            closeDeleteModal();
        });

        deleteForEveryoneButton.addEventListener('click', openConfirmModal);

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

                    if (! res.ok) return;

                    const data = await res.json();
                    list.appendChild(createMessageElement(data.message));
                    list.scrollTop = list.scrollHeight;
                    textarea.value = '';
                } finally {
                    btn.disabled = false;
                }
            });
        }
    })();
</script>
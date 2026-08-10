import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
	broadcaster: 'pusher',
	key: import.meta.env.VITE_PUSHER_APP_KEY,
	cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
	forceTLS: true,
	authEndpoint: '/broadcasting/auth',
	auth: {
		headers: {
			'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
		}
	}
});

// If a conversation is present on the window, subscribe to its private channel
const isCurrentUserMember = (member) => {
	const currentUser = window.CURRENT_USER ?? null;
	if (! currentUser || ! member) return false;
	return currentUser.role === member.role && Number(currentUser.id) === Number(member.id);
};

const getOtherParticipantRole = () => {
	const currentUser = window.CURRENT_USER ?? null;
	if (! currentUser) return null;
	return currentUser.role === 'student' ? 'lecturer' : 'student';
};

const updatePresenceStatus = (online) => {
	const statusElement = document.getElementById('participant-status');
	if (! statusElement) return;
	statusElement.textContent = online ? '🟢 Online' : '⚪ Offline';
	statusElement.classList.toggle('text-emerald-600', online);
	statusElement.classList.toggle('text-slate-500', !online);
};

const otherParticipantIsOnline = (members) => {
	const otherRole = getOtherParticipantRole();
	if (! otherRole || ! Array.isArray(members)) return false;
	return members.some((member) => ! isCurrentUserMember(member) && member.role === otherRole);
};

const appendIncomingMessage = (payload) => {
	const list = document.getElementById('messages-list');
	if (! list) return;

	const currentUser = window.CURRENT_USER ?? null;
	const div = document.createElement('div');
	const isCurrentUserSender = currentUser && currentUser.type === payload.sender_type && Number(currentUser.id) === Number(payload.sender_id);
	div.dataset.messageId = payload.message_id;
	div.className = `rounded-2xl p-4 ${isCurrentUserSender ? 'bg-blue-50 self-end' : 'bg-slate-100'}`;
	div.innerHTML = `<div class="flex items-center justify-between gap-3 text-xs text-slate-500 mb-2"><span>${payload.sender_name ?? 'Unknown'}</span><span>${payload.created_at}</span></div><div class="text-sm text-slate-800">${payload.message}</div>`;
	list.appendChild(div);
	list.scrollTop = list.scrollHeight;
};

const markMessageDeleted = (messageId) => {
	const item = document.querySelector(`[data-message-id='${messageId}']`);
	if (! item) return;

	const body = item.querySelector('.text-sm.text-slate-800');
	if (body) {
		body.innerHTML = '<em>This message was deleted</em>';
	}

	const button = item.querySelector('.delete-message-button');
	if (button) {
		button.remove();
	}
};

let activeConversationId = null;

const leaveRealtimeChannels = () => {
	const conversationIdAvailable = window.CONVERSATION_ID ?? null;
	if (! conversationIdAvailable) {
		return;
	}

	window.Echo.leave(`private-conversation.${conversationIdAvailable}`);
	window.Echo.leave(`presence-conversation.${conversationIdAvailable}`);
};

const initRealtimeChannels = () => {
	const conversationIdAvailable = window.CONVERSATION_ID ?? null;

	if (! conversationIdAvailable) {
		return;
	}

	activeConversationId = conversationIdAvailable;

	const channel = window.Echo.private(`private-conversation.${conversationIdAvailable}`);

	channel.listen('MessageSent', (e) => {
		const payload = e.message;
		const currentUser = window.CURRENT_USER ?? null;

		if (currentUser && currentUser.type === payload.sender_type && Number(currentUser.id) === Number(payload.sender_id)) {
			return;
		}

		appendIncomingMessage(payload);
	});

	channel.listen('MessageDeleted', (e) => {
		markMessageDeleted(e.message.message_id);
	});

	window.Echo.join(`presence-conversation.${conversationIdAvailable}`)
		.here((members) => {
			updatePresenceStatus(otherParticipantIsOnline(members));
		})
		.joining((member) => {
			if (! isCurrentUserMember(member) && member.role === getOtherParticipantRole()) {
				updatePresenceStatus(true);
			}
		})
		.leaving((member) => {
			if (! isCurrentUserMember(member) && member.role === getOtherParticipantRole()) {
				updatePresenceStatus(false);
			}
		});

	const logoutLink = document.querySelector('a[href="/logout"]');
	if (logoutLink) {
		logoutLink.addEventListener('click', () => {
			leaveRealtimeChannels();
		});
	}
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initRealtimeChannels);
} else {
	initRealtimeChannels();
}

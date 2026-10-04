import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => {
		navigator.serviceWorker.register('/service-worker.js').catch(() => {});
	});
}

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
	// A page can name its chat partner explicitly (a student talking to a company supervisor).
	if (window.CHAT_PARTNER_ROLE) return window.CHAT_PARTNER_ROLE;

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

	const header = document.createElement('div');
	header.className = 'flex items-center justify-between gap-3 text-xs text-slate-500 mb-2';
	const sender = document.createElement('span');
	sender.textContent = payload.sender_name ?? 'Unknown';
	const timestamp = document.createElement('span');
	timestamp.textContent = payload.created_at ?? '';
	header.append(sender, timestamp);

	const messageBody = document.createElement('div');
	messageBody.className = 'text-sm text-slate-800';
	messageBody.textContent = payload.message ?? '';
	div.append(header, messageBody);
	list.appendChild(div);
	list.scrollTop = list.scrollHeight;
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
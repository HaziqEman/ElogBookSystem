import './async-forms';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import './chat';

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

/*
 * Realtime glue. This file only subscribes to Pusher and re-emits browser events.
 * All chat rendering lives in chat.js (listens to: chat:message, chat:deleted, chat:read, chat:presence, chat:reconnected).
 */
const emit = (name, detail) => window.dispatchEvent(new CustomEvent(name, { detail }));

const isCurrentUserMember = (member) => {
	const currentUser = window.CURRENT_USER ?? null;
	if (! currentUser || ! member) return false;
	return currentUser.role === member.role && Number(currentUser.id) === Number(member.id);
};

const getOtherParticipantRole = () => {
	if (window.CHAT_PARTNER_ROLE) return window.CHAT_PARTNER_ROLE;
	const currentUser = window.CURRENT_USER ?? null;
	if (! currentUser) return null;
	return currentUser.role === 'student' ? 'lecturer' : 'student';
};

const otherParticipantIsOnline = (members) => {
	const otherRole = getOtherParticipantRole();
	if (! otherRole || ! Array.isArray(members)) return false;
	return members.some((member) => ! isCurrentUserMember(member) && member.role === otherRole);
};

const leaveRealtimeChannels = () => {
	const id = window.CONVERSATION_ID ?? null;
	if (! id) return;
	window.Echo.leave(`private-conversation.${id}`);
	window.Echo.leave(`presence-conversation.${id}`);
};

const initRealtimeChannels = () => {
	const id = window.CONVERSATION_ID ?? null;
	if (! id) return;

	const channel = window.Echo.private(`private-conversation.${id}`);

	channel.listen('MessageSent', (e) => emit('chat:message', e.message));
	channel.listen('MessageDeleted', (e) => emit('chat:deleted', e.message));
	channel.listen('MessagesRead', (e) => emit('chat:read', e.read));

	window.Echo.join(`presence-conversation.${id}`)
		.here((members) => emit('chat:presence', { online: otherParticipantIsOnline(members) }))
		.joining((member) => {
			if (! isCurrentUserMember(member) && member.role === getOtherParticipantRole()) emit('chat:presence', { online: true });
		})
		.leaving((member) => {
			if (! isCurrentUserMember(member) && member.role === getOtherParticipantRole()) emit('chat:presence', { online: false });
		});

	// A PWA that was in the background loses its socket and can miss events:
	// when the connection comes back, ask the server for what we missed.
	const connection = window.Echo.connector?.pusher?.connection;
	if (connection) {
		let first = true;
		connection.bind('connected', () => {
			if (first) { first = false; return; }
			emit('chat:reconnected');
		});
		connection.bind('state_change', (s) => emit('chat:connection', { state: s.current }));
	}

	const logoutLink = document.querySelector('a[href="/logout"]');
	if (logoutLink) logoutLink.addEventListener('click', leaveRealtimeChannels);
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initRealtimeChannels);
} else {
	initRealtimeChannels();
}

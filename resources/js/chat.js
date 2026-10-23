/*
 * WhatsApp-style chat UI (student / lecturer / supervisor share this file).
 * Server contract: see Message::toChatPayload() and ChatController.
 * Realtime events come from app.js as window events: chat:message, chat:deleted, chat:read, chat:presence, chat:reconnected.
 */

const EMOJIS = ['😀','😁','😂','🤣','😊','😍','😘','😎','🤔','😅','😉','🙂','🙃','😢','😭','😡','👍','👎','👏','🙏','💪','🙌','👌','✌️','🔥','🎉','✅','❌','⭐','💯','❤️','💙','📎','📄','📷','📅','⏰','🕒','📌','💡','👋','🤝','😴','🤒','🙈','🥲','😇','🤗'];

/* ---------- layout (shared by chat + list-only pages) ---------- */
function fitRoot(root) {
	const vh = window.visualViewport ? window.visualViewport.height : window.innerHeight;   // shrinks when the mobile keyboard opens
	const wide = window.innerWidth >= 768;
	const gap = wide ? 32 : 16;
	root.style.height = `${Math.max(wide ? 640 : 480, vh - root.getBoundingClientRect().top - gap)}px`;
}
function setupFit(root) {
	const f = () => fitRoot(root);
	window.addEventListener('resize', f);
	window.visualViewport?.addEventListener('resize', f);
	f(); setTimeout(f, 250);   // again after fonts / icons settle
}

/* ---------- left conversation list (lecturer / supervisor / student tabs) ---------- */
function initList(root) {
	const side = root.querySelector('.cm-side');
	if (! side) return;
	const L = window.CHAT_LIST || null;
	const box = side.querySelector('.cm-contacts');

	const short = (iso) => {
		if (! iso) return '';
		const d = new Date(iso); const now = new Date(); const y = new Date(); y.setDate(now.getDate() - 1);
		const same = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
		if (same(d, now)) return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
		if (same(d, y)) return 'Yesterday';
		return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
	};
	const paint = () => side.querySelectorAll('[data-iso]').forEach((e) => { e.textContent = short(e.dataset.iso); });
	paint();

	const search = side.querySelector('.cm-search input');
	search?.addEventListener('input', () => {
		const q = search.value.trim().toLowerCase();
		side.querySelectorAll('.cm-contact').forEach((c) => { c.hidden = !! q && ! (c.dataset.name || '').toLowerCase().includes(q); });
	});

	const previewOf = (m) => {
		if (m.deleted) return 'This message was deleted';
		if (m.message) return m.message;
		if (m.type === 'image') return '📷 Photo';
		if (m.type === 'audio') return '🎤 Voice message';
		return `📎 ${m.attachment?.name || 'File'}`;
	};

	const bump = (c, m, countUnread) => {
		const pv = c.querySelector('.cm-pv'); if (pv) pv.textContent = (m._mine ? 'You: ' : '') + previewOf(m);
		const tm = c.querySelector('.cm-ct-time'); if (tm) { tm.dataset.iso = m.created_at_iso || new Date().toISOString(); tm.textContent = short(tm.dataset.iso); }
		if (m.conversation_id) {
			c.dataset.conversationId = m.conversation_id;
			if (L?.openBase) c.href = `${L.openBase}/${m.conversation_id}`;   // supervisor: first message creates the conversation
		}
		if (countUnread) {
			let b = c.querySelector('.cm-badge');
			if (! b) { b = document.createElement('span'); b.className = 'cm-badge'; b.textContent = '0'; c.querySelector('.cm-row2')?.appendChild(b); }
			b.textContent = String((parseInt(b.textContent, 10) || 0) + 1);
		}
		box?.prepend(c);
	};

	// someone wrote to me
	if (L?.channel && window.Echo) {
		window.Echo.private(L.channel).listen('MessageSent', (e) => {
			const m = e.message;
			if (! m || m.sender_type !== L.partnerType) return;
			const c = side.querySelector(`.cm-contact[data-student-id="${m.sender_id}"]`);
			if (! c) return;
			const open = String(m.conversation_id) === String(window.CONVERSATION_ID || '');
			bump(c, m, ! open);
		});
	}

	// I sent something in the open chat
	window.addEventListener('chat:bump', (e) => {
		const m = { ...e.detail, _mine: true };
		const c = side.querySelector(`.cm-contact[data-conversation-id="${m.conversation_id}"]`);
		if (c) bump(c, m, false);
	});
}

function initChat() {
	const root = document.getElementById('cm-root');
	if (! root) return;
	setupFit(root);
	initList(root);
	const cfg = window.CHAT;
	if (! cfg) return;   // list-only page (no conversation selected)

	const me = cfg.me;
	const urls = cfg.urls;
	const maxBytes = (cfg.maxMb || 10) * 1024 * 1024;
	const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
	const touch = window.matchMedia('(pointer: coarse)').matches;

	const $ = (id) => document.getElementById(id);
	const list = $('cm-list'), form = $('cm-form'), input = $('cm-input');
	const sendBtn = $('cm-send'), micBtn = $('cm-mic'), tray = $('cm-tray');

	const state = {
		msgs: new Map(),   // id(string) -> payload
		nodes: new Map(),  // id(string) -> element
		retry: new Map(),  // tmp id -> args to resend
		lastDay: null,
		tray: [],          // [{file, url}]
		seq: 0,
		rec: null,
		typingTimer: null,
		lastWhisper: 0,
		online: false,
		activeAudio: null,
	};

	const isImg = (m) => m.type === 'image' || (m.attachment && (String(m.attachment.mime || '').startsWith('image/') || /\.(jpe?g|png|gif|webp|heic|heif)$/i.test(m.attachment.name || '')));
	const isMine = (m) => m.sender_type === me.type && Number(m.sender_id) === Number(me.id);
	const esc = (s) => String(s ?? '');
	const el = (tag, cls, html) => { const e = document.createElement(tag); if (cls) e.className = cls; if (html !== undefined) e.innerHTML = html; return e; };

	/* ---------- formatting ---------- */
	const fmtTime = (iso) => iso ? new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false }) : '';
	const dayKey = (iso) => { const d = iso ? new Date(iso) : new Date(); return `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`; };
	const dayLabel = (iso) => {
		const d = iso ? new Date(iso) : new Date();
		const today = new Date(); const y = new Date(); y.setDate(today.getDate() - 1);
		const full = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
		if (dayKey(d.toISOString()) === dayKey(today.toISOString())) return `Today, ${full}`;
		if (dayKey(d.toISOString()) === dayKey(y.toISOString())) return `Yesterday, ${full}`;
		return full;
	};
	const fmtSize = (b) => { if (! b) return ''; const u = ['B', 'KB', 'MB']; let i = 0; let n = b; while (n >= 1024 && i < 2) { n /= 1024; i++; } return `${n.toFixed(i ? 1 : 0)} ${u[i]}`; };
	const fmtDur = (s) => { s = Math.max(0, Math.round(s || 0)); return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`; };

	const fileIcon = (name = '', mime = '') => {
		const ext = (name.split('.').pop() || '').toLowerCase();
		if (ext === 'pdf' || mime === 'application/pdf') return ['fa-file-pdf', '#dc2626'];
		if (['doc', 'docx'].includes(ext)) return ['fa-file-word', '#2563eb'];
		if (['xls', 'xlsx', 'csv'].includes(ext)) return ['fa-file-excel', '#16a34a'];
		if (['ppt', 'pptx'].includes(ext)) return ['fa-file-powerpoint', '#ea580c'];
		return ['fa-file-lines', '#64748b'];
	};

	const linkify = (text, parent) => {
		String(text).split(/(https?:\/\/[^\s<]+)/g).forEach((part, i) => {
			if (i % 2) {
				const a = el('a'); a.href = part; a.textContent = part; a.target = '_blank'; a.rel = 'noopener noreferrer';
				parent.appendChild(a);
			} else if (part) parent.appendChild(document.createTextNode(part));
		});
	};

	const toast = (msg) => {
		const t = $('cm-toast'); if (! t) return;
		t.textContent = msg; t.hidden = false;
		clearTimeout(toast.t); toast.t = setTimeout(() => { t.hidden = true; }, 3500);
	};

	/* ---------- bubbles ---------- */
	const tickHTML = (m) => {
		if (m.failed) return '<i class="fa-solid fa-circle-exclamation cm-fail"></i>';
		if (m.pending) return '<i class="fa-regular fa-clock"></i>';
		if (m.read_at) return '<i class="fa-solid fa-check-double cm-read"></i>';
		return '<i class="fa-solid fa-check"></i>';
	};

	const pauseOthers = (except) => { if (state.activeAudio && state.activeAudio !== except) state.activeAudio.pause(); };

	const audioNode = (a) => {
		const wrap = el('div', 'cm-audio');
		const btn = el('button', 'cm-play', '<i class="fa-solid fa-play"></i>'); btn.type = 'button'; btn.setAttribute('aria-label', 'Play voice message');
		const bar = el('div', 'cm-bar'); const fill = el('i'); bar.appendChild(fill);
		const dur = el('span', 'cm-dur', fmtDur(a.duration));
		let audio = null;
		const total = () => (audio && isFinite(audio.duration) && audio.duration > 0 ? audio.duration : (a.duration || 1));
		btn.addEventListener('click', () => {
			if (! audio) {
				audio = new Audio(a.url); audio.preload = 'metadata';
				audio.addEventListener('timeupdate', () => { fill.style.width = `${Math.min(100, (audio.currentTime / total()) * 100)}%`; dur.textContent = fmtDur(audio.currentTime); });
				audio.addEventListener('ended', () => { btn.innerHTML = '<i class="fa-solid fa-play"></i>'; fill.style.width = '0'; dur.textContent = fmtDur(a.duration); });
				audio.addEventListener('pause', () => { btn.innerHTML = '<i class="fa-solid fa-play"></i>'; });
				audio.addEventListener('play', () => { btn.innerHTML = '<i class="fa-solid fa-pause"></i>'; });
			}
			if (audio.paused) { pauseOthers(audio); state.activeAudio = audio; audio.play().catch(() => toast('Could not play this voice message.')); } else audio.pause();
		});
		bar.addEventListener('click', (e) => { if (! audio) return; const r = bar.getBoundingClientRect(); audio.currentTime = ((e.clientX - r.left) / r.width) * total(); });
		wrap.append(btn, bar, dur);
		return wrap;
	};

	const buildBubble = (m) => {
		const mine = isMine(m);
		const row = el('div', `cm-row ${mine ? 'cm-me' : 'cm-them'}`);
		row.dataset.id = m.message_id;

		if (! mine) row.appendChild(el('div', `cm-avatar cm-tone-${cfg.partner.tone}`, esc(cfg.partner.initials)));

		const bubble = el('div', 'cm-bubble');
		const a = m.attachment;

		if (m.deleted) {
			bubble.classList.add('cm-deleted');
			bubble.appendChild(el('span', 'cm-del-text', '<i class="fa-solid fa-ban"></i> This message was deleted'));
		} else {
			if (a && isImg(m)) {
				const img = el('img', 'cm-img'); img.src = a.url; img.alt = a.name || 'Photo'; img.loading = 'lazy';
				img.addEventListener('click', () => openLightbox(a));
				bubble.appendChild(img);
			} else if (a && m.type === 'audio') {
				bubble.appendChild(audioNode(a));
			} else if (a) {
				const [ic, color] = fileIcon(a.name, a.mime);
				const f = el('a', 'cm-file'); f.href = a.url; f.target = '_blank'; f.rel = 'noopener noreferrer';
				f.innerHTML = `<span class="cm-file-ic"><i class="fa-solid ${ic}" style="color:${color}"></i></span>`;
				const info = el('span', 'cm-file-info'); const n = el('b'); n.textContent = a.name || 'File'; const s = el('small', '', `${fmtSize(a.size)}${a.size ? ' · ' : ''}Tap to open`);
				info.append(n, s); f.appendChild(info); bubble.appendChild(f);
			}
			if (m.message) { const t = el('div', 'cm-text'); linkify(m.message, t); bubble.appendChild(t); }
			if (m.pending && a) bubble.appendChild(el('div', 'cm-prog', '<i></i>'));
		}

		const meta = el('div', 'cm-meta');
		meta.appendChild(el('span', 'cm-time', fmtTime(m.created_at_iso)));
		if (mine && ! m.deleted) meta.appendChild(el('span', 'cm-tick', tickHTML(m)));
		bubble.appendChild(meta);

		if (m.failed) {
			const fl = el('div', 'cm-failrow', '<span>Not sent.</span> <button type="button" data-act="retry">Retry</button> <button type="button" data-act="discard">Discard</button>');
			bubble.appendChild(fl);
		}

		if (! m.pending && ! m.failed) {
			const more = el('button', 'cm-more', '<i class="fa-solid fa-chevron-down"></i>');
			more.type = 'button'; more.setAttribute('aria-label', 'Message options');
			more.addEventListener('click', () => openSheet(String(m.message_id)));
			bubble.appendChild(more);
		}

		row.appendChild(bubble);
		return row;
	};

	const nearBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 140;
	const scrollBottom = () => { list.scrollTop = list.scrollHeight; $('cm-newpill').hidden = true; };

	const ensureDay = (iso) => {
		const k = dayKey(iso);
		if (state.lastDay === k) return;
		state.lastDay = k;
		list.appendChild(el('div', 'cm-day', `<span>${dayLabel(iso)}</span>`));
	};

	const addMessage = (m, { silent = false } = {}) => {
		const id = String(m.message_id);
		if (state.msgs.has(id)) { patchMessage(m); return; }
		const stick = nearBottom() || isMine(m);
		$('cm-empty').hidden = true;
		ensureDay(m.created_at_iso);
		const node = buildBubble(m);
		list.appendChild(node);
		state.msgs.set(id, m); state.nodes.set(id, node);
		if (silent) return;
		if (stick) scrollBottom(); else $('cm-newpill').hidden = false;
	};

	const rebuild = (id) => {
		const m = state.msgs.get(id); const old = state.nodes.get(id);
		if (! m || ! old) return;
		const n = buildBubble(m); old.replaceWith(n); state.nodes.set(id, n);
	};

	const patchMessage = (m) => { const id = String(m.message_id); state.msgs.set(id, { ...state.msgs.get(id), ...m }); rebuild(id); };

	const setTicks = (id) => { const m = state.msgs.get(id); const n = state.nodes.get(id); const t = n?.querySelector('.cm-tick'); if (t && m) t.innerHTML = tickHTML(m); };

	/* ---------- network ---------- */
	const headers = () => {
		const h = { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
		const sid = window.Echo?.socketId?.(); if (sid) h['X-Socket-ID'] = sid;
		return h;
	};

	const xhrSend = (fd, onProgress) => new Promise((resolve, reject) => {
		const x = new XMLHttpRequest();
		x.open('POST', urls.store);
		Object.entries(headers()).forEach(([k, v]) => x.setRequestHeader(k, v));
		x.upload.onprogress = (e) => { if (e.lengthComputable) onProgress?.(e.loaded / e.total); };
		x.onload = () => {
			let body = {}; try { body = JSON.parse(x.responseText); } catch (_) {}
			if (x.status >= 200 && x.status < 300) resolve(body.message);
			else reject(new Error(body.message || (x.status === 413 ? 'File is too large for the server.' : 'Could not send message.')));
		};
		x.onerror = () => reject(new Error('Network error. Check your connection.'));
		x.send(fd);
	});

	const sendMessage = async (args, tmpId = null) => {
		const { text = '', file = null, voice = false, duration = 0 } = args;
		const id = tmpId || `tmp-${++state.seq}`;
		const blobUrl = file ? URL.createObjectURL(file) : null;
		const kind = ! file ? 'text' : voice ? 'audio' : file.type.startsWith('image/') ? 'image' : 'file';

		const temp = {
			message_id: id, sender_type: me.type, sender_id: me.id, sender_name: me.name, type: kind, message: text,
			attachment: file ? { url: blobUrl, name: file.name, size: file.size, mime: file.type, duration } : null,
			created_at_iso: new Date().toISOString(), read_at: null, pending: true, failed: false,
		};

		if (tmpId) { state.msgs.set(id, temp); rebuild(id); } else addMessage(temp);
		state.retry.set(id, args);

		const fd = new FormData();
		if (text) fd.append('message', text);
		if (file) { fd.append('attachment', file, file.name); if (voice) { fd.append('voice', '1'); fd.append('duration', String(Math.round(duration))); } }

		try {
			const real = await xhrSend(fd, (p) => { const bar = state.nodes.get(id)?.querySelector('.cm-prog i'); if (bar) bar.style.width = `${Math.round(p * 100)}%`; });
			const old = state.nodes.get(id);
			state.msgs.delete(id); state.nodes.delete(id); state.retry.delete(id);
			if (blobUrl) setTimeout(() => URL.revokeObjectURL(blobUrl), 5000);

			const rid = String(real.message_id);
			if (state.msgs.has(rid)) { old?.remove(); return; }   // the broadcast beat the HTTP response
			state.msgs.set(rid, real);
			const node = buildBubble(real);
			old ? old.replaceWith(node) : list.appendChild(node);
			state.nodes.set(rid, node);
			scrollBottom();
			window.dispatchEvent(new CustomEvent('chat:bump', { detail: real }));
		} catch (err) {
			state.msgs.set(id, { ...state.msgs.get(id), pending: false, failed: true });
			rebuild(id);
			toast(err.message);
		}
	};

	list.addEventListener('click', (e) => {
		const b = e.target.closest('button[data-act]'); if (! b) return;
		const id = b.closest('.cm-row')?.dataset.id; if (! id) return;
		if (b.dataset.act === 'retry') sendMessage(state.retry.get(id), id);
		if (b.dataset.act === 'discard') { state.nodes.get(id)?.remove(); state.nodes.delete(id); state.msgs.delete(id); state.retry.delete(id); }
	});

	const markRead = (() => { let t; return () => { if (document.hidden) return; clearTimeout(t); t = setTimeout(() => { fetch(urls.read, { method: 'POST', headers: headers(), credentials: 'same-origin' }).catch(() => {}); }, 300); }; })();

	const applyReadUpTo = (upTo, at) => {
		if (! upTo) return;
		state.msgs.forEach((m, id) => { if (isMine(m) && /^\d+$/.test(id) && Number(id) <= Number(upTo) && ! m.read_at) { m.read_at = at || new Date().toISOString(); setTicks(id); } });
	};

	const catchUp = async () => {
		const ids = [...state.msgs.keys()].filter((k) => /^\d+$/.test(k)).map(Number);
		const after = ids.length ? Math.max(...ids) : 0;
		try {
			const r = await fetch(`${urls.since}?after=${after}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
			if (! r.ok) return;
			const d = await r.json();
			d.messages.forEach((m) => addMessage(m));
			applyReadUpTo(d.my_read_up_to);
			markRead();
		} catch (_) {}
	};

	/* ---------- realtime events ---------- */
	window.addEventListener('chat:message', (e) => {
		const m = e.detail;
		if (Number(m.conversation_id) !== Number(cfg.conversationId)) return;
		addMessage(m);
		if (! isMine(m)) { setTyping(false); markRead(); }
	});
	window.addEventListener('chat:deleted', (e) => {
		const id = String(e.detail.message_id); const m = state.msgs.get(id); if (! m) return;
		state.msgs.set(id, { ...m, deleted: true, attachment: null, message: null, type: 'text' }); rebuild(id);
	});
	window.addEventListener('chat:read', (e) => {
		const r = e.detail; if (r.reader_type === me.type && Number(r.reader_id) === Number(me.id)) return;
		applyReadUpTo(r.up_to, r.read_at);
	});
	window.addEventListener('chat:presence', (e) => { state.online = !! e.detail.online; paintStatus(); });
	window.addEventListener('chat:reconnected', catchUp);

	const paintStatus = () => {
		const s = $('cm-status'); const typing = $('cm-typing').hidden === false;
		s.classList.toggle('on', state.online);
		$('cm-status-text').textContent = typing ? 'typing…' : state.online ? 'Online' : 'Offline';
	};
	const setTyping = (on) => { $('cm-typing').hidden = ! on; paintStatus(); };

	// typing indicator (Pusher client events: enable "Client events" in the Pusher app settings; harmless if disabled)
	let channel = null;
	try {
		channel = window.Echo.private(`private-conversation.${cfg.conversationId}`);
		channel.listenForWhisper('typing', () => { setTyping(true); clearTimeout(state.typingTimer); state.typingTimer = setTimeout(() => setTyping(false), 3500); });
	} catch (_) {}
	const whisperTyping = () => {
		if (! channel || Date.now() - state.lastWhisper < 2500) return;
		state.lastWhisper = Date.now();
		try { channel.whisper('typing', { role: me.role }); } catch (_) {}
	};

	/* ---------- composer ---------- */
	const autosize = () => { input.style.height = 'auto'; input.style.height = `${Math.min(input.scrollHeight, 140)}px`; };
	const refreshButtons = () => {
		const hasContent = input.value.trim().length > 0 || state.tray.length > 0;
		sendBtn.hidden = ! hasContent;
		micBtn.hidden = hasContent || ! (navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
	};

	input.addEventListener('input', () => { autosize(); refreshButtons(); if (input.value.trim()) whisperTyping(); });
	input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && ! e.shiftKey && ! touch) { e.preventDefault(); form.requestSubmit(); } });

	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		const text = input.value.trim();
		const files = state.tray.splice(0);
		if (! text && ! files.length) return;
		input.value = ''; autosize(); renderTray(); refreshButtons(); input.focus();

		if (! files.length) return sendMessage({ text });
		for (let i = 0; i < files.length; i++) {
			// eslint-disable-next-line no-await-in-loop
			await sendMessage({ text: i === 0 ? text : '', file: files[i].file });
		}
	});

	/* attachments */
	const prepImage = async (file) => {
		if (! file.type.startsWith('image/') || file.type === 'image/gif' || file.size < 600 * 1024) return file;
		try {
			const bmp = await createImageBitmap(file);
			const s = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
			const c = document.createElement('canvas'); c.width = Math.round(bmp.width * s); c.height = Math.round(bmp.height * s);
			c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
			const blob = await new Promise((r) => c.toBlob(r, 'image/jpeg', 0.82));
			if (! blob || blob.size >= file.size) return file;
			return new File([blob], `${file.name.replace(/\.\w+$/, '')}.jpg`, { type: 'image/jpeg' });
		} catch (_) { return file; }
	};

	const addToTray = async (fileList) => {
		for (const f of Array.from(fileList)) {
			const file = await prepImage(f);
			if (file.size > maxBytes) { toast(`${f.name} is larger than ${cfg.maxMb} MB.`); continue; }
			state.tray.push({ file, url: file.type.startsWith('image/') ? URL.createObjectURL(file) : null });
		}
		renderTray(); refreshButtons();
	};

	function renderTray() {
		tray.innerHTML = '';
		tray.hidden = state.tray.length === 0;
		state.tray.forEach((t, i) => {
			const chip = el('div', 'cm-chip');
			if (t.url) chip.innerHTML = `<img src="${t.url}" alt="">`;
			else { const [ic, color] = fileIcon(t.file.name, t.file.type); chip.innerHTML = `<span class="cm-chip-ic"><i class="fa-solid ${ic}" style="color:${color}"></i></span>`; }
			const info = el('div', 'cm-chip-info'); const n = el('b'); n.textContent = t.file.name; info.append(n, el('small', '', fmtSize(t.file.size)));
			const x = el('button', 'cm-chip-x', '<i class="fa-solid fa-xmark"></i>'); x.type = 'button'; x.setAttribute('aria-label', 'Remove');
			x.addEventListener('click', () => { if (t.url) URL.revokeObjectURL(t.url); state.tray.splice(i, 1); renderTray(); refreshButtons(); });
			chip.append(info, x); tray.appendChild(chip);
		});
	}

	const menu = $('cm-menu');
	const closeMenus = () => { menu.hidden = true; $('cm-emoji').hidden = true; };
	$('cm-attach').addEventListener('click', (e) => { e.stopPropagation(); const open = menu.hidden; closeMenus(); menu.hidden = ! open; });
	$('cm-emoji-btn').addEventListener('click', (e) => { e.stopPropagation(); const open = $('cm-emoji').hidden; closeMenus(); $('cm-emoji').hidden = ! open; });
	document.addEventListener('click', (e) => { if (! e.target.closest('.cm-menu, .cm-emoji, #cm-attach, #cm-emoji-btn')) closeMenus(); });

	const emojiBox = $('cm-emoji');
	EMOJIS.forEach((em) => { const b = el('button', '', em); b.type = 'button'; b.addEventListener('click', () => { const s = input.selectionStart ?? input.value.length; input.setRangeText(em, s, input.selectionEnd ?? s, 'end'); input.dispatchEvent(new Event('input')); input.focus(); }); emojiBox.appendChild(b); });

	$('cm-pick-doc').addEventListener('click', () => { closeMenus(); $('cm-in-doc').click(); });
	$('cm-pick-photo').addEventListener('click', () => { closeMenus(); $('cm-in-photo').click(); });
	$('cm-pick-cam').addEventListener('click', () => { closeMenus(); if (touch) $('cm-in-cam').click(); else openWebcam(); });
	['cm-in-doc', 'cm-in-photo', 'cm-in-cam'].forEach((id) => $(id).addEventListener('change', (e) => { addToTray(e.target.files); e.target.value = ''; }));

	// drag & drop and paste (desktop)
	['dragover', 'dragenter'].forEach((ev) => root.addEventListener(ev, (e) => { if (e.dataTransfer?.types?.includes('Files')) { e.preventDefault(); root.classList.add('cm-drop'); } }));
	['dragleave', 'drop'].forEach((ev) => root.addEventListener(ev, () => root.classList.remove('cm-drop')));
	root.addEventListener('drop', (e) => { if (e.dataTransfer?.files?.length) { e.preventDefault(); addToTray(e.dataTransfer.files); } });
	input.addEventListener('paste', (e) => { const fs = Array.from(e.clipboardData?.files || []); if (fs.length) { e.preventDefault(); addToTray(fs); } });

	/* webcam (desktop camera) */
	let camStream = null;
	async function openWebcam() {
		if (! navigator.mediaDevices?.getUserMedia) return toast('Camera is not available on this device.');
		try {
			camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
			$('cm-cam-video').srcObject = camStream; $('cm-cam').hidden = false;
		} catch (_) { toast('Camera permission was denied.'); }
	}
	const closeWebcam = () => { camStream?.getTracks().forEach((t) => t.stop()); camStream = null; $('cm-cam').hidden = true; };
	$('cm-cam-close').addEventListener('click', closeWebcam);
	$('cm-cam-snap').addEventListener('click', () => {
		const v = $('cm-cam-video'); const c = document.createElement('canvas'); c.width = v.videoWidth; c.height = v.videoHeight;
		c.getContext('2d').drawImage(v, 0, 0);
		c.toBlob((b) => { if (b) addToTray([new File([b], `photo-${Date.now()}.jpg`, { type: 'image/jpeg' })]); closeWebcam(); }, 'image/jpeg', 0.85);
	});

	/* voice notes */
	const recBar = $('cm-rec');
	const stopTimer = () => { clearInterval(state.rec?.tick); };
	const endRec = (send) => {
		const r = state.rec; if (! r) return;
		r.send = send; stopTimer();
		if (r.recorder.state !== 'inactive') r.recorder.stop();
		recBar.hidden = true; form.hidden = false;
	};

	micBtn.addEventListener('click', async () => {
		let stream;
		try { stream = await navigator.mediaDevices.getUserMedia({ audio: true }); } catch (_) { return toast('Microphone permission was denied.'); }
		const mime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'].find((t) => MediaRecorder.isTypeSupported(t)) || '';
		const recorder = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
		const chunks = []; const started = Date.now();
		state.rec = { recorder, send: false, tick: null };

		recorder.ondataavailable = (e) => { if (e.data.size) chunks.push(e.data); };
		recorder.onstop = () => {
			stream.getTracks().forEach((t) => t.stop());
			const send = state.rec?.send; state.rec = null;
			if (! send || ! chunks.length) return;
			const type = recorder.mimeType || mime || 'audio/webm';
			const ext = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
			const file = new File([new Blob(chunks, { type })], `voice-note.${ext}`, { type });
			if (file.size > maxBytes) return toast('Voice note is too long.');
			sendMessage({ file, voice: true, duration: (Date.now() - started) / 1000 });
		};

		recorder.start();
		form.hidden = true; recBar.hidden = false; $('cm-rec-time').textContent = '0:00';
		state.rec.tick = setInterval(() => {
			const s = (Date.now() - started) / 1000;
			$('cm-rec-time').textContent = fmtDur(s);
			if (s >= 300) endRec(true);   // 5 minute cap
		}, 250);
	});
	$('cm-rec-cancel').addEventListener('click', () => endRec(false));
	$('cm-rec-send').addEventListener('click', () => endRec(true));

	/* ---------- delete sheet (reuses your existing endpoints) ---------- */
	const sheet = $('cm-sheet'); let activeId = null; let confirming = false;
	const everyoneBtn = $('cm-del-everyone');
	function openSheet(id) {
		const m = state.msgs.get(id); if (! m) return;
		activeId = id; confirming = false;
		everyoneBtn.hidden = ! (isMine(m) && ! m.deleted);
		everyoneBtn.textContent = 'Delete for everyone';
		sheet.hidden = false;
	}
	const closeSheet = () => { sheet.hidden = true; activeId = null; };
	const postDelete = (kind) => fetch(`${urls.deleteBase}/${activeId}/${kind}`, { method: 'POST', headers: { ...headers(), 'Content-Type': 'application/json' }, body: '{}', credentials: 'same-origin' });
	$('cm-del-cancel').addEventListener('click', closeSheet);
	sheet.addEventListener('click', (e) => { if (e.target === sheet) closeSheet(); });
	$('cm-del-me').addEventListener('click', async () => {
		const id = activeId; const r = await postDelete('delete-for-me');
		if (r.ok) { state.nodes.get(id)?.remove(); state.nodes.delete(id); state.msgs.delete(id); } else toast('Could not delete.');
		closeSheet();
	});
	everyoneBtn.addEventListener('click', async () => {
		if (! confirming) { confirming = true; everyoneBtn.textContent = 'Tap again to confirm'; return; }
		const id = activeId; const r = await postDelete('delete-for-everyone');
		if (r.ok) { const m = state.msgs.get(id); state.msgs.set(id, { ...m, deleted: true, attachment: null, message: null, type: 'text' }); rebuild(id); } else toast('Could not delete.');
		closeSheet();
	});

	/* ---------- lightbox ---------- */
	function openLightbox(a) { $('cm-lb-img').src = a.url; $('cm-lb-open').href = a.url; $('cm-lb').hidden = false; }
	const closeLb = () => { $('cm-lb').hidden = true; $('cm-lb-img').src = ''; };
	$('cm-lb-close').addEventListener('click', closeLb);
	$('cm-lb').addEventListener('click', (e) => { if (e.target.id === 'cm-lb') closeLb(); });
	document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeLb(); closeSheet(); closeMenus(); } });

	/* ---------- layout / PWA behaviour ---------- */
	window.visualViewport?.addEventListener('resize', () => { if (nearBottom()) scrollBottom(); });

	const net = () => { $('cm-offline').hidden = navigator.onLine; };
	window.addEventListener('offline', net);
	window.addEventListener('online', () => { net(); catchUp(); });

	document.addEventListener('visibilitychange', () => { if (! document.hidden) { catchUp(); markRead(); } });
	window.addEventListener('focus', markRead);
	list.addEventListener('scroll', () => { if (nearBottom()) $('cm-newpill').hidden = true; });
	$('cm-newpill').addEventListener('click', scrollBottom);

	/* ---------- boot ---------- */
	(cfg.messages || []).forEach((m) => addMessage(m, { silent: true }));
	$('cm-empty').hidden = state.msgs.size > 0;
	net(); refreshButtons(); scrollBottom();
	setTimeout(scrollBottom, 300);
	markRead();
}

// app.js imports this file before it creates window.Echo, so start on the next tick (after app.js finished running).
const boot = () => setTimeout(initChat, 0);
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();

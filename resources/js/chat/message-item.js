export function createMessageItemRenderer({
	me,
	partner,
	state,
	isImg,
	el,
	esc,
	fileIcon,
	fmtDur,
	fmtSize,
	linkify,
	fmtTime,
	tickHTML,
	openLightbox,
	toast,
}) {
	const isMine = (message) => message.sender_type === me.type && Number(message.sender_id) === Number(me.id);
	const pauseOthers = (except) => {
		if (state.activeAudio && state.activeAudio !== except) state.activeAudio.pause();
	};

	const audioNode = (attachment) => {
		const wrap = el('div', 'cm-audio');
		const btn = el('button', 'cm-play', '<i class="fa-solid fa-play"></i>');
		btn.type = 'button';
		btn.setAttribute('aria-label', 'Play voice message');
		const bar = el('div', 'cm-bar');
		const fill = el('i');
		bar.appendChild(fill);
		const dur = el('span', 'cm-dur', fmtDur(attachment.duration));
		let audio = null;
		const total = () => (audio && isFinite(audio.duration) && audio.duration > 0 ? audio.duration : (attachment.duration || 1));

		btn.addEventListener('click', () => {
			if (! audio) {
				audio = new Audio(attachment.url);
				audio.preload = 'metadata';
				audio.addEventListener('timeupdate', () => {
					fill.style.width = `${Math.min(100, (audio.currentTime / total()) * 100)}%`;
					dur.textContent = fmtDur(audio.currentTime);
				});
				audio.addEventListener('ended', () => {
					btn.innerHTML = '<i class="fa-solid fa-play"></i>';
					fill.style.width = '0';
					dur.textContent = fmtDur(attachment.duration);
				});
				audio.addEventListener('pause', () => {
					btn.innerHTML = '<i class="fa-solid fa-play"></i>';
				});
				audio.addEventListener('play', () => {
					btn.innerHTML = '<i class="fa-solid fa-pause"></i>';
				});
			}
			if (audio.paused) {
				pauseOthers(audio);
				state.activeAudio = audio;
				audio.play().catch(() => toast('Could not play this voice message.'));
			} else {
				audio.pause();
			}
		});
		bar.addEventListener('click', (event) => {
			if (! audio) return;
			const rect = bar.getBoundingClientRect();
			audio.currentTime = ((event.clientX - rect.left) / rect.width) * total();
		});
		wrap.append(btn, bar, dur);

		return wrap;
	};

	return (message) => {
		const mine = isMine(message);
		const row = el('div', `cm-row ${mine ? 'cm-me' : 'cm-them'}`);
		row.dataset.id = message.message_id;

		if (! mine) row.appendChild(el('div', `cm-avatar cm-tone-${partner.tone}`, esc(partner.initials)));

		const bubble = el('div', 'cm-bubble');
		const attachment = message.attachment;

		if (message.deleted) {
			bubble.classList.add('cm-deleted');
			bubble.appendChild(el('span', 'cm-del-text', '<i class="fa-solid fa-ban"></i> This message was deleted'));
		} else {
			if (attachment && isImg(message)) {
				const img = el('img', 'cm-img');
				img.src = attachment.url;
				img.alt = attachment.name || 'Photo';
				img.loading = 'lazy';
				img.addEventListener('click', () => openLightbox(attachment));
				bubble.appendChild(img);
			} else if (attachment && message.type === 'audio') {
				bubble.appendChild(audioNode(attachment));
			} else if (attachment) {
				const [icon, color] = fileIcon(attachment.name, attachment.mime);
				const file = el('a', 'cm-file');
				file.href = attachment.url;
				file.target = '_blank';
				file.rel = 'noopener noreferrer';
				file.innerHTML = `<span class="cm-file-ic"><i class="fa-solid ${icon}" style="color:${color}"></i></span>`;
				const info = el('span', 'cm-file-info');
				const name = el('b');
				name.textContent = attachment.name || 'File';
				const size = el('small', '', `${fmtSize(attachment.size)}${attachment.size ? ' · ' : ''}Tap to open`);
				info.append(name, size);
				file.appendChild(info);
				bubble.appendChild(file);
			}
			if (message.message) {
				const text = el('div', 'cm-text');
				linkify(message.message, text);
				bubble.appendChild(text);
			}
			if (message.pending && attachment) bubble.appendChild(el('div', 'cm-prog', '<i></i>'));
		}

		const meta = el('div', 'cm-meta');
		meta.appendChild(el('span', 'cm-time', fmtTime(message.created_at_iso)));
		if (mine && ! message.deleted) meta.appendChild(el('span', 'cm-tick', tickHTML(message)));
		bubble.appendChild(meta);

		if (message.failed) {
			const failure = el('div', 'cm-failrow', '<span>Not sent.</span> <button type="button" data-act="retry">Retry</button> <button type="button" data-act="discard">Discard</button>');
			bubble.appendChild(failure);
		}

		if (! message.pending && ! message.failed) {
			const more = el('button', 'cm-more', '<i class="fa-solid fa-chevron-down"></i>');
			more.type = 'button';
			more.setAttribute('aria-label', 'Message options');
			more.addEventListener('click', () => openSheet(String(message.message_id)));
			bubble.appendChild(more);
		}

		row.appendChild(bubble);

		return row;
	};
}

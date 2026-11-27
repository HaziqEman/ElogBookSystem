
function openUniversityModal() {
    const modal = document.getElementById('universityModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeUniversityModal() {
    const modal = document.getElementById('universityModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

window.openUniversityModal = openUniversityModal;
window.closeUniversityModal = closeUniversityModal;

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeUniversityModal();
});

(function () {
    if (window.__dashboardBound) return;
    window.__dashboardBound = true;

    var COOLDOWN_SECONDS = 10;

    function byId(id) { return document.getElementById(id); }

 
    // applyBannerDismiss();
  
   
    var form = byId('logbook-form');
    var dateInput = form ? form.querySelector('input[name="activity_date"]') : null;
    var weekInput = form ? form.querySelector('[name="week_no"]') : null;
    var weekHint = byId('week-hint');
    var dateHint = byId('date-hint');
    var submitBtn = byId('submit-entry-btn');
    var firstMonday = form ? form.getAttribute('data-first-monday') : '';
    var totalWeeks = form ? parseInt(form.getAttribute('data-total-weeks'), 10) || 14 : 14;
    var todayKey = form ? form.getAttribute('data-today') : '';
    var workingDays = form
        ? (form.getAttribute('data-working-days') || '1,2,3,4,5').split(',').map(function (day) { return parseInt(day, 10); })
        : [];

    function entryDates() {
        var holder = byId('entry-dates');

        try { return JSON.parse((holder && holder.getAttribute('data-dates')) || '[]'); } catch (e) { return []; }
    }

    function utc(value) {
        var p = value.split('-');
        return Date.UTC(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    }

    function problemFor(value) {
        if (!value) return '';

        if (todayKey && value > todayKey) {
            return 'You cannot write an entry for a day that has not started yet.';
        }

        if (firstMonday) {
            var week = Math.floor((utc(value) - utc(firstMonday)) / 86400000 / 7) + 1;
            if (week < 1 || week > totalWeeks) {
                return 'This date is outside your internship weeks.';
            }


        
        }

        if (entryDates().indexOf(value) !== -1) {
            return 'You already have an entry for this day. Edit it from the week list instead.';
        }

        return '';
    }

    function onDate() {
        if (!dateInput) return;
        var value = dateInput.value;

        if (firstMonday && weekInput && weekHint) {
            if (value) {
                var week = Math.floor((utc(value) - utc(firstMonday)) / 86400000 / 7) + 1;

                weekInput.value = week;

                var weekday = new Date(utc(value)).getUTCDay();
                var iso = weekday === 0 ? 7 : weekday;
                var extraDay = workingDays.indexOf(iso) === -1;

                weekHint.textContent = (week >= 1 && week <= totalWeeks)
                    ? 'This entry belongs to Week ' + week + (extraDay ? ' (an extra day outside your usual schedule)' : '') + '.'
                    : 'This date is outside your ' + totalWeeks + ' internship weeks.';
            } else {
                weekInput.value = '';
                weekHint.textContent = 'Pick a date and the week is worked out for you.';
            }
        }

        var problem = problemFor(value);

        if (dateHint) {
            dateHint.textContent = problem;
            dateHint.classList.toggle('hidden', !problem);
        }

        if (submitBtn) submitBtn.disabled = !!problem;
    }

    if (dateInput) {
        dateInput.addEventListener('change', onDate);
        onDate();
    }

    window.addEventListener('pageshow', onDate);

    // The description is the real entry, so it needs real content. Show a red note instead of failing silently.
    var descField = byId('description-field');
    var descError = byId('description-error');
    var DESC_MIN = 10; // keep the same as 'min:10' in LogbookController

    function descProblem() {
        var length = descField ? descField.value.trim().length : 0;

        if (length === 0) return 'No description written. Please describe what you did before saving.';
        if (length < DESC_MIN) return 'Your description is too short (' + length + '/' + DESC_MIN + ' characters). Please write at least ' + DESC_MIN + ' characters.';

        return '';
    }

    function showDescError(text) {
        if (!descError) return;
        descError.textContent = text || '';
        descError.classList.toggle('hidden', !text);
        if (descField) {
            descField.classList.toggle('border-red-400', !!text);
            descField.classList.toggle('border-[#CBD3E3]', !text);
        }
    }

    if (form && descField) {
        // Capture phase, so this runs before the shared async submit script and can stop it.
        form.addEventListener('submit', function (event) {
            var problem = descProblem();

            if (problem) {
                event.preventDefault();
                event.stopImmediatePropagation();
                showDescError(problem);
                descField.focus();
            }
        }, true);

        descField.addEventListener('input', function () {
            if (!descError || descError.classList.contains('hidden')) return;
            showDescError(descProblem());
        });
    }

    function openEntry(focusName, date) {
        var panel = byId('new-entry');
        if (!panel) return;

        panel.open = true;

        if (date && dateInput) {
            dateInput.value = date;
            onDate();
        }

        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

        if (focusName) {
            var target = panel.querySelector('[name="' + focusName + '"]') || byId(focusName + '-field');
            if (target) setTimeout(function () { target.focus(); }, 350);
        }
    }

     window.openNewEntry = openEntry;


    if (location.hash === '#new-entry') openEntry(null, new URLSearchParams(location.search).get('write'));
    // Edit entry pop-up.
    var modal = byId('edit-modal');
    var modalBody = byId('edit-modal-body');

    function closeEditModal() {
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (modalBody) modalBody.innerHTML = '';
    }

    async function openEditModal(href) {
        if (!modal || !modalBody) {
            if (window.asyncToast) window.asyncToast('The edit popup is unavailable. Reload the page and try again.', 'error');
            return;
        }

        var refreshUrl = new URL(window.location.href);
        var weekSection = byId('logbook-week');
        var selectedWeek = weekSection && weekSection.getAttribute('data-selected-week');

        if (selectedWeek) refreshUrl.searchParams.set('week', selectedWeek);
        refreshUrl.hash = 'logbook-week';

        modalBody.innerHTML = '<div role="status" aria-live="polite" class="flex items-center gap-3 py-8 text-sm font-semibold text-slate-500"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Loading edit form...</div>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        try {
            var response = await fetch(href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                credentials: 'same-origin'
            });

            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }

            if (!response.ok) {
                var message = 'This entry cannot be edited.';

                try { message = (await response.json()).message || message; } catch (e) {}
                closeEditModal();
                if (window.asyncToast) window.asyncToast(message, 'error');

                return;
            }

            modalBody.innerHTML = await response.text();
            var editForm = modalBody.querySelector('form[data-edit-logbook-form]');
            if (editForm) editForm.dataset.refreshUrl = refreshUrl.toString();

            var first = modalBody.querySelector('textarea, input:not([type="hidden"])');
            if (first) first.focus();
        } catch (error) {
            console.error('Could not load the logbook edit form.', error);
            closeEditModal();
            if (window.asyncToast) window.asyncToast('Could not load the edit form. Please try again.', 'error');
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;



        var write = target.closest('a[href="#new-entry"]');

        if (write) {
            event.preventDefault();
            openEntry(write.getAttribute('data-focus'), write.getAttribute('data-write-date'));
            return;
        }

        var edit = target.closest('a[data-edit-entry]');

        if (edit) {
            event.preventDefault();
            openEditModal(edit.href);
            return;
        }

        if (target.id === 'edit-modal' || target.closest('#edit-modal-close') || target.closest('#edit-cancel')) {
            event.preventDefault();
            closeEditModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        var imageLightbox = byId('logbook-image-lightbox');
        if (event.key === 'Escape' && (!imageLightbox || imageLightbox.classList.contains('hidden'))) {
            closeEditModal();
        }
    });

    // After the new entry is saved: clear the form and close the panel.
    document.addEventListener('async:success', function (event) {
        var source = event.target;

        if (form && source === form) {
            form.reset();

            var panel = byId('new-entry');
            if (panel) panel.open = false;

            var statusEl = byId('ai-help-status');
            if (statusEl) statusEl.textContent = '';

            var readBox = byId('ai-read-file-box');
            if (readBox) readBox.classList.add('hidden');

            setTimeout(onDate, 0);
        }

        if (source && source.closest && source.closest('#edit-modal')) closeEditModal();
    });

    function recheck(event) {
        if (form && event.target === form) setTimeout(onDate, 0);
    }

    document.addEventListener('async:invalid', recheck);
    document.addEventListener('async:error', recheck);

    // AI Help
    function updateMeter(left) {
        var leftEl = byId('ai-left');
        var bar = byId('ai-bar');
        if (!leftEl || !bar || isNaN(left)) return;

        var limit = parseInt(leftEl.getAttribute('data-limit'), 10) || 10;
        left = Math.max(0, Math.min(limit, left));
        var color = left <= Math.ceil(limit * 0.2) ? '#C2410C' : '#6D28D9';

        leftEl.textContent = left;
        leftEl.style.color = color;
        bar.style.width = Math.round(left / limit * 100) + '%';
        bar.style.background = color;
    }

    var AI_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    var AI_MAX_BYTES = 1900000;

    // Photos are shrunk in the browser so they fit the server's upload limit.
    function shrinkForAi(file) {
        return new Promise(function (resolve) {
            if (file.type === 'application/pdf' || !window.URL) { resolve(file); return; }

            var image = new Image();
            var url = URL.createObjectURL(file);

            image.onload = function () {
                URL.revokeObjectURL(url);

                var scale = Math.min(1, 1280 / Math.max(image.width, image.height));
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(image.width * scale);
                canvas.height = Math.round(image.height * scale);
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);

                canvas.toBlob(function (blob) {
                    resolve(blob ? new File([blob], 'photo.jpg', { type: 'image/jpeg' }) : file);
                }, 'image/jpeg', 0.8);
            };

            image.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
            image.src = url;
        });
    }

    function releaseLogbookAttachmentPreviews(input) {
        (input.__logbookPendingAttachments || []).forEach(function (item) {
            if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
        });
        input.__logbookPendingAttachments = [];
    }

    function renderLogbookAttachmentPreviews(input) {
        var form = input.closest('form');
        var section = form && form.querySelector('[data-logbook-attachment-preview-section]');
        var previewList = form && form.querySelector('[data-logbook-attachment-previews]');
        var error = form && form.querySelector('[data-logbook-attachment-error]');
        var pending = input.__logbookPendingAttachments || [];

        if (!section || !previewList) return;

        section.classList.toggle('hidden', pending.length === 0);
        if (error) {
            error.textContent = '';
            error.classList.add('hidden');
        }

        previewList.innerHTML = pending.map(function (item, index) {
            var fileName = popupEscape(item.file.name);
            var preview = item.previewUrl
                ? '<button type="button" data-logbook-image-preview="' + popupEscape(item.previewUrl) +
                    '" data-logbook-image-name="' + fileName + '" aria-label="Preview ' + fileName +
                    '" class="block h-full w-full cursor-zoom-in bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500"><img src="' +
                    popupEscape(item.previewUrl) + '" alt="Selected image: ' + fileName +
                    '" class="h-full w-full object-contain p-1"></button>'
                : '<div class="flex h-full flex-col items-center justify-center gap-2 bg-slate-50 p-2 text-slate-500"><i class="fa-solid ' +
                    (item.file.type === 'application/pdf' ? 'fa-file-pdf text-rose-500' : 'fa-file-lines text-blue-600') +
                    ' text-2xl"></i><span class="max-w-full truncate text-xs font-semibold">' + fileName + '</span></div>';

            return '<div class="relative h-32 w-44 overflow-hidden rounded-lg border border-blue-200 bg-white ring-1 ring-blue-50 sm:h-36 sm:w-48">' +
                preview +
                '<span class="absolute inset-x-0 bottom-0 truncate bg-slate-900/65 px-1.5 py-1 text-[10px] text-white">' + fileName + '</span>' +
                '<span class="absolute left-1 top-1 rounded bg-blue-600 px-1.5 py-0.5 text-[9px] font-bold text-white">Pending</span>' +
                '<button type="button" data-remove-logbook-attachment="' + index + '" aria-label="Remove ' + fileName +
                ' from pending uploads" title="Remove file" class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white/80 text-[9px] text-slate-500 shadow-sm transition hover:bg-white hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>' +
                '</div>';
        }).join('');
    }

    var logbookImageReturnFocus = null;
    var logbookImageBodyWasLocked = false;

    function closeLogbookImagePreview() {
        var lightbox = byId('logbook-image-lightbox');
        if (!lightbox || lightbox.classList.contains('hidden')) return;

        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        lightbox.querySelector('[data-logbook-lightbox-image]').src = '';
        lightbox.querySelector('[data-logbook-lightbox-caption]').textContent = '';
        if (!logbookImageBodyWasLocked) document.body.classList.remove('overflow-hidden');
        if (logbookImageReturnFocus && document.body.contains(logbookImageReturnFocus)) logbookImageReturnFocus.focus();
        logbookImageReturnFocus = null;
    }

    function openLogbookImagePreview(button) {
        var lightbox = byId('logbook-image-lightbox');
        if (!lightbox) return;

        lightbox.querySelector('[data-logbook-lightbox-image]').src = button.getAttribute('data-logbook-image-preview');
        lightbox.querySelector('[data-logbook-lightbox-image]').alt = button.getAttribute('data-logbook-image-name');
        lightbox.querySelector('[data-logbook-lightbox-caption]').textContent = button.getAttribute('data-logbook-image-name');
        logbookImageReturnFocus = button;
        logbookImageBodyWasLocked = document.body.classList.contains('overflow-hidden');
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        lightbox.focus();
    }

    document.addEventListener('click', function (event) {
        var preview = event.target.closest ? event.target.closest('[data-logbook-image-preview]') : null;
        if (preview) {
            event.preventDefault();
            openLogbookImagePreview(preview);
            return;
        }

        var lightbox = byId('logbook-image-lightbox');
        if (lightbox && !lightbox.classList.contains('hidden') &&
            (event.target === lightbox || (event.target.closest && event.target.closest('[data-logbook-image-close]')))) {
            closeLogbookImagePreview();
        }
    });

    document.addEventListener('keydown', function (event) {
        var lightbox = byId('logbook-image-lightbox');
        if (event.key === 'Escape' && lightbox && !lightbox.classList.contains('hidden')) {
            closeLogbookImagePreview();
        }
    });

    document.addEventListener('change', function (event) {
        var input = event.target.closest ? event.target.closest('[data-logbook-attachment-input]') : null;
        if (!input) return;

        releaseLogbookAttachmentPreviews(input);
        input.__logbookPendingAttachments = Array.prototype.slice.call(input.files || []).map(function (file) {
            return {
                file: file,
                previewUrl: file.type.indexOf('image/') === 0 && window.URL && URL.createObjectURL
                    ? URL.createObjectURL(file)
                    : null
            };
        });
        renderLogbookAttachmentPreviews(input);
    });

    document.addEventListener('async:success', function (event) {
        var source = event.target;
        if (!source || !source.querySelectorAll) return;

        source.querySelectorAll('[data-logbook-attachment-input]').forEach(function (input) {
            releaseLogbookAttachmentPreviews(input);
            renderLogbookAttachmentPreviews(input);
        });
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('[data-remove-logbook-attachment]') : null;
        if (!button) return;

        event.preventDefault();
        var form = button.closest('form');
        var input = form && form.querySelector('[data-logbook-attachment-input]');
        if (!input) return;

        var pending = input.__logbookPendingAttachments || [];
        var index = parseInt(button.getAttribute('data-remove-logbook-attachment'), 10);
        if (!pending[index]) return;

        if (pending.length > 1) {
            if (typeof DataTransfer === 'undefined') {
                var error = form.querySelector('[data-logbook-attachment-error]');
                if (error) {
                    error.textContent = 'Your browser cannot remove one file from the selection. Please use a recent browser.';
                    error.classList.remove('hidden');
                }
                return;
            }

            var transfer = new DataTransfer();
            pending.forEach(function (item, itemIndex) {
                if (itemIndex !== index) transfer.items.add(item.file);
            });
            input.files = transfer.files;
        } else {
            input.value = '';
        }

        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    document.addEventListener('change', function (event) {
        var fileInput = event.target.closest ? event.target.closest('[data-ai-file]') : null;
        if (!fileInput) return;

        var aiForm = fileInput.closest('form');
        var readBox = aiForm && aiForm.querySelector('[data-ai-read-file-box]');
        var readCheck = aiForm && aiForm.querySelector('[data-ai-read-file]');
        if (!readBox) return;

        var file = fileInput.files && fileInput.files[0];
        var usable = !!file && AI_TYPES.indexOf(file.type) !== -1;
        readBox.classList.toggle('hidden', !usable);
        if (!usable && readCheck) readCheck.checked = false;
    });

    document.addEventListener('click', async function (event) {
        var btn = event.target.closest ? event.target.closest('[data-ai-help-button]') : null;
        if (!btn) return;

        event.preventDefault();
        if (btn.dataset.busy || btn.disabled) return;

        var aiForm = btn.closest('form');
        var field = aiForm && aiForm.querySelector('[data-ai-description]');
        var status = aiForm && aiForm.querySelector('[data-ai-help-status]');
        var label = btn.querySelector('[data-ai-help-label]');
        var fileInput = aiForm && aiForm.querySelector('[data-ai-file]');
        var readCheck = aiForm && aiForm.querySelector('[data-ai-read-file]');
        var roleInput = aiForm && aiForm.querySelector('[data-ai-role]');
        var chosenFile = readCheck && readCheck.checked && fileInput && fileInput.files
            ? fileInput.files[0]
            : null;
        var chosen = chosenFile && AI_TYPES.indexOf(chosenFile.type) !== -1 ? chosenFile : null;

        if (!field || !status || !label) return;

        var setStatus = function (text, color) {
            status.textContent = text;
            status.style.color = color;
        };

        if (!field.value.trim() && !chosen) {
            setStatus('Type a few words about what you did, or select an image/PDF and let AI Help read it.', '#C2410C');
            field.focus();
            return;
        }

        btn.dataset.busy = '1';
        btn.disabled = true;
        label.textContent = 'Working...';
        setStatus(chosen ? 'Reading your file and writing a draft...' : 'Writing a draft...', '#5B6478');

        try {
            var weekInput = aiForm.querySelector('[name="week_no"]');
            var dateInput = aiForm.querySelector('[name="activity_date"]');
            var body = new FormData();
            body.append('week_no', weekInput ? weekInput.value : '');
            body.append('activity_date', dateInput ? dateInput.value : '');
            body.append('description', field.value);
            body.append('role', roleInput ? roleInput.value : '');

            if (chosen) {
                var prepared = await shrinkForAi(chosen);

                if (prepared.size > AI_MAX_BYTES) {
                    throw new Error('This file is too large for AI Help (limit about 2 MB). Try a smaller file or a screenshot.');
                }

                body.append('use_file', '1');
                body.append('attachment', prepared, prepared.name || 'file');
            }

            var tokenInput = aiForm.querySelector('input[name="_token"]');
            var response = await fetch('/student/logbook/ai-help', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': tokenInput ? tokenInput.value : '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: body
            });

            var remaining = response.headers.get('X-AI-Remaining');
            if (remaining !== null) updateMeter(parseInt(remaining, 10));

            var data = null;
            try { data = await response.json(); } catch (parseError) { data = null; }

            if (response.status === 429) {
                throw new Error((data && data.message) || 'Too many requests. Please wait a moment and try again.');
            }
            if (!response.ok) {
                throw new Error((data && data.message) || ('HTTP ' + response.status));
            }
            if (!data || !data.text) {
                throw new Error('No text field in response');
            }

            field.value = data.text;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.focus();
            setStatus(data.message || 'Draft inserted. Please check it.', data.fallback ? '#C2410C' : '#10b981');
        } catch (error) {
            setStatus('Error: ' + (error.message || 'Unable to generate text.'), '#ef4444');
        } finally {
            delete btn.dataset.busy;
            var left = COOLDOWN_SECONDS;
            label.textContent = 'Wait ' + left + 's';
            var timer = setInterval(function () {
                left -= 1;

                if (left <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    label.textContent = 'AI Help';
                } else {
                    label.textContent = 'Wait ' + left + 's';
                }
            }, 1000);
        }
    });

    document.addEventListener('submit', function (event) {
        var editForm = event.target.closest ? event.target.closest('form[data-edit-logbook-form]') : null;
        if (!editForm) return;

        var field = editForm.querySelector('[data-edit-description]');
        var error = editForm.querySelector('[data-edit-description-error]');
        if (!field || !error) return;

        var length = field.value.trim().length;
        var message = length === 0
            ? 'No description written. Please describe what you did before saving.'
            : (length < 10
                ? 'Your description is too short (' + length + '/10 characters). Please write at least 10 characters.'
                : '');

        error.textContent = message;
        error.classList.toggle('hidden', !message);

        if (message) {
            event.preventDefault();
            event.stopImmediatePropagation();
            field.focus();
        }
    }, true);

    document.addEventListener('input', function (event) {
        var field = event.target.closest ? event.target.closest('[data-edit-description]') : null;
        if (!field) return;

        var error = field.closest('form').querySelector('[data-edit-description-error]');
        if (error && field.value.trim().length >= 10) {
            error.textContent = '';
            error.classList.add('hidden');
        }
    });

    document.addEventListener('click', async function (event) {
        var button = event.target.closest ? event.target.closest('[data-delete-attachment]') : null;
        if (!button || button.disabled) return;

        event.preventDefault();
        if (!window.confirm('Remove this attachment from the logbook entry?')) return;

        button.disabled = true;

        try {
            var form = button.closest('form');
            var token = form && form.querySelector('input[name="_token"]');
            var response = await fetch(button.dataset.deleteAttachment, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token ? token.value : '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            var data = {};
            try { data = await response.json(); } catch (error) {}

            if (!response.ok) {
                throw new Error(data.message || ('HTTP ' + response.status));
            }

            var row = button.closest('[data-attachment-row]');
            if (row) {
                var list = row.closest('[data-attachment-list]');
                row.remove();
                if (list && !list.querySelector('[data-attachment-row]')) {
                    list.classList.add('hidden');
                    var empty = list.parentElement.querySelector('[data-no-attachments]');
                    if (empty) empty.classList.remove('hidden');
                }
            }
            if (window.asyncToast) window.asyncToast(data.message || 'Attachment removed.', 'success');
        } catch (error) {
            if (window.asyncToast) window.asyncToast('Edit failed: ' + (error.message || 'The attachment could not be deleted.'), 'error');
        } finally {
            if (document.body.contains(button)) button.disabled = false;
        }
    });

    // Calendar and to-do card.
    // Click a day: the day tab (right side) shows that day. Other tabs filter the task list.
    var calSelected = null;
    var todoTab = 'all';          // 'day' | 'all' | 'overdue' | 'upcoming'

    function applyCalendar(key) {
        var card = byId('calendar-card');
        if (!card) return;

        // The day panels live in the to-do card, so search the whole page.
        var panels = document.querySelectorAll('[data-cal-panel]');
        var found = false;

        panels.forEach(function (panel) {
            if (panel.getAttribute('data-cal-panel') === key) found = true;
        });

        if (!found) key = card.getAttribute('data-today');
        calSelected = key;

        panels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-cal-panel') !== key);
        });

        card.querySelectorAll('[data-cal-day]').forEach(function (button) {
            var on = button.getAttribute('data-cal-day') === key;
            button.classList.toggle('is-sel', on);
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        // Keep the day tab label and count in step with the selected day.
        var active = document.querySelector('[data-cal-panel="' + key + '"]');
        var label = document.querySelector('[data-day-label]');
        var count = document.querySelector('[data-day-count]');

        if (active && label) label.textContent = active.getAttribute('data-cal-short');
        if (active && count) count.textContent = active.getAttribute('data-cal-count');
    }

    function applyTodoTab(tab) {
        var card = byId('todo-list');
        if (!card) return;

        if (tab) todoTab = tab;

        var isDay = todoTab === 'day';
        var filter = isDay ? 'all' : todoTab;
        var shown = 0;

        // Day tab shows the day panels, every other tab shows the task list.
        card.querySelectorAll('[data-todo-view]').forEach(function (view) {
            var isDayView = view.getAttribute('data-todo-view') === 'day';
            view.classList.toggle('hidden', isDayView !== isDay);
        });

        card.querySelectorAll('[data-todo-row]').forEach(function (row) {
            var show = filter === 'all' || row.getAttribute('data-state') === filter;
            row.classList.toggle('hidden', !show);
            if (show) shown++;
        });

        var empty = card.querySelector('[data-todo-empty]');
        if (empty) empty.classList.toggle('hidden', shown > 0);

        card.querySelectorAll('[data-todo-tab]').forEach(function (button) {
            var on = button.getAttribute('data-todo-tab') === todoTab;
            button.classList.toggle('bg-white', on);
            button.classList.toggle('text-[#1E293B]', on);
            button.classList.toggle('shadow-sm', on);
            button.classList.toggle('text-[#5B6478]', !on);
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    var dashboardDayPopup = null;
    var popupReturnFocus = null;
    var popupBodyWasLocked = false;

    function popupEscape(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[character];
        });
    }

    function closeDashboardDay() {
        dashboardDayPopup = byId('dashboard-calendar-popup');
        if (!dashboardDayPopup || dashboardDayPopup.classList.contains('hidden')) return;

        dashboardDayPopup.classList.add('hidden');
        dashboardDayPopup.classList.remove('flex');
        if (!popupBodyWasLocked) document.body.classList.remove('overflow-hidden');
        if (popupReturnFocus && document.body.contains(popupReturnFocus)) popupReturnFocus.focus();
        popupReturnFocus = null;
    }

    function openDashboardDay(details, trigger) {
        dashboardDayPopup = byId('dashboard-calendar-popup');
        if (!dashboardDayPopup || !details) return;

        var title = dashboardDayPopup.querySelector('[data-popup-title]');
        var subtitle = dashboardDayPopup.querySelector('[data-popup-subtitle]');
        var summary = details.summary || {};
        var attendance = details.attendance;
        var note = details.note;
        var tasks = details.tasks || [];
        var logs = details.logs || [];

        title.textContent = details.label || 'Day details';
        subtitle.textContent = (details.weekday || '') + (details.week ? ' · Week ' + details.week : '');
        dashboardDayPopup.querySelector('[data-popup-logs]').textContent = summary.logs || 0;
        dashboardDayPopup.querySelector('[data-popup-attendance-count]').textContent = summary.attendance || 0;
        dashboardDayPopup.querySelector('[data-popup-leave]').textContent = summary.leave || 0;
        dashboardDayPopup.querySelector('[data-popup-tasks-count]').textContent = summary.tasks || 0;

        var map = dashboardDayPopup.querySelector('[data-popup-map]');
        var mapLink = dashboardDayPopup.querySelector('[data-popup-map-link]');
        var attendanceStatus = dashboardDayPopup.querySelector('[data-popup-attendance-status]');
        var checkIn = dashboardDayPopup.querySelector('[data-popup-check-in]');
        var checkOut = dashboardDayPopup.querySelector('[data-popup-check-out]');
        var workMode = dashboardDayPopup.querySelector('[data-popup-work-mode]');
        var locationName = dashboardDayPopup.querySelector('[data-popup-location-name]');
        attendanceStatus.classList.remove('bg-emerald-100', 'text-emerald-700', 'bg-amber-100', 'text-amber-700', 'bg-slate-100', 'text-slate-700');
        if (attendance) {
            attendanceStatus.textContent = attendance.status || 'Attendance recorded';
            if (attendance.status === 'Present') {
                attendanceStatus.classList.add('bg-emerald-100', 'text-emerald-700');
            } else {
                attendanceStatus.classList.add('bg-amber-100', 'text-amber-700');
            }
            checkIn.textContent = attendance.check_in || '--:--';
            checkOut.textContent = attendance.check_out || '--:--';
            workMode.innerHTML = attendance.work_mode
                ? '<i class="fa-solid ' + (attendance.work_mode.toLowerCase().indexOf('home') !== -1 ? 'fa-house' : 'fa-building') +
                    ' mr-1"></i>' + popupEscape(attendance.work_mode)
                : '';
            workMode.classList.toggle('hidden', !attendance.work_mode);
            locationName.textContent = attendance.location_name
                || (attendance.location_status === 'shared' ? 'Location name unavailable' : 'Location was not shared');
            if (attendance.map_embed) {
                map.src = attendance.map_embed;
                map.classList.remove('hidden');
            } else {
                map.removeAttribute('src');
                map.classList.add('hidden');
            }
            if (attendance.map_link) {
                mapLink.href = attendance.map_link;
                mapLink.classList.remove('hidden');
            } else {
                mapLink.removeAttribute('href');
                mapLink.classList.add('hidden');
            }
        } else {
            attendanceStatus.textContent = 'No attendance';
            attendanceStatus.classList.add('bg-slate-100', 'text-slate-700');
            checkIn.textContent = '--:--';
            checkOut.textContent = '--:--';
            workMode.innerHTML = '';
            workMode.classList.add('hidden');
            locationName.textContent = 'No location recorded for this day.';
            map.removeAttribute('src');
            map.classList.add('hidden');
            mapLink.removeAttribute('href');
            mapLink.classList.add('hidden');
        }

        var noteText = dashboardDayPopup.querySelector('[data-popup-note]');
        var noteImages = dashboardDayPopup.querySelector('[data-popup-note-images]');
        noteText.textContent = note && note.body ? note.body : 'No daily note saved for this day.';
        noteImages.innerHTML = note && note.images && note.images.length
            ? note.images.map(function (image) {
                return '<img src="' + popupEscape(image.url) + '" alt="' + popupEscape(image.name) + '" loading="lazy" class="h-14 w-full rounded-lg object-cover">';
            }).join('')
            : '';

        var completedTasks = tasks.filter(function (task) { return task.done; }).length;
        dashboardDayPopup.querySelector('[data-popup-task-progress]').textContent = completedTasks + ' / ' + tasks.length + ' done';
        dashboardDayPopup.querySelector('[data-popup-tasks]').innerHTML = tasks.length
            ? tasks.map(function (task) {
                return '<div class="flex items-center gap-2 rounded-lg bg-white px-2 py-1.5 text-xs ' +
                    (task.done ? 'text-slate-400 line-through' : 'text-[#334155]') +
                    '"><i class="fa-solid ' + (task.done ? 'fa-square-check text-blue-600' : 'fa-square text-slate-300') +
                    '"></i><span class="min-w-0 flex-1 truncate">' + popupEscape(task.title) + '</span>' +
                    (task.done ? '<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 no-underline">Completed</span>' : '') +
                    '</div>';
            }).join('')
            : '<p class="text-xs text-[#64748B]">No tasks scheduled for this day.</p>';

        var logbookList = dashboardDayPopup.querySelector('[data-popup-logbook]');
        var logbookLink = dashboardDayPopup.querySelector('[data-popup-logbook-link]');
        if (logs.length) {
            logbookLink.href = logs[0].url || '/student/logbooks';
            logbookLink.classList.remove('hidden');
            logbookList.innerHTML = logs.slice(0, 2).map(function (log) {
                var preview = log.thumbnail
                    ? '<img src="' + popupEscape(log.thumbnail) + '" alt="" loading="lazy" class="h-16 w-20 flex-none rounded-xl object-cover">'
                    : '<span class="flex h-16 w-20 flex-none items-center justify-center rounded-xl bg-blue-100 text-blue-600"><i class="fa-solid fa-book-open text-xl"></i></span>';
                return '<a href="' + popupEscape(log.url || '/student/logbooks') + '" class="flex min-w-0 items-center gap-3 rounded-xl border border-[#E8EDF6] bg-white p-2">' +
                    preview + '<span class="min-w-0 flex-1"><span class="block truncate text-xs font-extrabold text-[#1E293B]">' +
                    popupEscape(log.title) + '</span><span class="mt-1 block line-clamp-2 text-[11px] leading-4 text-[#64748B]">' +
                    popupEscape(log.description || 'Logbook entry submitted.') + '</span><span class="mt-1 inline-block rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">' +
                    popupEscape(log.status || 'Submitted') + '</span></span></a>';
            }).join('');
        } else {
            logbookLink.classList.add('hidden');
            logbookLink.removeAttribute('href');
            logbookList.innerHTML = '<p class="rounded-xl bg-white px-3 py-2 text-xs text-[#64748B]">No logbook entry recorded for this day.</p>';
        }

        dashboardDayPopup.querySelector('[data-popup-full-calendar]').href =
            '/student/calendar?month=' + encodeURIComponent((details.date || '').slice(0, 7)) +
            '&selected=' + encodeURIComponent(details.date || '');

        popupReturnFocus = trigger;
        popupBodyWasLocked = document.body.classList.contains('overflow-hidden');
        dashboardDayPopup.classList.remove('hidden');
        dashboardDayPopup.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        dashboardDayPopup.querySelector('[data-popup-close]').focus();
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;

        var day = target.closest('[data-cal-day]');
        if (day) {
            var selectedDate = day.getAttribute('data-cal-day');
            applyCalendar(selectedDate);
            applyTodoTab('day');
            var dayPanel = document.querySelector('[data-cal-panel="' + selectedDate + '"]');
            if (dayPanel) openDashboardDay(JSON.parse(dayPanel.getAttribute('data-day-details') || '{}'), day);
            return;
        }

        var activeDayPopup = byId('dashboard-calendar-popup');
        if (activeDayPopup && !activeDayPopup.classList.contains('hidden')) {
            if (target === activeDayPopup || target.closest('[data-popup-close]')) {
                closeDashboardDay();
                return;
            }
        }

        var tab = target.closest('[data-todo-tab]');
        if (tab) applyTodoTab(tab.getAttribute('data-todo-tab'));
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeDashboardDay();
    });

    document.addEventListener('async:swapped', function () {
        applyCalendar(calSelected);
        applyTodoTab();
    });

    var dashboardFoldMedia = window.matchMedia('(max-width: 767px)');

    function syncDashboardFolds() {
        var mobile = dashboardFoldMedia.matches;
        document.querySelectorAll('details[data-mobile-fold]').forEach(function (section) {
            section.open = !mobile;
        });
    }

    syncDashboardFolds();
    if (dashboardFoldMedia.addEventListener) {
        dashboardFoldMedia.addEventListener('change', syncDashboardFolds);
    } else {
        dashboardFoldMedia.addListener(syncDashboardFolds);
    }
    document.addEventListener('page:loaded', syncDashboardFolds);

    document.addEventListener('click', function (event) {
        var target = event.target;
        var taskLink = target && target.closest ? target.closest('a[href="#todo-list"]') : null;
        if (!taskLink) return;

        var planner = document.querySelector('details[data-mobile-fold="planner"]');
        if (planner) planner.open = true;
    }, true);

    applyCalendar(null);
    applyTodoTab();

    // Working days: submit asynchronously, then refresh only the current week section.
    var scheduleTimer = null;
    var scheduleBusy = false;

    function scheduleDays(form) {
        return Array.prototype.slice.call(form.querySelectorAll('[data-working-day][aria-pressed="true"]'))
            .map(function (button) { return button.getAttribute('data-working-day'); })
            .sort(function (a, b) { return Number(a) - Number(b); });
    }

    function syncSchedule(form) {
        form = form || byId('schedule-bar');
        if (!form) return;

        var saved = (form.getAttribute('data-saved') || '').split(',').filter(Boolean).sort().join(',');
        var picked = scheduleDays(form);
        var current = picked.join(',');
        var save = form.querySelector('#schedule-save');
        var count = form.querySelector('#schedule-count');

        if (save) save.disabled = scheduleBusy || picked.length === 0 || current === saved;
        if (count) {
            count.textContent = picked.length === 0
                ? 'Pick at least one day'
                : picked.length + (picked.length === 1 ? ' day a week' : ' days a week') + (current === saved ? '' : ' (saving...)');
            count.classList.toggle('text-[#BE123C]', picked.length === 0);
        }
    }

    function scheduleMessage(form, text) {
        var message = form.querySelector('#schedule-message');
        if (!message) return;
        message.textContent = text;
        message.classList.toggle('hidden', !text);
    }

    function restoreSavedDays(form) {
        var saved = (form.getAttribute('data-saved') || '').split(',').filter(Boolean);
        form.querySelectorAll('[data-working-day]').forEach(function (button) {
            var selected = saved.indexOf(button.getAttribute('data-working-day')) !== -1;
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            button.classList.toggle('border-[#2F5BFF]', selected);
            button.classList.toggle('bg-[#E8EFFF]', selected);
            button.classList.toggle('text-[#1F45D6]', selected);
            button.classList.toggle('border-[#CBD3E3]', !selected);
            button.classList.toggle('bg-white', !selected);
            button.classList.toggle('text-[#334155]', !selected);
        });
    }

    function setScheduleDisabled(form, disabled) {
        form.querySelectorAll('[data-working-day], #schedule-save').forEach(function (control) {
            control.disabled = disabled;
        });
    }

    async function saveSchedule(form) {
        if (scheduleBusy || !form) return;
        clearTimeout(scheduleTimer);

        var picked = scheduleDays(form);
        if (!picked.length) {
            syncSchedule(form);
            scheduleMessage(form, 'Choose at least one working day.');
            return;
        }

        scheduleBusy = true;
        scheduleMessage(form, '');
        setScheduleDisabled(form, true);

        try {
            var tokenInput = form.querySelector('input[name="_token"]');
            var body = new FormData(form);
            picked.forEach(function (day) { body.append('working_days[]', day); });
            var response = await fetch(form.action, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': tokenInput ? tokenInput.value : ''
                }
            });
            var data = {};
            try { data = await response.json(); } catch (e) {}

            if (!response.ok) throw new Error((data && data.message) || ('Could not save (error ' + response.status + ')'));

            form.setAttribute('data-saved', picked.join(','));

            try {
                var page = await fetch(window.location.href, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!page.ok) throw new Error('Week refresh failed.');

                var doc = new DOMParser().parseFromString(await page.text(), 'text/html');
                var freshSection = doc.getElementById('logbook-week');
                var currentSection = byId('logbook-week');
                if (!freshSection || !currentSection) throw new Error('Week section was not found.');

                currentSection.innerHTML = freshSection.innerHTML;
                document.dispatchEvent(new Event('async:swapped'));
            } catch (refreshError) {
                window.location.reload();
            }
        } catch (error) {
            var currentForm = byId('schedule-bar');
            if (currentForm) {
                restoreSavedDays(currentForm);
                scheduleMessage(currentForm, error.message || 'Could not save your working days.');
            }
        } finally {
            scheduleBusy = false;
            var currentForm = byId('schedule-bar');
            if (currentForm) {
                setScheduleDisabled(currentForm, false);
                syncSchedule(currentForm);
            }
        }
    }

    document.addEventListener('click', function (event) {
        var dayButton = event.target && event.target.closest ? event.target.closest('#schedule-bar [data-working-day]') : null;
        if (dayButton) {
            event.preventDefault();
            if (scheduleBusy || dayButton.disabled) return;

            var selected = dayButton.getAttribute('aria-pressed') !== 'true';
            dayButton.setAttribute('aria-pressed', selected ? 'true' : 'false');
            dayButton.classList.toggle('border-[#2F5BFF]', selected);
            dayButton.classList.toggle('bg-[#E8EFFF]', selected);
            dayButton.classList.toggle('text-[#1F45D6]', selected);
            dayButton.classList.toggle('border-[#CBD3E3]', !selected);
            dayButton.classList.toggle('bg-white', !selected);
            dayButton.classList.toggle('text-[#334155]', !selected);

            var dayForm = byId('schedule-bar');
            syncSchedule(dayForm);
            scheduleMessage(dayForm, '');
            clearTimeout(scheduleTimer);
            scheduleTimer = setTimeout(function () { saveSchedule(byId('schedule-bar')); }, 250);
            return;
        }

        var saveButton = event.target && event.target.closest ? event.target.closest('#schedule-save') : null;
        if (saveButton) {
            event.preventDefault();
            saveSchedule(byId('schedule-bar'));
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.id !== 'schedule-bar') return;

        event.preventDefault();
        event.stopImmediatePropagation();
        saveSchedule(form);
    }, true);

    document.addEventListener('async:swapped', function () { syncSchedule(); });
    syncSchedule();
})();

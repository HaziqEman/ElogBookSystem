
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

    // // Reminder banner: hidden for the rest of the day when closed.
    // function applyBannerDismiss() {
    //     var banner = byId('reminder-banner');
    //     if (!banner) return;

    //     try {
    //         if (localStorage.getItem(banner.getAttribute('data-dismiss-key'))) banner.classList.add('hidden');
    //     } catch (e) {}
    // }

    // applyBannerDismiss();
    // document.addEventListener('async:swapped', applyBannerDismiss);

    // New entry form: week number and day checks.
   
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

            // var weekday = new Date(utc(value)).getUTCDay();
            // var iso = weekday === 0 ? 7 : weekday;
            // if (workingDays.indexOf(iso) === -1) {
            //     return 'That day is not one of your working days. Your administrator sets your working days.';
            // }
        
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
            window.location.assign(href);
            return;
        }

        try {
            var response = await fetch(href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                credentials: 'same-origin'
            });

            if (response.redirected) {
                window.location.assign(href);
                return;
            }

            if (!response.ok) {
                var message = 'This entry cannot be edited.';

                try { message = (await response.json()).message || message; } catch (e) {}
                if (window.asyncToast) window.asyncToast(message, 'error');

                return;
            }

            modalBody.innerHTML = await response.text();
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            var first = modalBody.querySelector('textarea, input:not([type="hidden"])');
            if (first) first.focus();
        } catch (e) {
            window.location.assign(href);
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;

        // var close = target.closest('#reminder-close');

        // if (close) {
        //     var banner = byId('reminder-banner');

        //     if (banner) {
        //         banner.classList.add('hidden');
        //         try { localStorage.setItem(banner.getAttribute('data-dismiss-key'), '1'); } catch (e) {}
        //     }

        //     return;
        // }

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
        if (event.key === 'Escape') closeEditModal();
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

    document.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('[data-carousel-step]') : null;
        if (!button) return;

        var carousel = button.closest('[data-attachment-carousel]');
        if (!carousel) return;

        var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-carousel-slide]'));
        if (slides.length < 2) return;

        var current = slides.findIndex(function (slide) { return !slide.classList.contains('hidden'); });
        if (current < 0) current = 0;

        var step = parseInt(button.dataset.carouselStep, 10) || 1;
        var next = (current + step + slides.length) % slides.length;
        slides.forEach(function (slide, index) {
            slide.classList.toggle('hidden', index !== next);
        });

        var counter = carousel.querySelector('[data-carousel-count]');
        if (counter) counter.textContent = (next + 1) + ' / ' + slides.length;
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

    // Calendar: click a day to see its items. Todo tabs: filter the list.
    var calSelected = null;
    var todoTab = 'all';

    function applyCalendar(key) {
        var card = byId('calendar-card');
        if (!card) return;

        var panels = card.querySelectorAll('[data-cal-panel]');
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
    }

    function applyTodoTab(tab) {
        var card = byId('todo-list');
        if (!card) return;

        if (tab) todoTab = tab;
        var shown = 0;

        card.querySelectorAll('[data-todo-row]').forEach(function (row) {
            var show = todoTab === 'all' || row.getAttribute('data-state') === todoTab;
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

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;

        var day = target.closest('[data-cal-day]');
        if (day) { applyCalendar(day.getAttribute('data-cal-day')); return; }

        var tab = target.closest('[data-todo-tab]');
        if (tab) applyTodoTab(tab.getAttribute('data-todo-tab'));
    });

    document.addEventListener('async:swapped', function () {
        applyCalendar(calSelected);
        applyTodoTab();
    });

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

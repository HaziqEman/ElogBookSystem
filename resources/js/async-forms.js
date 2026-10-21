// Forms with a data-async attribute submit without reloading the page.
// The server may answer with: { message, target, html }
//   target + html : the element with that id is replaced by the new HTML
//   message       : shown as a small pop-up

function toast(message, type) {
    if (!message) return;

    var box = document.getElementById('async-toasts');

    if (!box) {
        box = document.createElement('div');
        box.id = 'async-toasts';
        box.className = 'fixed right-4 top-4 z-[100] flex max-w-sm flex-col gap-2';
        document.body.appendChild(box);
    }

    var item = document.createElement('div');
    item.setAttribute('role', 'status');
    item.className = 'rounded-xl border px-4 py-3 text-sm font-semibold shadow-lg '
        + (type === 'error'
            ? 'border-rose-200 bg-rose-50 text-rose-700'
            : 'border-emerald-200 bg-emerald-50 text-emerald-700');
    item.textContent = message;
    box.appendChild(item);

    setTimeout(function () { item.remove(); }, 4000);
}

function swapFromHtml(id, html) {
    var current = document.getElementById(id);
    if (!current || !html) return false;

    var next = new DOMParser().parseFromString(html, 'text/html').getElementById(id);
    if (!next) return false;

    current.replaceWith(next);
    return true;
}

document.addEventListener('submit', async function (event) {
    if (event.defaultPrevented) return;                      // a confirm() was cancelled

    var form = event.target.closest ? event.target.closest('form[data-async]') : null;
    if (!form) return;

    event.preventDefault();

    if (form.dataset.busy) return;
    form.dataset.busy = '1';

    var button = form.querySelector('[type="submit"]');
    if (button) button.disabled = true;

    try {
        var response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });

        // This action is not converted to JSON on the server yet: behave like a normal form.
        if (response.redirected) {
            window.location.assign(response.url);
            return;
        }

        var data = {};
        try { data = await response.json(); } catch (e) { data = {}; }

        if (response.status === 401 || response.status === 419) {
            throw new Error('Your session expired. Please reload the page.');
        }

        if (response.status === 422) {
            form.dispatchEvent(new CustomEvent('async:invalid', { bubbles: true, detail: data }));
            return;
        }

        if (!response.ok) {
            throw new Error(data.message || 'Something went wrong. Please try again.');
        }

        form.dispatchEvent(new CustomEvent('async:success', { bubbles: true, detail: data }));
    } catch (error) {
        form.dispatchEvent(new CustomEvent('async:error', { bubbles: true, detail: { message: error.message } }));
    } finally {
        delete form.dataset.busy;
        if (button && document.body.contains(button)) button.disabled = false;
    }
});

document.addEventListener('async:success', function (event) {
    var detail = event.detail || {};

    if (detail.target && detail.html) swapFromHtml(detail.target, detail.html);
    if (detail.message) toast(detail.message, 'success');
});

document.addEventListener('async:invalid', function (event) {
    var detail = event.detail || {};
    var errors = detail.errors ? Object.values(detail.errors).flat() : [];

    toast(errors[0] || detail.message || 'Please check what you entered.', 'error');
});

document.addEventListener('async:error', function (event) {
    toast(event.detail && event.detail.message, 'error');
});
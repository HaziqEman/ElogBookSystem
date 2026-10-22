// Forms with a data-async attribute submit without reloading the page.
// After a successful action, every element with data-live is refreshed from the server.
window.__asyncForms = true;

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

    setTimeout(function () { item.remove(); }, 4500);
}

window.asyncToast = toast;

var liveToken = 0;
var liveNavigated = false;

// Fetch the page again and swap every [data-live] element that has an id.
async function refreshLive(url) {
    var target = url || window.location.href;
    var token = ++liveToken;

    try {
        var response = await fetch(target, {
            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });

        if (!response.ok || token !== liveToken) return false;

        // A redirect to another page (for example the login page) must not be swapped in.
        if (new URL(response.url).pathname !== new URL(target, window.location.href).pathname) return false;

        var doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        var swapped = [];

        document.querySelectorAll('[data-live]').forEach(function (current) {
            if (!current.id) return;

            var next = doc.getElementById(current.id);
            if (!next) return;

            current.replaceWith(next);
            swapped.push(next.id);
        });

        document.dispatchEvent(new CustomEvent('async:swapped', { detail: { ids: swapped } }));

        return true;
    } catch (e) {
        return false;
    }
}

window.refreshLive = refreshLive;

document.addEventListener('submit', async function (event) {
    if (event.defaultPrevented) return;                      // a confirm() or another handler cancelled it

    var form = event.target.closest ? event.target.closest('form[data-async]') : null;
    if (!form) return;

    event.preventDefault();

    if (form.dataset.busy) return;
    form.dataset.busy = '1';

    // The clicked submit button (Validate or Revision, for example) is not part of FormData by itself.
    var submitter = event.submitter || null;
    var body = new FormData(form);

    if (submitter && submitter.name && !body.has(submitter.name)) {
        body.append(submitter.name, submitter.value);
    }

    var buttons = form.querySelectorAll('[type="submit"]');
    buttons.forEach(function (button) { button.disabled = true; });

    try {
        var response = await fetch(form.action, {
            method: 'POST',
            body: body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });

        // This action is not converted to JSON on the server: behave like a normal form.
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

            var errors = data.errors ? Object.values(data.errors).flat() : [];
            toast(errors[0] || data.message || 'Please check what you entered.', 'error');
            return;
        }

        if (!response.ok) {
            throw new Error(data.message || 'Something went wrong. Please try again.');
        }

        form.dispatchEvent(new CustomEvent('async:success', { bubbles: true, detail: data }));
        toast(data.message, 'success');

        if (form.dataset.refresh !== 'false') {
            await refreshLive();
        }
    } catch (error) {
        form.dispatchEvent(new CustomEvent('async:error', { bubbles: true, detail: { message: error.message } }));
        toast(error.message, 'error');
    } finally {
        delete form.dataset.busy;

        buttons.forEach(function (button) {
            if (document.body.contains(button)) button.disabled = false;
        });
    }
});

// Links with data-live-nav change the page contents without a reload (the week chips).
document.addEventListener('click', async function (event) {
    var link = event.target.closest ? event.target.closest('a[data-live-nav]') : null;

    if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;

    event.preventDefault();

    if (await refreshLive(link.href)) {
        window.history.pushState({ live: true }, '', link.href);
        liveNavigated = true;
    } else {
        window.location.assign(link.href);
    }
});

window.addEventListener('popstate', function () {
    if (liveNavigated) refreshLive(window.location.href);
});
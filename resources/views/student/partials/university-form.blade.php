{{-- University Information form, shown inside the Export PDF modal. Needs $student. --}}
@php
    $uniList = \App\Models\University::visibleTo($student->student_id)->orderBy('name')->get();
    $currentUni = $student->university;
    $uniMode = old('mode', 'existing');
    $uniSelected = old('university_id', $student->university_id);
    $uniSelectedModel = $uniList->firstWhere('university_id', (int) $uniSelected);
    $uniInput = 'w-full rounded-xl border border-[#E3E8F2] bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#2F5BFF] focus:outline-none focus:ring-2 focus:ring-[#2F5BFF]/20';
@endphp

<div id="university-form-wrap">
    <form method="POST" action="/student/university" enctype="multipart/form-data" id="uni-form" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="mode" id="uni-mode" value="{{ $uniMode }}">
        <input type="hidden" name="export" value="1">

        {{-- Tabs --}}
        <div class="flex rounded-full bg-[#EEF1F7] p-1" role="tablist">
            <button type="button" data-uni-tab="existing" role="tab" class="flex-1 rounded-full px-4 py-2 text-sm font-semibold transition">Choose existing</button>
            <button type="button" data-uni-tab="new" role="tab" class="flex-1 rounded-full px-4 py-2 text-sm font-semibold transition">Add new</button>
        </div>

        {{-- Choose existing --}}
        <div data-uni-panel="existing" class="space-y-4">
            <label class="block space-y-2">
                <span class="text-sm font-semibold text-slate-600">University</span>
                <select name="university_id" id="uni-select" class="{{ $uniInput }}">
                    <option value="" data-logo="">Select your university</option>
                    @foreach($uniList as $uni)
                        <option value="{{ $uni->university_id }}" data-logo="{{ $uni->logo_url }}" @selected((string) $uniSelected === (string) $uni->university_id)>
                            {{ $uni->name }}{{ $uni->is_verified ? '' : ' (pending approval)' }}
                        </option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[#E3E8F2] bg-[#F7F8FB]">
                    <img id="uni-logo-img" src="{{ $uniSelectedModel->logo_url ?? '' }}" alt="University logo" class="{{ ($uniSelectedModel->logo_url ?? null) ? '' : 'hidden' }} h-full w-full object-contain p-1">
                    <i id="uni-logo-placeholder" class="fa-solid fa-building-columns text-xl text-slate-300 {{ ($uniSelectedModel->logo_url ?? null) ? 'hidden' : '' }}"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">University logo</p>
                    <p class="text-xs text-slate-500">Automatically retrieved from university records</p>
                </div>
            </div>
        </div>

        {{-- Add new --}}
        <div data-uni-panel="new" class="hidden space-y-4">
            <label class="block space-y-2">
                <span class="text-sm font-semibold text-slate-600">University name</span>
                <input type="text" name="name" id="uni-name" value="{{ old('name') }}" maxlength="255" autocomplete="off" placeholder="Enter university name" class="{{ $uniInput }}">
            </label>
            <div id="uni-suggest" class="hidden rounded-xl border border-[#E3E8F2] bg-[#F7F8FB] p-3">
                <p class="mb-2 text-xs font-semibold text-slate-500">Did you mean one of these? Pick it instead of adding a duplicate.</p>
                <div id="uni-suggest-list" class="flex flex-wrap gap-2"></div>
            </div>

            <div class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">University logo</span>
                <label for="uni-logo-file" class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 bg-[#F7F8FB] px-4 py-6 text-center transition hover:border-[#2F5BFF]">
                    <img id="uni-new-preview" src="" alt="" class="mb-2 hidden h-16 w-16 rounded-lg border border-[#E3E8F2] bg-white object-contain p-1">
                    <i id="uni-new-icon" class="fa-solid fa-cloud-arrow-up text-xl text-slate-500"></i>
                    <span id="uni-new-label" class="text-sm font-bold text-slate-800">Upload university logo</span>
                    <span class="text-xs text-slate-500">PNG, JPG or WebP &middot; Maximum 2 MB</span>
                </label>
                <input type="file" name="logo" id="uni-logo-file" accept="image/png,image/jpeg,image/webp" class="sr-only">
                <p id="uni-file-error" class="hidden text-xs font-semibold text-rose-600"></p>
                <p class="text-xs text-slate-500">New universities are shown to other students after an admin approves them.</p>
            </div>
        </div>

        {{-- Internship placement --}}
        <div class="space-y-4 border-t border-[#E3E8F2] pt-5">
            <div>
                <h3 class="text-sm font-bold text-slate-700">Internship placement</h3>
                <p class="mt-1 text-xs text-slate-500">The company or organization where you do your internship.</p>
            </div>
            <label class="block space-y-2">
                <span class="text-sm font-semibold text-slate-600">Organization address</span>
                <textarea name="organization_address" rows="2" maxlength="500" placeholder="e.g. Level 5, Menara ABC, Cyberjaya, Selangor" class="{{ $uniInput }}">{{ old('organization_address', $student->organization_address) }}</textarea>
            </label>
            <label class="block space-y-2">
                <span class="text-sm font-semibold text-slate-600">Department</span>
                <input type="text" name="department" value="{{ old('department', $student->department) }}" maxlength="255" placeholder="e.g. IT Department" class="{{ $uniInput }}">
            </label>
        </div>

        <button type="submit" id="uni-submit" class="w-full rounded-xl bg-[#2F5BFF] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#2449d6] disabled:cursor-not-allowed disabled:opacity-40">
            <i class="fa-solid fa-file-pdf mr-1"></i> Save &amp; Export PDF
        </button>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('uni-form');
    if (!form) { return; }

    var modeInput = document.getElementById('uni-mode');
    var tabs = form.querySelectorAll('[data-uni-tab]');
    var panels = form.querySelectorAll('[data-uni-panel]');
    var select = document.getElementById('uni-select');
    var logoImg = document.getElementById('uni-logo-img');
    var logoPlaceholder = document.getElementById('uni-logo-placeholder');
    var nameInput = document.getElementById('uni-name');
    var fileInput = document.getElementById('uni-logo-file');
    var fileError = document.getElementById('uni-file-error');
    var newPreview = document.getElementById('uni-new-preview');
    var newIcon = document.getElementById('uni-new-icon');
    var newLabel = document.getElementById('uni-new-label');
    var suggestBox = document.getElementById('uni-suggest');
    var suggestList = document.getElementById('uni-suggest-list');
    var submit = document.getElementById('uni-submit');
    var MAX_BYTES = 2 * 1024 * 1024;
    var TYPES = ['image/png', 'image/jpeg', 'image/webp'];
    var fileOk = false;
    var timer = null;
    var previewUrl = null;

    function setMode(mode) {
        modeInput.value = mode;

        tabs.forEach(function (tab) {
            var on = tab.getAttribute('data-uni-tab') === mode;
            tab.className = 'flex-1 rounded-full px-4 py-2 text-sm font-semibold transition ' +
                (on ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600');
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            var show = panel.getAttribute('data-uni-panel') === mode;
            panel.classList.toggle('hidden', !show);
            // Fields in the hidden tab must not be submitted.
            panel.querySelectorAll('input, select').forEach(function (el) { el.disabled = !show; });
        });

        validate();
    }

    function showLogo(url) {
        if (url) {
            logoImg.src = url;
            logoImg.classList.remove('hidden');
            logoPlaceholder.classList.add('hidden');
        } else {
            logoImg.removeAttribute('src');
            logoImg.classList.add('hidden');
            logoPlaceholder.classList.remove('hidden');
        }
    }

    function validate() {
        if (modeInput.value === 'new') {
            submit.disabled = !(nameInput.value.trim().length >= 2 && fileOk);
        } else {
            submit.disabled = false; // existing: an empty choice just keeps the current university
        }
    }

    function resetFile(message) {
        fileInput.value = '';
        fileOk = false;
        if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
        newPreview.classList.add('hidden');
        newIcon.classList.remove('hidden');
        newLabel.textContent = 'Upload university logo';
        fileError.textContent = message || '';
        fileError.classList.toggle('hidden', !message);
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { setMode(tab.getAttribute('data-uni-tab')); });
    });

    select.addEventListener('change', function () {
        var opt = select.options[select.selectedIndex];
        showLogo(opt ? opt.getAttribute('data-logo') : '');
    });

    fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) { resetFile(''); validate(); return; }

        if (TYPES.indexOf(file.type) === -1) {
            resetFile('The logo must be a PNG, JPG or WebP image.');
        } else if (file.size > MAX_BYTES) {
            resetFile('The logo must be 2 MB or smaller.');
        } else {
            fileError.classList.add('hidden');
            fileOk = true;
            if (previewUrl) { URL.revokeObjectURL(previewUrl); }
            previewUrl = URL.createObjectURL(file);
            newPreview.src = previewUrl;
            newPreview.classList.remove('hidden');
            newIcon.classList.add('hidden');
            newLabel.textContent = file.name;
        }
        validate();
    });

    nameInput.addEventListener('input', function () {
        validate();
        clearTimeout(timer);
        var term = nameInput.value.trim();

        if (term.length < 2) { suggestBox.classList.add('hidden'); return; }

        timer = setTimeout(function () {
            fetch('/student/university/search?q=' + encodeURIComponent(term), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(function (res) { return res.ok ? res.json() : []; })
            .then(function (items) {
                suggestList.innerHTML = '';
                if (!items.length) { suggestBox.classList.add('hidden'); return; }

                items.forEach(function (item) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'rounded-full border border-[#E3E8F2] bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-[#2F5BFF] hover:text-[#2F5BFF]';
                    btn.textContent = item.name;
                    btn.addEventListener('click', function () {
                        select.value = String(item.university_id);
                        select.dispatchEvent(new Event('change'));
                        setMode('existing');
                    });
                    suggestList.appendChild(btn);
                });
                suggestBox.classList.remove('hidden');
            })
            .catch(function () { suggestBox.classList.add('hidden'); });
        }, 300);
    });

    form.addEventListener('submit', function () {
        submit.disabled = true;
        submit.textContent = 'Preparing PDF...';
    });

    setMode(modeInput.value === 'new' ? 'new' : 'existing');

    // A failed save redirects back to the dashboard: reopen the modal so the student sees what to fix.
    @if(old('export') && $errors->any())
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof openUniversityModal === 'function') { openUniversityModal(); }
    });
    @endif
})();
</script>

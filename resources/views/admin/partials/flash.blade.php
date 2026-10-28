@if(session('success'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        <i class="fa-solid fa-circle-check mr-1"></i> {{ session('success') }}
    </div>
@endif

@if(session('temp_password'))
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">Temporary password for {{ session('temp_for') }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <code id="temp-password" class="rounded-md border border-amber-300 bg-white px-3 py-1.5 font-mono text-base tracking-wide">{{ session('temp_password') }}</code>
            <button type="button" id="temp-password-copy" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-slate-900 transition hover:bg-amber-600">Copy</button>
        </div>
        <p class="mt-2 text-xs text-amber-800">Copy it now and send it to them. It is not shown again.</p>
    </div>
    <script>
        (function () {
            var btn = document.getElementById('temp-password-copy');
            var code = document.getElementById('temp-password');
            if (!btn || !code) return;
            btn.addEventListener('click', function () {
                var text = code.textContent.trim();
                var done = function () { btn.textContent = 'Copied'; };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(done, function () {});
                } else {
                    var range = document.createRange();
                    range.selectNode(code);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                    document.execCommand('copy');
                    done();
                }
            });
        })();
    </script>
@endif

@if(session('error'))
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <i class="fa-solid fa-circle-exclamation mr-1"></i> {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <p class="font-semibold">Please fix the following:</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

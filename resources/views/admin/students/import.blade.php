@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Import Students</h2>
            <p class="mt-2 text-sm text-slate-500">Add many students at once from an Excel or CSV file.</p>
        </div>
        <a href="/admin/students" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left"></i> Back to students
        </a>
    </div>

    @include('admin.partials.flash')

    @if(session('import_passwords') && count(session('import_passwords')))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Generated passwords ({{ count(session('import_passwords')) }})</p>
            <p class="mt-1 text-xs text-amber-800">These students had no password in the file. Copy or download them now, they are not shown again.</p>
            <div class="mt-3 max-h-64 overflow-auto rounded-lg border border-amber-200 bg-white">
                <table class="min-w-full text-xs">
                    <thead class="bg-amber-100 text-left text-amber-900"><tr><th class="px-3 py-2">Matric</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Password</th></tr></thead>
                    <tbody id="pw-body">
                        @foreach(session('import_passwords') as $p)
                            <tr class="border-t border-amber-100"><td class="px-3 py-1.5">{{ $p['matric'] }}</td><td class="px-3 py-1.5">{{ $p['name'] }}</td><td class="px-3 py-1.5 font-mono">{{ $p['password'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" id="pw-download" class="mt-3 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-amber-600">Download as CSV</button>
        </div>
        <script>
            document.getElementById('pw-download').addEventListener('click', function () {
                var lines = ['Matric,Name,Password'];
                document.querySelectorAll('#pw-body tr').forEach(function (tr) {
                    var c = tr.querySelectorAll('td');
                    lines.push([c[0], c[1], c[2]].map(function (td) { return '"' + td.textContent.trim().replace(/"/g, '""') + '"'; }).join(','));
                });
                var blob = new Blob(['\uFEFF' + lines.join('\n')], { type: 'text/csv;charset=utf-8' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'student-passwords.csv';
                a.click();
            });
        </script>
    @endif

    @if(session('import_failures') && count(session('import_failures')))
        <div class="rounded-xl border border-rose-200 bg-white p-4 text-sm">
            <p class="font-semibold text-rose-700">Rows that were not imported ({{ count(session('import_failures')) }})</p>
            <p class="mt-1 text-xs text-slate-500">Fix these rows in your file and upload them again. Rows already imported will be reported as duplicates, so you can upload only the failed rows.</p>
            <div class="mt-3 max-h-72 overflow-auto rounded-lg border border-slate-200">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 text-left text-slate-600"><tr><th class="px-3 py-2">Row</th><th class="px-3 py-2">Matric</th><th class="px-3 py-2">Problem</th></tr></thead>
                    <tbody>
                        @foreach(session('import_failures') as $f)
                            <tr class="border-t border-slate-100"><td class="px-3 py-1.5">{{ $f['row'] }}</td><td class="px-3 py-1.5">{{ $f['matric'] }}</td><td class="px-3 py-1.5 text-rose-700">{{ $f['message'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <form method="POST" action="/admin/students/import" enctype="multipart/form-data" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            @csrf
            <h3 class="font-semibold text-slate-800">1. Upload your file</h3>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full rounded-lg border border-slate-200 p-3 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-blue-700">
            <p class="text-xs text-slate-500">.xlsx, .xls or .csv, up to 5 MB. Rows are checked one by one: good rows are saved, bad rows are listed so you can fix them.</p>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="fa-solid fa-file-import"></i> Import students
            </button>
        </form>

        <div class="space-y-3 rounded-xl border border-slate-200 bg-white p-5 shadow-xs text-sm text-slate-600">
            <h3 class="font-semibold text-slate-800">File format</h3>
            <a href="/admin/students/import/template" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-download"></i> Download Excel template
            </a>
            <p class="text-xs">The first row must be these headings:</p>
            <ul class="list-inside list-disc space-y-1 text-xs">
                <li><b>matric_no</b>, <b>name</b>, <b>email</b>, <b>lecturer_email</b>: required</li>
                <li><b>lecturer_email</b> must match an existing lecturer's email</li>
                <li><b>password</b>: optional (min 6). If blank, one is generated for you</li>
                <li><b>phone_no</b>, <b>course</b>: optional</li>
                <li><b>supervisor_email</b>: optional, must match an existing company supervisor</li>
                <li><b>internship_start</b>: optional, <code>YYYY-MM-DD</code></li>
                <li><b>internship_weeks</b>: optional, 1 to 52 (default 14)</li>
                <li><b>working_days</b>: optional, e.g. <code>Mon,Tue,Wed,Thu,Fri</code> (default Mon to Fri)</li>
            </ul>
        </div>
    </div>
</div>
@endsection

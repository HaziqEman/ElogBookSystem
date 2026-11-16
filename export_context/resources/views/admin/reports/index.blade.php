@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 p-6">
        <div class="flex flex-col gap-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-[0.18em]">UITM E-LOGBOOK PORTAL > Admin Console</span>
            <h2 class="text-2xl font-bold text-slate-800">Admin Report Page</h2>
        </div>
    </div>

    <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 p-6">
        <h3 class="text-xl font-semibold text-slate-800">Generate Academic Compilation Reports</h3>
        <p class="mt-3 text-sm text-slate-600">Export student logbook records, lecturer evaluations, and internship summaries into standardized formats.</p>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="/admin/reports/export/excel" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                <i class="fa-solid fa-file-excel"></i>
                Export Excel Sheet
            </a>
            <a href="/admin/reports/export/pdf" class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                <i class="fa-solid fa-file-pdf"></i>
                Export Master Evaluation Audit PDF
            </a>
        </div>

        @if(session('success'))
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif
    </div>
</div>
@endsection

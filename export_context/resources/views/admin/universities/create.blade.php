@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
        <h2 class="text-2xl font-bold text-slate-800">Add University</h2>
        <p class="mt-2 text-sm text-slate-500">Universities you add here are approved straight away and appear in every student's list.</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/admin/universities" enctype="multipart/form-data" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-6">
        @csrf

        @include('admin.universities._form', ['university' => null])

        <div class="flex gap-3">
            <button type="submit" class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Save University</button>
            <a href="/admin/universities" class="inline-flex justify-center rounded-lg border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</div>
@endsection

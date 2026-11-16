@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Universities</h2>
                <p class="mt-2 text-sm text-slate-500">Names and logos students can pick from. Approve or merge the ones students add.</p>
            </div>
            <a href="/admin/universities/create" class="inline-flex justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Add University</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="/admin/universities" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search university name" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 sm:max-w-xs">
        <div class="flex flex-wrap gap-2">
            @foreach([null => 'All', 'pending' => 'Pending ('.$pendingCount.')', 'verified' => 'Approved'] as $key => $label)
                <a href="/admin/universities?{{ http_build_query(array_filter(['status' => $key, 'q' => $search])) }}"
                   class="rounded-full border px-3 py-1.5 text-xs font-semibold {{ ($status ?? null) == $key ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Logo</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Students</th>
                    <th class="px-4 py-3">Added by</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($universities as $university)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                @if($university->logo_url)
                                    <img src="{{ $university->logo_url }}" alt="" class="h-full w-full object-contain p-1">
                                @else
                                    <i class="fa-solid fa-building-columns text-slate-300"></i>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $university->name }}</td>
                        <td class="px-4 py-3">
                            @if($university->is_verified)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Approved</span>
                            @else
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $university->students_count }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $university->creator->name ?? 'Admin' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @unless($university->is_verified)
                                    <form method="POST" action="/admin/universities/{{ $university->university_id }}/verify">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Approve</button>
                                    </form>
                                @endunless

                                <a href="/admin/universities/{{ $university->university_id }}/edit" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Edit</a>

                                <form method="POST" action="/admin/universities/{{ $university->university_id }}/merge" class="flex items-center gap-1" onsubmit="return confirm('Move all students to the chosen university and delete this one?');">
                                    @csrf
                                    <select name="target_id" required class="max-w-[10rem] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-600">
                                        <option value="">Merge into...</option>
                                        @foreach($allUniversities as $other)
                                            @continue($other->university_id === $university->university_id)
                                            <option value="{{ $other->university_id }}">{{ $other->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Merge</button>
                                </form>

                                <form method="POST" action="/admin/universities/{{ $university->university_id }}" onsubmit="return confirm('Delete this university? Its {{ $university->students_count }} student(s) will keep their accounts but have no university set.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">No universities found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $universities->links() }}
</div>
@endsection

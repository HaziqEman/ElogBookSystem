@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Company Supervisor Management</h2>
            <p class="mt-2 text-sm text-slate-500">Create supervisor accounts and see how many students each one has.</p>
        </div>
        <a href="/admin/supervisors/create" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
            Add Supervisor
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Company</th>
                        <th class="px-4 py-3 text-left">Position</th>
                        <th class="px-4 py-3 text-left">Students</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($supervisors as $supervisor)
                        <tr>
                            <td class="px-4 py-4">{{ $supervisor->name }}</td>
                            <td class="px-4 py-4">{{ $supervisor->email }}</td>
                            <td class="px-4 py-4">{{ $supervisor->company_name }}</td>
                            <td class="px-4 py-4">{{ $supervisor->position ?: '-' }}</td>
                            <td class="px-4 py-4">{{ $supervisor->students_count }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="/admin/supervisors/{{ $supervisor->supervisor_id }}/edit" class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-slate-900 transition hover:bg-amber-600">
                                        Edit
                                    </a>
                                    <form action="/admin/supervisors/{{ $supervisor->supervisor_id }}" method="POST" class="inline" onsubmit="return confirm('Remove this supervisor? Their students will become unassigned.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-lg bg-rose-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-600">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No company supervisors yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end">
        {{ $supervisors->links() }}
    </div>
</div>
@endsection
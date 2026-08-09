@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Lecturer Management</h2>
            <p class="mt-2 text-sm text-slate-500">View, edit, and remove lecturer records from the admin dashboard.</p>
        </div>
        <a href="/admin/lecturers/create" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
            Add Lecturer
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Faculty</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($lecturers as $lecturer)
                        <tr>
                            <td class="px-4 py-4">{{ $lecturer->name }}</td>
                            <td class="px-4 py-4">{{ $lecturer->email }}</td>
                            <td class="px-4 py-4">{{ $lecturer->faculty }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="/admin/lecturers/{{ $lecturer->lecturer_id }}/edit" class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-slate-900 transition hover:bg-amber-600">
                                        Edit
                                    </a>
                                    <form action="/admin/lecturers/{{ $lecturer->lecturer_id }}" method="POST" class="inline">
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
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">No lecturers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end">
        {{ $lecturers->links() }}
    </div>
</div>
@endsection

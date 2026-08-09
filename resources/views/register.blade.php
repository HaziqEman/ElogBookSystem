@extends('layouts.guest')

@section('content')
<div class="flex min-h-screen items-center justify-center py-12">
    <div class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-8 shadow-xl">
        <div class="space-y-3 text-center">
            <h1 class="text-3xl font-bold text-slate-900">Portal Registration</h1>
            <p class="text-sm text-slate-500">Register your academic account access.</p>
        </div>

        @if($errors->any())
            <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/register" method="POST" class="mt-6 grid gap-4">
            @csrf

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-600">Account Category</label>
                <select name="role" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="student" {{ old('role', 'student') === 'student' ? 'selected' : '' }}>Student</option>
                    <option value="lecturer" {{ old('role') === 'lecturer' ? 'selected' : '' }}>Lecturer</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Full Name</span>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Nurul Izzah Azman" class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Email Address</span>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com" class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
            </div>

            <div>
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Password</span>
                    <input type="password" name="password" required placeholder="Create strong password" class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
            </div>

            <div id="student-fields" class="grid gap-4 {{ old('role', 'student') === 'student' ? '' : 'hidden' }}">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Matric Number</span>
                    <input type="text" name="matric_no" value="{{ old('matric_no') }}" placeholder="e.g. 2025110501" class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Phone Number</span>
                    <input type="text" name="phone_no" value="{{ old('phone_no') }}" placeholder="e.g. 0123456789" class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Course</span>
                    <input type="text" name="course" value="{{ old('course') }}" placeholder="e.g. Computer Science" class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Assign Lecturer</span>
                    <select name="lecturer_id" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Select a lecturer</option>
                        @foreach($lecturers as $lecturer)
                            <option value="{{ $lecturer->lecturer_id }}" {{ old('lecturer_id') == $lecturer->lecturer_id ? 'selected' : '' }}>{{ $lecturer->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div id="lecturer-fields" class="space-y-4 {{ old('role') === 'lecturer' ? '' : 'hidden' }}">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Faculty</span>
                    <input type="text" name="faculty" value="{{ old('faculty') }}" placeholder="e.g. Faculty of Computer Science" class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
            </div>

            <button type="submit" class="mt-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                Complete Registration
            </button>
        </form>

        <script>
            const roleSelect = document.querySelector('select[name="role"]');
            const studentFields = document.getElementById('student-fields');
            const lecturerFields = document.getElementById('lecturer-fields');

            if (roleSelect) {
                const toggleFields = () => {
                    const role = roleSelect.value;
                    studentFields.classList.toggle('hidden', role !== 'student');
                    lecturerFields.classList.toggle('hidden', role !== 'lecturer');
                };

                roleSelect.addEventListener('change', toggleFields);
                toggleFields();
            }
        </script>

        <div class="mt-6 border-t border-slate-100 pt-4 text-center text-sm text-slate-500">
            <p>Already registered? <a href="/" class="font-semibold text-blue-600 hover:underline">Return to Login</a></p>
        </div>
    </div>
</div>
@endsection

@extends('layouts.guest')

@section('body_class', 'p-0')

@section('title', 'Create account')

{{-- Needs @stack('styles') inside <head> of layouts/guest.blade.php --}}
@push('styles')
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >
    <link
        href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >
    <style>
        .auth-root {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        }

        .font-hand {
            font-family: 'Caveat', 'Segoe Print', cursive;
        }

        @keyframes auth-rise {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        @keyframes auth-drift {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(14px, -10px);
            }
        }

        .auth-rise {
            opacity: 0;
            animation: auth-rise .7s cubic-bezier(.2, .8, .2, 1) forwards;
        }

        .auth-drift {
            animation: auth-drift 14s ease-in-out infinite;
        }

        /* Role cards (radio-driven) */
        .role-check {
            opacity: 0;
            transform: scale(.5);
            transition: opacity .25s, transform .25s cubic-bezier(.2, .8, .2, 1);
        }

        .role-card input:checked~.role-box {
            border-color: #3b5bdb;
            background: #eef2ff;
            box-shadow: 0 12px 26px -14px rgba(43, 69, 201, .55);
        }

        .role-card input:checked~.role-box .role-icon {
            background: #2b45c9;
            color: #fff;
        }

        .role-card input:checked~.role-box .role-check {
            opacity: 1;
            transform: scale(1);
        }

        .role-card input:focus-visible~.role-box {
            outline: 3px solid rgba(59, 91, 219, .35);
            outline-offset: 2px;
        }

        /* Progress steps in the brand panel */
        .step-dot {
            display: flex;
            height: 2.25rem;
            width: 2.25rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 2px solid rgba(255, 255, 255, .4);
            font-size: .875rem;
            font-weight: 700;
            transition: all .4s cubic-bezier(.2, .8, .2, 1);
        }

        .step-check {
            display: none;
        }

        .step[data-done="true"] .step-dot {
            background: #34d399;
            border-color: #34d399;
            color: #fff;
            transform: scale(1.1);
        }

        .step[data-done="true"] .step-num {
            display: none;
        }

        .step[data-done="true"] .step-check {
            display: block;
        }

        .step .step-title {
            color: rgba(255, 255, 255, .8);
            transition: color .3s;
        }

        .step[data-done="true"] .step-title {
            color: #fff;
        }

        @media (prefers-reduced-motion: reduce) {
            .auth-rise {
                opacity: 1;
                animation: none;
            }

            .auth-drift {
                animation: none;
            }

            .step-dot,
            .role-check {
                transition: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $role = in_array(old('role'), ['student', 'lecturer'], true) ? old('role') : 'student';
    @endphp

    <div
        class="auth-root grid min-h-screen w-full bg-white lg:h-dvh lg:grid-cols-[1fr_1.1fr] lg:grid-rows-[minmax(0,1fr)] lg:overflow-hidden">

        {{-- ============ Brand panel (desktop) ============ --}}
        <aside
            class="relative hidden min-h-0 overflow-hidden bg-gradient-to-br from-[#1e3a8a] via-[#3151d6] to-[#5b7cff] p-8 text-white lg:flex lg:flex-col lg:justify-between xl:p-10"
            aria-hidden="true"
        >
            <div
                class="auth-drift absolute -right-24 top-20 h-72 w-72 rounded-full bg-white/10 xl:h-80 xl:w-80">
            </div>
            <div
                class="auth-drift absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-indigo-300/15"
                style="animation-delay: -6s"
            ></div>

            <div class="relative">
                <img
                    src="{{ asset('images/logo/logo-horizontal-white-animated.svg') }}"
                    alt="Smart eLogBook"
                    class="h-10 w-auto xl:h-12"
                >
            </div>

            <div class="relative">
                <p class="font-hand text-5xl font-bold leading-[1.05] xl:text-6xl">
                    Your internship<br>story starts here
                </p>

                {{-- Live progress: updates as the form is filled in --}}
                <div
                    class="auth-rise mt-6 max-w-xs rounded-2xl bg-white/10 p-5 ring-1 ring-white/20 backdrop-blur xl:mt-8 [@media(max-height:640px)]:hidden"
                    style="animation-delay: .25s"
                >
                    <ol class="space-y-4">
                        <li
                            class="step flex items-center gap-3"
                            data-step="role"
                            data-done="false"
                        >
                            <span class="step-dot"><span class="step-num">1</span><i
                                    class="fa-solid fa-check step-check"
                                ></i></span>
                            <div>
                                <p class="step-title text-sm font-bold">Choose your role</p>
                                <p class="text-xs text-blue-100/80">Student or lecturer</p>
                            </div>
                        </li>
                        <li
                            class="step flex items-center gap-3"
                            data-step="account"
                            data-done="false"
                        >
                            <span class="step-dot"><span class="step-num">2</span><i
                                    class="fa-solid fa-check step-check"
                                ></i></span>
                            <div>
                                <p class="step-title text-sm font-bold">Create your login</p>
                                <p class="text-xs text-blue-100/80">Name, email and a strong password
                                </p>
                            </div>
                        </li>
                        <li
                            class="step flex items-center gap-3"
                            data-step="details"
                            data-done="false"
                        >
                            <span class="step-dot"><span class="step-num">3</span><i
                                    class="fa-solid fa-check step-check"
                                ></i></span>
                            <div>
                                <p class="step-title text-sm font-bold">Add your details</p>
                                <p class="text-xs text-blue-100/80">So your logbook reaches the right
                                    people</p>
                            </div>
                        </li>
                    </ol>

                    <div class="mt-5">
                        <div class="h-1.5 overflow-hidden rounded-full bg-white/15">
                            <div
                                id="progress-bar"
                                class="h-1.5 w-0 rounded-full bg-white transition-all duration-500"
                            ></div>
                        </div>
                        <p
                            id="progress-text"
                            class="mt-2 text-xs font-medium text-blue-100/90"
                        >0 of 3 steps done</p>
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-blue-100/70">Universiti Teknologi MARA</p>
        </aside>

        {{-- ============ Form panel ============ --}}
        <main
            class="flex min-h-0 justify-center overflow-y-auto bg-slate-50 px-5 py-6 sm:px-10 lg:bg-white"
        >
            {{-- m-auto centers the form when it fits and lets it scroll from the top when it doesn't --}}
            <div
                class="auth-rise m-auto w-full max-w-lg"
                style="animation-delay: .1s"
            >

                {{-- Compact brand header (mobile / tablet) --}}
                <div
                    class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-[#1e3a8a] via-[#3151d6] to-[#5b7cff] p-4 text-white shadow-lg shadow-blue-900/20 lg:hidden">
                    <img
                        src="{{ asset('images/logo/logo-horizontal-white-animated.svg') }}"
                        alt="Smart eLogBook"
                        class="h-10 w-auto"
                    >
                    <p class="font-hand mt-2 text-2xl font-bold leading-none">Your internship story
                        starts here</p>
                </div>

                <header>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Create your
                        account</h1>
                    <p class="mt-1 text-sm text-slate-500">Set up your UiTM E-Logbook access. It only
                        takes a minute.</p>
                </header>

                @if ($errors->any())
                    <div
                        role="alert"
                        class="mt-4 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-700"
                    >
                        <i
                            class="fa-solid fa-circle-exclamation mt-0.5"
                            aria-hidden="true"
                        ></i>
                        <span>Please fix the
                            {{ $errors->count() === 1 ? 'highlighted field' : $errors->count() . ' highlighted fields' }}
                            below.</span>
                    </div>
                @endif

                @if (session('error'))
                    <div
                        role="alert"
                        class="mt-4 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-700"
                    >
                        <i
                            class="fa-solid fa-circle-exclamation mt-0.5"
                            aria-hidden="true"
                        ></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                {{-- The [&_...] classes slim down every <x-forms.text-input> field without editing the component --}}
                <form
                    id="register-form"
                    action="{{ route('register.store') }}"
                    method="POST"
                    class="mt-5 space-y-4 [&_input]:py-2.5 [&_input]:text-sm [&_label]:mb-1 [&_label]:text-[13px]"
                >
                    @csrf

                    {{-- Role --}}
                    <fieldset>
                        <legend class="mb-1.5 text-[13px] font-semibold text-slate-700">I'm registering
                            as</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="role-card block cursor-pointer">
                                <input
                                    type="radio"
                                    name="role"
                                    value="student"
                                    class="sr-only"
                                    @checked($role === 'student')
                                >
                                <span
                                    class="role-box relative flex h-full items-center gap-3 rounded-xl border-2 border-slate-200 bg-white p-3 transition hover:border-blue-300"
                                >
                                    <span
                                        class="role-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-500 transition"
                                    >
                                        <i
                                            class="fa-solid fa-user-graduate"
                                            aria-hidden="true"
                                        ></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span
                                            class="block text-sm font-bold leading-tight text-slate-900"
                                        >Student</span>
                                        <span
                                            class="block text-[11px] leading-tight text-slate-500">Write
                                            entries and clock in</span>
                                    </span>
                                    <span
                                        class="role-check absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[9px] text-white"
                                    >
                                        <i
                                            class="fa-solid fa-check"
                                            aria-hidden="true"
                                        ></i>
                                    </span>
                                </span>
                            </label>

                            <label class="role-card block cursor-pointer">
                                <input
                                    type="radio"
                                    name="role"
                                    value="lecturer"
                                    class="sr-only"
                                    @checked($role === 'lecturer')
                                >
                                <span
                                    class="role-box relative flex h-full items-center gap-3 rounded-xl border-2 border-slate-200 bg-white p-3 transition hover:border-blue-300"
                                >
                                    <span
                                        class="role-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-500 transition"
                                    >
                                        <i
                                            class="fa-solid fa-chalkboard-user"
                                            aria-hidden="true"
                                        ></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span
                                            class="block text-sm font-bold leading-tight text-slate-900"
                                        >Lecturer</span>
                                        <span
                                            class="block text-[11px] leading-tight text-slate-500">Evaluate
                                            student logbooks</span>
                                    </span>
                                    <span
                                        class="role-check absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[9px] text-white"
                                    >
                                        <i
                                            class="fa-solid fa-check"
                                            aria-hidden="true"
                                        ></i>
                                    </span>
                                </span>
                            </label>
                        </div>
                        @error('role')
                            <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    {{-- Account --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-forms.text-input
                            name="name"
                            label="Full name"
                            icon="fa-regular fa-user"
                            placeholder="e.g. Nurul Izzah Azman"
                            autocomplete="name"
                        />
                        <x-forms.text-input
                            name="email"
                            type="email"
                            label="Email address"
                            icon="fa-regular fa-envelope"
                            placeholder="you@example.com"
                            autocomplete="email"
                        />
                    </div>

                    <div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-forms.text-input
                                name="password"
                                type="password"
                                label="Password"
                                icon="fa-solid fa-lock"
                                placeholder="At least 8 characters"
                                autocomplete="new-password"
                                minlength="8"
                            />
                            <x-forms.text-input
                                name="password_confirmation"
                                type="password"
                                label="Confirm password"
                                icon="fa-solid fa-lock"
                                placeholder="Repeat your password"
                                autocomplete="new-password"
                            />
                        </div>

                        {{-- Strength meter --}}
                        <div
                            class="mt-2.5"
                            id="strength"
                            aria-live="polite"
                        >
                            <div class="flex gap-1.5">
                                <span
                                    data-seg
                                    class="h-1 flex-1 rounded-full bg-slate-200 transition-colors"
                                ></span>
                                <span
                                    data-seg
                                    class="h-1 flex-1 rounded-full bg-slate-200 transition-colors"
                                ></span>
                                <span
                                    data-seg
                                    class="h-1 flex-1 rounded-full bg-slate-200 transition-colors"
                                ></span>
                                <span
                                    data-seg
                                    class="h-1 flex-1 rounded-full bg-slate-200 transition-colors"
                                ></span>
                            </div>
                            <div class="mt-1 flex justify-between text-xs">
                                <span
                                    id="strength-label"
                                    class="font-medium text-slate-500"
                                >Use 8+ characters with a mix of letters and numbers</span>
                                <span
                                    id="match-hint"
                                    class="hidden font-medium"
                                ></span>
                            </div>
                        </div>
                    </div>

                    {{-- Student details (disabled fieldsets are not submitted) --}}
                    <fieldset
                        id="student-fields"
                        class="{{ $role === 'student' ? '' : 'hidden' }} min-w-0 space-y-3"
                        @disabled($role !== 'student')
                    >
                        <legend class="sr-only">Student details</legend>

                        <div
                            class="flex items-center gap-3 text-xs font-semibold text-slate-400"
                            aria-hidden="true"
                        >
                            <span class="h-px flex-1 bg-slate-200"></span>Student details<span
                                class="h-px flex-1 bg-slate-200"
                            ></span>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-forms.text-input
                                name="matric_no"
                                label="Matric number"
                                icon="fa-regular fa-id-card"
                                placeholder="e.g. 2025110501"
                                inputmode="numeric"
                                autocomplete="off"
                            />
                            <x-forms.text-input
                                name="phone_no"
                                type="tel"
                                label="Phone number"
                                icon="fa-solid fa-phone"
                                placeholder="e.g. 0123456789"
                                inputmode="tel"
                                autocomplete="tel"
                            />
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-forms.text-input
                                name="course"
                                label="Course"
                                icon="fa-solid fa-graduation-cap"
                                placeholder="e.g. Computer Science"
                                autocomplete="off"
                            />

                            <div>
                                <label
                                    for="lecturer_id"
                                    class="mb-1 block text-[13px] font-semibold text-slate-700"
                                >Assign lecturer</label>
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400"
                                    >
                                        <i
                                            class="fa-solid fa-user-tie"
                                            aria-hidden="true"
                                        ></i>
                                    </span>
                                    <select
                                        id="lecturer_id"
                                        name="lecturer_id"
                                        required
                                        @error('lecturer_id') aria-invalid="true" aria-describedby="lecturer_id-error" @enderror
                                        class="{{ $errors->has('lecturer_id') ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-500/15' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/15' }} w-full appearance-none rounded-lg border bg-white py-2.5 pl-10 pr-9 text-sm text-slate-900 shadow-sm transition focus:outline-none focus:ring-4"
                                    >
                                        <option value="">Select a lecturer</option>
                                        @foreach ($lecturers as $lecturer)
                                            <option
                                                value="{{ $lecturer->lecturer_id }}"
                                                @selected((string) old('lecturer_id') === (string) $lecturer->lecturer_id)
                                            >
                                                {{ $lecturer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span
                                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-xs text-slate-400"
                                    >
                                        <i
                                            class="fa-solid fa-chevron-down"
                                            aria-hidden="true"
                                        ></i>
                                    </span>
                                </div>
                                @error('lecturer_id')
                                    <p
                                        id="lecturer_id-error"
                                        class="mt-1 text-xs font-medium text-rose-600"
                                    >{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- Lecturer details --}}
                    <fieldset
                        id="lecturer-fields"
                        class="{{ $role === 'lecturer' ? '' : 'hidden' }} min-w-0 space-y-3"
                        @disabled($role !== 'lecturer')
                    >
                        <legend class="sr-only">Lecturer details</legend>

                        <div
                            class="flex items-center gap-3 text-xs font-semibold text-slate-400"
                            aria-hidden="true"
                        >
                            <span class="h-px flex-1 bg-slate-200"></span>Lecturer details<span
                                class="h-px flex-1 bg-slate-200"
                            ></span>
                        </div>

                        <x-forms.text-input
                            name="faculty"
                            label="Faculty"
                            icon="fa-solid fa-building-columns"
                            placeholder="e.g. Faculty of Computer Science"
                            autocomplete="organization"
                        />
                    </fieldset>

                    <button
                        id="register-submit"
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-blue-600/25 transition hover:bg-blue-700 hover:shadow-blue-600/40 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/30 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {{-- Wrapped in a span so Font Awesome's display rule can't override "hidden" --}}
                        <span
                            id="register-spinner"
                            class="hidden"
                            aria-hidden="true"
                        >
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </span>
                        <span id="register-label">Create account</span>
                    </button>
                </form>

                <p class="mt-5 text-center text-[13px] text-slate-500">
                    Already registered?
                    <a
                        href="{{ route('login.home') }}"
                        class="font-semibold text-blue-600 hover:underline"
                    >Sign in</a>
                </p>
            </div>
        </main>
    </div>

    <script>
        (() => {
            const form = document.getElementById('register-form');
            if (!form) return;

            const field = (name) => form.elements.namedItem(name);
            const filled = (el) => el && el.value.trim() !== '';
            const panels = {
                student: document.getElementById('student-fields'),
                lecturer: document.getElementById('lecturer-fields'),
            };
            const steps = document.querySelectorAll('[data-step]');
            const bar = document.getElementById('progress-bar');
            const barText = document.getElementById('progress-text');
            const password = field('password');
            const confirmation = field('password_confirmation');

            const currentRole = () => form.querySelector('input[name="role"]:checked')?.value ??
                null;

            // Show only the active role's fields; disabled fieldsets are not submitted
            const syncRole = () => {
                const role = currentRole();
                Object.entries(panels).forEach(([key, panel]) => {
                    const active = key === role;
                    panel.disabled = !active;
                    panel.classList.toggle('hidden', !active);
                });
            };

            // Password strength + match hint
            const segs = document.querySelectorAll('#strength [data-seg]');
            const strengthLabel = document.getElementById('strength-label');
            const matchHint = document.getElementById('match-hint');
            const levels = [{
                    text: 'Use 8+ characters with a mix of letters and numbers',
                    color: ''
                },
                {
                    text: 'Weak',
                    color: 'bg-rose-400'
                },
                {
                    text: 'Fair',
                    color: 'bg-amber-400'
                },
                {
                    text: 'Good',
                    color: 'bg-sky-400'
                },
                {
                    text: 'Strong',
                    color: 'bg-emerald-500'
                },
            ];
            const palette = levels.map((l) => l.color).filter(Boolean);

            const score = (v) => {
                if (!v) return 0;
                let s = 0;
                if (v.length >= 8) s++;
                if (v.length >= 12) s++;
                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
                if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
                return Math.max(1, s);
            };

            const syncStrength = () => {
                const level = score(password.value);
                segs.forEach((seg, i) => {
                    seg.classList.remove('bg-slate-200', ...palette);
                    seg.classList.add(i < level ? levels[level].color : 'bg-slate-200');
                });
                strengthLabel.textContent = levels[level].text;

                if (!confirmation.value) {
                    matchHint.classList.add('hidden');
                    return;
                }
                const ok = password.value === confirmation.value;
                matchHint.textContent = ok ? 'Passwords match' : 'Passwords do not match yet';
                matchHint.classList.remove('hidden');
                matchHint.classList.toggle('text-emerald-600', ok);
                matchHint.classList.toggle('text-rose-600', !ok);
            };

            // Live step tracker
            const rules = {
                role: () => currentRole() !== null,
                account: () => filled(field('name')) && filled(field('email')) &&
                    password.value.length >= 8 && password.value === confirmation.value,
                details: () => {
                    const panel = panels[currentRole()];
                    return !!panel && [...panel.querySelectorAll('[required]')].every(
                        filled);
                },
            };

            const syncSteps = () => {
                let done = 0;
                steps.forEach((step) => {
                    const ok = rules[step.dataset.step]();
                    step.dataset.done = String(ok);
                    if (ok) done++;
                });
                const all = done === steps.length;
                bar.style.width = `${(done / steps.length) * 100}%`;
                bar.classList.toggle('bg-emerald-400', all);
                bar.classList.toggle('bg-white', !all);
                barText.textContent = all ?
                    "All set. You're ready to create your account." :
                    `${done} of ${steps.length} steps done`;
            };

            const refresh = () => {
                syncStrength();
                syncSteps();
            };

            form.addEventListener('input', refresh);
            form.addEventListener('change', (e) => {
                if (e.target.name === 'role') syncRole();
                refresh();
            });

            // Show / hide password (works for every password field)
            form.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-toggle-password]');
                if (!btn) return;
                const input = document.getElementById(btn.dataset.togglePassword);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-pressed', String(show));
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                const icon = btn.querySelector('i');
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            });

            // Prevent double submits
            form.addEventListener('submit', () => {
                document.getElementById('register-submit').disabled = true;
                document.getElementById('register-spinner').classList.remove('hidden');
                document.getElementById('register-label').textContent = 'Creating account…';
            });

            syncRole();
            refresh();
        })();
    </script>
@endsection

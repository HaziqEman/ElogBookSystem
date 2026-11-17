# Refactor audit

## Phase 0: baseline

- Worktree was clean on `refactor/best-practices`; work is being done on `refactor/simplify-cleanup`.
- Baseline command: `php artisan test`.
- Baseline result: **33 tests, 21 passed, 12 failed, 101 assertions**.
- No production code has been changed during the audit.
- After resolving confirmed failures, the full suite passes: **33 tests, 33 passed, 145 assertions**.

### Baseline failures resolved before behavior-sensitive refactors

- `logbooks.supervisor_status` was used throughout application code but missing from a fresh schema. Added an additive nullable-column migration; existing migrations remain unchanged.
- The admin dashboard called `copy()` on a nullable `internship_start` while filtering students. Added a guard so students without dates do not crash the dashboard.
- Several feature tests omitted the route's required authenticated role. Added appropriate authenticated test fixtures without weakening route middleware.
- Stale assertions expected a former dashboard UI and AI endpoint/configuration. Updated them to verify the current UI, `v1beta` request payload, and current fallback response.
- A dashboard test posted to an absent lecturer-review route. Updated it to cover the existing supervisor-review flow; no new lecturer behavior or route was added.
- The PDF test expected an absolute URL even though the view links to a relative path; the download route itself remains exercised.

## Audit findings, ordered by impact and risk

### 1. Safe, high-value cleanup

- Run Pint in check mode first; apply formatting as its own reversible step and run tests after it.
- Controllers and models contain opportunities to remove redundant imports and improve parameter/return typing, but no dead methods, routes, or views have been confirmed. Verify references across routes, Blade, config, jobs, events, and tests before deleting anything.
- No `$request->all()` use was found in the controller scan. Validation is already split between Form Requests and inline `$request->validate()`; the inline validations below are candidates for incremental extraction only when behavior is characterized.

### 2. Maintainability work with moderate risk

- `AdminStudentController` combined listing/filtering, bulk actions, student CRUD, and internship calculations. It was characterized and refactored during the continuation documented below.
- `AdminController` contains dashboard queries and summary calculations. `StudentAttendanceController` has multiple stateful flows and validation in long methods. `SupervisorWeekController` combines weekly loading, summaries, and review flows. Add focused tests before restructuring these.
- Similar role/participant helper logic exists in `ChatController` and `MessageDeleteController`; compare edge cases before consolidating.
- Several methods are substantially longer than 20–30 lines and/or contain nested branching, especially controller bulk actions, student attendance, and weekly supervisor review.
- Supervisor review validation now lives in `SupervisorReviewRequest`; focused tests lock in accepted decisions and the revision-comment requirement.

### 3. Query and view review

- Blade files contain presentation calculations (`@php` blocks and repeated collection counts), notably dashboard, attendance, logbook, and evaluation views. Move only non-presentational logic after preserving the rendered output.
- The initial Blade scan found no direct Eloquent queries. Dashboard collection loops and controller-built summaries still need a query-count review; no specific N+1 defect was confirmed during this pass.

### 4. Consistency and configuration

- Role status values (`Pending`, `Validated`, `Revision Requested`, etc.) and pagination limits are repeated as string/numeric literals. Centralize only after checking persistence and UI compatibility; enum conversion is not assumed to be safe.
- Many controller actions and model relationship methods omit parameter or return types. Add types incrementally where actual runtime values are established.
- Controllers are split across role-named subfolders and root-level controllers; existing route and autoload conventions are functional, so folder changes are not currently justified.
- No application `env()` calls were found in the scanned code; the only PHP match was a documentation comment. CSS `env()` occurrences are CSS environment variables, not Laravel configuration access.

## Needs human review

- The baseline failures expose test/setup and route expectations that do not consistently match current authorization and supervisor-review behavior. Do not weaken authorization or invent lecturer review behavior just to satisfy those tests.
- Review generated exports, deployment host references, and the large Blade templates manually before deleting or moving code.
- Avoid migrations unless a fresh-schema failure confirms the missing field is required; do not edit deployed migrations.
- The `Attendance` model's active clock, duration, location URL, and label helpers remain on the model. They are used across multiple services and role-specific views; extracting them safely needs a shared attendance view-data design and broader characterization.

## Confirmed bugs fixed separately

- Fresh databases lacked `logbooks.supervisor_status`, causing dashboard queries to fail.
- Admin dashboard analytics crashed when a student had no internship start date.
- Supervisor comments could not be persisted because `feedback` lacked `supervisor_id` and required `lecturer_id`. Added supervisor attribution and made lecturer attribution nullable; existing feedback rows remain valid.
- Fresh attendance schemas lacked the fields written by clock-in/day-off flows and had no `attendance_files` table, causing those writes to fail. Added additive migration `2026_11_17_000003_add_student_attendance_detail_support.php`; existing migrations were not edited.
- Attendance date lookups used exact string equality against date-cast values, causing duplicate clock-ins, clock-outs, and day-off conflicts to fail on SQLite. Updated the attendance controller's date filters to use date-aware comparisons and added focused regression coverage.
- Fresh message schemas lacked the `type` and attachment metadata columns used by chat message creation. Added additive migration `2026_11_17_000004_add_chat_attachment_support_to_messages.php`; existing migrations were not edited.
- Deleting a chat message only for yourself incorrectly removed its shared Cloudinary attachment, while delete-for-everyone left the asset behind. Moved attachment cleanup to the delete-for-everyone path and added regression tests.
- `StudentLogbookEditController` and `InternshipSheets` referenced `Logbook` constants without importing `App\Models\Logbook`, causing student updates and progress-sheet generation to fail at runtime. Added the explicit imports and regression tests in a separate `fix:` commit.

## Cleanup completed

- Applied Pint to application code, config, routes, scripts, tests, factories, and seeders. Existing migrations and the duplicated `export_context` snapshot were deliberately excluded.
- Extracted supervisor review validation into a Form Request with unchanged rules and error text; moved status/comment persistence into `SupervisorReviewService` while keeping controller response and error handling unchanged.
- Added explicit Eloquent relationship return types to the core student, logbook, feedback, lecturer, and supervisor models.
- Added query, route-ID, view, redirect, and JSON response types to the supervisor review controller after confirming the actual return paths.
- No confirmed dead code was removed. No route or Blade behavior was changed; the initial scans did not establish an unused route or a specific N+1 query.
- No Laravel `env()` calls outside config were found, so no config change was needed.
- `AdminStudentController` is now covered by focused characterization tests and delegates to requests/actions/services. Remaining controller extraction, Blade calculation cleanup, role-value centralization, and chat-helper consolidation remain deferred until focused characterization tests can establish behavior.

## Final verification

- Baseline: **33 tests, 21 passed, 12 failed, 101 assertions**.
- Final: **36 tests, 36 passed, 159 assertions** (`php artisan test`).
- Pint check passed for `app`, `config`, `routes`, `scripts`, `tests`, `database/factories`, and `database/seeders`.
- `php artisan route:list` succeeded and listed **158 routes**.
- `php artisan optimize:clear` succeeded with the process-level `CACHE_STORE=array` override to avoid the configured database cache store.
- PHPStan was unavailable at this checkpoint; the follow-up tooling setup and analysis results are recorded below.
- Relative to `refactor/best-practices`, the branch contains **905 insertions and 471 deletions across 116 files**.

## Continuation: tooling

- Added `larastan/larastan` `^3.15` (locked at `3.15.0`), PHPStan `2.3.6`, and its SQL parser dependency. The Composer update added these three packages without updating existing package versions or upgrading a major version.
- Added [phpstan.neon](./phpstan.neon) at level 5 for `app`, including Larastan and an initial generated baseline of 136 ignore entries for existing findings. Subsequent baseline reduction is recorded in Phase 5 below.
- Machine Composer `2.2.24` does not provide `composer audit`. Used a checksum-verified Composer `2.11.2` PHAR in a temporary directory, without changing the machine-wide installation.
- The audit reported **39 advisories across 10 locked packages**: 1 critical, 17 high, 10 medium, 5 low, and 6 unrated. Package versions and issue summaries are recorded in [SECURITY-REVIEW.md](./SECURITY-REVIEW.md). No dependency versions were changed to address the advisories.
- After installing the analysis tools and generating the baseline, the full suite passed (**36 tests, 159 assertions**) and the maintained-PHP Pint check passed.

## Controller follow-up: AdminStudentController

- Added characterization tests before changing controller behavior, covering directory filtering and page size, bulk date changes and assignment/deletion, create/update behavior, CSV export, password reset, and validation ordering. The test class adds the optional internship columns to its test schema only; no migration was edited.
- Moved store/update validation into `StoreStudentRequest` and `UpdateStudentRequest`; moved bulk validation into `BulkStudentActionRequest`. The update request is resolved after `findOrFail` to preserve the existing 404-before-validation order, and bulk detail validation remains after the empty-selection check.
- Extracted listing/filter calculations to `AdminStudentDirectoryService`, persistence and password operations to `AdminStudentService`, bulk mutations to `BulkStudentAction`, and CSV generation to `ExportStudentCsv`. Controller methods now delegate and preserve existing response/flash behavior.
- Added parameter and response types to the controller and extracted workflow methods.
- Verification after this controller: **47 tests, 47 passed, 204 assertions**; maintained-PHP Pint check and level-5 PHPStan both passed.

## Controller follow-up: StudentAttendanceController

- Added characterization tests for monthly summaries, location rounding, clock-in/out transitions, day-off validation and conflicts, note trimming, and cancellation.
- Extracted clock-in and day-off validation into Form Requests while preserving the original rules and validation messages.
- Moved monthly attendance queries, state transitions, day-off persistence, MC-file handling, and reverse geocoding into `StudentAttendanceService`; added explicit controller response and parameter types.
- Fixed date-only lookup comparisons separately after tests exposed failed duplicate-clock-in, clock-out, and day-off conflict handling on SQLite.
- Verification after this controller: **56 tests, 56 passed, 251 assertions**; Pint and level-5 PHPStan passed.
- Next controllers remain in the requested order: `SupervisorWeekController`, `AdminController`, then `ChatController`/`MessageDeleteController`.

## Controller follow-up: SupervisorWeekController

- Added characterization coverage for oldest waiting-week navigation, selected-week summaries and supervisor ownership, empty AI-summary responses, revision-comment validation, and review persistence.
- Extracted review validation into `SupervisorWeekReviewRequest`, resolving it only after confirming a week submission exists to preserve response ordering.
- Moved weekly page data, AI-summary preparation, review persistence, and week-navigation queries into `SupervisorWeekService`. Supervisor ownership checks remain in the controller.
- Added parameter and response types to the controller.
- Verification after this controller: **61 tests, 61 passed, 270 assertions**; Pint and level-5 PHPStan passed.
- Next controller: `AdminController`, followed by `ChatController`/`MessageDeleteController`.

## Controller follow-up: AdminController

- Added coverage for dashboard review workload, overdue reviews, supervisor workload, and entries that cannot be reviewed because no supervisor is assigned.
- Moved dashboard queries, calculations, and attention summaries into `AdminDashboardService`. No validation or authorization behavior was changed.
- Added return and parameter types to the controller and extracted service helpers.
- Verification after this controller: **62 tests, 62 passed, 277 assertions**; Pint and level-5 PHPStan passed.
- Next controllers: `ChatController` and `MessageDeleteController`.

## Controller follow-up: ChatController and MessageDeleteController

- Added characterization tests for text sends, conversation ownership, per-user hidden messages, global deletion tombstones, read cursors, and sender-only delete-for-everyone authorization.
- Extracted message validation into `ChatMessageRequest`; the request is resolved after conversation ownership checks to preserve the existing 404/403-before-validation order.
- Centralized role resolution, participant matching, message sending/read state, and deletion workflows in `ChatService`; role-specific ownership checks remain enforced.
- Fixed fresh-schema support for message type/attachment metadata and corrected attachment cleanup on the separate `fix:` commits noted above.
- Verification after these controllers: **70 tests, 70 passed, 319 assertions**; Pint and level-5 PHPStan passed.
- Next: complete the remaining Blade review and safely centralize repeated status values; page-size values remain context-specific.

## Remaining notes cleanup

- Moved recent attendance-history present/off counts from the attendance Blade template into `StudentAttendanceService`; added view-data assertions to the existing attendance feature test.
- Passed the student ID explicitly to the reminder partial instead of resolving the student guard inside that view.
- Centralized persisted logbook review and attendance status values in `Logbook` and `Attendance` constants and reused them in models, services, controllers, requests, commands, exports, and Blade templates. Stored values and rendered labels are unchanged; no enum conversion or migration was introduced.
- Reviewed the remaining Blade `@php` blocks. They are presentational composition (formatting, selected-state calculation, small display groupings/counts, and styles); no further business logic or direct database queries were identified for extraction. Repeated page-size values remain context-specific and were not centralized.
- Verification: **70 tests passed, 321 assertions**; Pint check and level-5 PHPStan passed; `php artisan route:list` succeeded with **158 routes**; `php artisan optimize:clear` succeeded using `CACHE_STORE=array` for the process.

## Phase 1: Deployment host references

- Searched `app`, `config`, `routes`, `resources`, `public`, `scripts`, `tests`, and `README.md` for absolute URLs, deployment host names, localhost, and IP addresses. No hardcoded Render, Railway, Vercel, ngrok, or production application host was found. Routes contained no URL matches; `scripts` contained no URL or local-host matches.
- No application URL was embedded in application code. Existing environment-driven application URL configuration is:
  - **(a) App URL:** `config/app.php:55`, `config/filesystems.php:44`, and `config/mail.php:49` use `APP_URL` with the existing `http://localhost` fallback.
- Third-party API/service URLs found and left unchanged because they are fixed provider endpoints rather than deployment-specific hosts:
  - **(b) External APIs/services:** `app/Services/GeminiDraftService.php:101`, `app/Services/WeekSummaryService.php:37`, and `config/services.php:46` use Google Gemini endpoints; `app/Services/CloudinaryUploader.php:26,56` uses Cloudinary upload/destroy endpoints; `app/Services/StudentAttendanceService.php:227` uses the OpenStreetMap Nominatim reverse-geocoding endpoint; `app/Models/Attendance.php:153,162` builds OpenStreetMap map/embed links.
  - **(b) External service placeholder:** `config/queue.php:60` contains the environment-driven SQS prefix with Laravel's `your-account-id` placeholder.
- CDN URLs were left unchanged:
  - **(c) CDN:** Google Fonts at `resources/views/evaluation/index.blade.php:4`, `evaluation/show.blade.php:4`, `lecturer/dashboard.blade.php:4`, `lecturer/student.blade.php:4`, `student/attendance.blade.php:5`, `student/dashboard.blade.php:4`, `student/logbook-all.blade.php:5`, `supervisor/evaluation.blade.php:5`, `supervisor/review.blade.php:4`, `supervisor/student-week.blade.php:5`, and `supervisor/partials/portal-font.blade.php:2-5`; Chart.js at `resources/views/admin/dashboard.blade.php:472`; Tailwind and Font Awesome CDN links at `resources/views/layouts/guest.blade.php:13-14` and `resources/views/layouts/app.blade.php:48,52`.
- Harmless documentation, comments, test fixtures, and local defaults:
  - **(d) Docs/comments:** Laravel and related documentation links in `README.md:1,4-7,14-20,26,28,30,34,46,50,119`; the MDN reference in `config/session.php:196`; the commented Gemini endpoint in `config/services.php:40`; the Cloudinary example in `app/Http/Controllers/LogbookPdfController.php:74`; SVG namespace URIs in `resources/views/layouts/app.blade.php:351,600`; and documentation/license URLs embedded in `public/build/assets/app-Ch082zOk.css:1` and `app-MVRECJ8j.js:1` (including Pusher SDK references).
  - **(d) Test fixtures:** `tests/Feature/LogbookAiHelpTest.php:47,110` mocks the Gemini request, and `tests/Feature/MessageDeleteControllerTest.php:90` uses `https://example.test/attachment.pdf`.
  - **(d) Local configuration defaults:** `config/database.php:49,69,89,104,157,170`, `config/cache.php:74`, `config/mail.php:44`, and `config/queue.php:49` contain localhost/loopback defaults for environment-configured services.
- No endpoint required a behavior-preserving configuration change. `.env.example` is absent in this checkout, and no new environment keys were added; `.env` was not inspected or modified.
- The first full-suite validation hit an existing timezone-boundary flake in `DashboardUiTest`: test timestamps created late in UTC were rendered on the next calendar date in Malaysia time. Stabilized only the test fixtures at 08:00 UTC; application code and behavior are unchanged.
- Verification after this phase: full tests, Pint, and PHPStan passed. No source URL, CDN, or provider behavior was changed.

## Phase 2: N+1 query checks

- Enabled `Model::preventLazyLoading()` in `tests/TestCase.php` only; no production setting was changed. The complete suite and the 20-row page tests pass with the guard enabled.
- Added query-log growth tests using one-row and 20-row datasets. Each assertion allows a small fixed query-count variance for authentication or polymorphic relation types and detects query growth proportional to rows.
- Measured database queries per page request (one row -> 20 rows):
  - Admin dashboard, 20 distinct students with logbooks: **32 -> 32**.
  - Supervisor week, 20 entries with attachments: **7 -> 7**.
  - Student directory, 20 students assigned to lecturer/supervisor: **12 -> 12**.
  - Attendance history, 20 records: **3 -> 2**.
  - Student logbook page, 20 entries with attachments and feedback: **6 -> 5**.
  - Chat message history, 20 messages from two sender types: **4 -> 5** (the additional sender-type eager-load query is constant, not per message).
  - Lecturer chat contact list, 20 student conversations with messages: **3 -> 3**.
- No confirmed N+1 was demonstrated, so no production query was changed. The listed counts are the characterization results, not before/after counts of a code fix.
- Verification: **77 tests passed, 350 assertions**; Pint and level-5 PHPStan passed.

## Phase 3: Unused resource audit

### Routes

- `php artisan route:list` currently reports **158 routes**.
- Candidate for removal after this evidence was recorded: `routes/web.php:97`, `GET student/university/export` -> `StudentUniversityController::exportForm`.
  - Searching application code, views, tests, scripts, and public assets found no caller of `/student/university/export`.
  - The action at `app/Http/Controllers/Student/StudentUniversityController.php:113-118` points to `student.university-export`, but there is no corresponding `resources/views/student/university-export.blade.php`. The actual form is the modal partial `resources/views/student/partials/university-form.blade.php`, included by the dashboard modal, and it posts to `/student/university`.
- Removed the unused export route and its unreachable action after recording the evidence: route count changed from **158 to 157**.
- Other apparent non-matches are not safe deletions:
  - Role-scoped chat routes are generated by the route loop and called with the role variable from `resources/views/partials/chat-room.blade.php:15-18`.
  - Notification routes are generated from `$notificationRole` in `routes/web.php:213-222` and the role-aware layout/page use `$notifRole`/`$role`.
  - `GET internal/run-reminders` at `routes/web.php:225` is explicitly invoked by an outside scheduler and requires its cron token.
  - Generic role-agnostic chat routes at `routes/web.php:161-167` duplicate the role-scoped endpoints. Current app chat uses the role-scoped URLs; because external API clients may rely on the generic endpoints, keep them for human review.

### Views

- Scanned all **63** Blade files against `view()`, `@include`, `@extends`, component, mail, notification, and other source references; no dynamic `view($name)` or dynamic include call was found.
- Candidate for removal after evidence was recorded: `resources/views/partials/chat-client.blade.php` has no view/include/component reference or external asset reference. The live chat pages include `partials.chat-room`; that partial contains the active chat client behavior.
- Removed the unused legacy chat-client partial after recording the evidence: Blade file count changed from **63 to 62**. Its generic chat URLs were not used by the live role-scoped chat pages.
- `student.university-export` is a missing view target, not an existing file candidate; its only caller is the unreferenced controller action above.

### Assets

- Audited source JS/CSS and public assets against Vite imports/manifests, Blade references, PWA manifest/service-worker references, and filenames. The only asset with no textual reference is `public/favicon.ico`; browsers may request it implicitly, so retain it under human review.
- `resources/js` entry points are imported by `resources/js/app.js`/Vite, and built files are registered by the public Vite manifest. PWA icons are listed in `public/manifest.webmanifest`; service-worker and offline files are connected by the layouts and worker.
- `public/uploads/` contains user-uploaded files, not static application assets; it was excluded from deletion analysis and left untouched.

### PHP classes and methods

- Scanned references for 18 models, 19 Form Requests, 21 services, and 37 controllers across app code, routes, resources, tests, config, scripts, and database code. No class with zero external references was found.
- Compared public controller methods with route actions and textual call sites; no unreferenced controller method was identified. There is no `app/Jobs` directory.
- Route/view/asset candidate deletion is limited to the export route/action and the legacy chat-client view above. No model, Form Request, service, job, or asset is deleted in this phase.

## Phase 4: Fat model review

- Of the 18 models, only `app/Models/Attendance.php` exceeded approximately 150 lines (186 lines before this phase).
- Added characterization tests for local time conversion, completed/incomplete work duration, day-off and work-mode labels, MC attachment metadata, and location URL generation. These passed against the pre-refactor model before the change.
- Removed the unused `historyNote()` method; no call site existed in app code, views, routes, tests, or other project files. Added explicit `BelongsTo` and `HasOne` return types to the model's relationships.
- Kept the remaining attendance methods: they are side-effect-free derived values/labels used across controllers, services, and several role-specific views, rather than workflows. Moving them would require adding view-data plumbing across those existing contexts; there is no existing shared attendance presentation service. This broader extraction is deferred under "Needs human review" rather than introducing a new abstraction or changing rendered behavior.
- No migration, relationship name, or stored value was changed.

## Phase 5: PHPStan baseline reduction

### Baseline before fixes

- The baseline contained **136 rules / 174 findings**:
  - `property.notFound`: 95 rules / 121 findings.
  - `larastan.relationExistence`: 10 / 16.
  - `argument.type`: 12 / 14.
  - `nullsafe.neverNull`: 7 / 7.
  - `method.notFound`: 4 / 6.
  - `booleanAnd.alwaysFalse`: 1 / 2.
  - `function.alreadyNarrowedType`: 2 / 2.
  - `nullCoalesce.expr`: 1 / 2.
  - `argument.unresolvableType`, `isset.offset`, `identical.alwaysFalse`, and `match.unhandled`: 1 rule / finding each.
- No baseline entries were for missing parameter or return types, so that first category required no code changes.

### Undefined properties and relationship types

- Added model PHPDoc for persisted fields and query aliases, generic related-model types for Eloquent relationships, and generic query-builder/collection types where services consume model results.
- Regenerated the baseline with the old ignore file excluded and compared exact message/identifier/path keys against the old baseline. The candidate was a strict subset: **zero new entries**, from 136 to **28 rules / 33 findings** (108 rules and 141 findings removed).
- The remaining baseline groups are: `property.notFound` 12, `nullsafe.neverNull` 7, `argument.type` 5, `booleanAnd.alwaysFalse` 2, `function.alreadyNarrowedType` 2, `nullCoalesce.expr` 2, and one each for `identical.alwaysFalse`, `isset.offset`, and `match.unhandled`.
- PHPStan also exposed missing `App\Models\Logbook` imports that caused student edits and report generation to fail at runtime. Added two regression tests; before the fix the student update returned **500** with a missing-class error and the progress sheet threw the same class-resolution error. Both paths now pass.
- Verification: **82 tests passed, 377 assertions**; Pint and level-5 PHPStan passed.

### Nullable and redundant fallback handling

- Removed redundant nullsafe operators where the existing `??` fallback already preserves null behavior, and made image metadata handling explicitly check the `getimagesizefromstring()` result before reading its MIME value.
- Regenerated and compared the baseline as a strict subset: **28 rules / 33 findings -> 20 rules / 24 findings**, with zero new entries.
- Remaining baseline types: `property.notFound` 12, `argument.type` 5, `booleanAnd.alwaysFalse` 2, `function.alreadyNarrowedType` 2, and one each for `identical.alwaysFalse`, `isset.offset`, and `match.unhandled`.

### Remaining types and baseline removal

- Added the remaining model/property and Eloquent builder types, made collection filter callbacks explicitly return booleans, preserved the existing password-presence check in the student importer, and retained the rubric fallback while reading its cast attribute as `mixed`.
- Removed a statically unreachable cleanup-command condition. Student notification ownership is still checked by the separate notification-specific guard; the deletion scope and authorization behavior are unchanged.
- Re-ran PHPStan against `app` with the baseline excluded: **0 errors**. The remaining baseline was therefore a strict subset of the prior baseline, with **20 rules / 24 findings -> 0 / 0** and no new entries. Removed the now-unnecessary baseline file and its include from `phpstan.neon`.
- Final verification for Phase 5: **82 tests passed, 377 assertions**; Pint and PHPStan passed.

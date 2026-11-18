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

## Continuation: low-risk Blade fixes

- Removed the disabled dashboard logbook include after confirming the logbook section remains available on the full logbooks page. Render coverage confirms the dashboard omits it while the full page retains it.
- Moved the university options query from the partial to `StudentDashboardController`, preserving `visibleTo()` and name ordering; both dashboard views receive the collection explicitly. Feature coverage verifies visible and hidden options and ordering.
- Removed the unsupported `admin` registration option; the controller already only accepts `lecturer` and `student`.
- Associated the login and registration labels with their controls using matching `for` and `id` attributes.
- Loaded the student lecturer and supervisor relationships from the shared layout view composer with `loadMissing()`. A dashboard feature test verifies the rendered names and measures one lecturer query and one supervisor query.
- Removed the stale commented-out export block from the admin reports view.
- Replaced the supervisor review button's hardcoded validated status with `Logbook::SUPERVISOR_STATUS_VALIDATED`.
- Each item was committed separately after the full tests, Pint, and PHPStan passed. Test results progressed from **82 tests / 377 assertions** to **89 tests / 427 assertions**; all seven added render/feature tests pass.
- At that checkpoint, the chat-room/layout follow-up and pagination were left for later. The chat-room and shared-layout work are documented below; pagination remains deferred.

## Chat-room view map (before refactoring)

Target: `resources/views/partials/chat-room.blade.php` (1,741 lines at mapping time). This map is limited to the requested chat partial and its directly connected JavaScript, route names, and role-specific render wrappers. At the time this map was recorded, no markup, CSS, or JavaScript had been changed for this task.

### File sections

| Lines | Section | Responsibility |
|---|---|---|
| 1–22 | Blade data/config | Reads the explicit `$chat` array, derives contacts/role/conversation/tabs, and builds the client config. |
| 24–1,341 | Inline CSS | `.cm-*` chat styling, responsive states, message bubbles, composer, attachment tray, overlays, and root sizing. |
| 1,343–1,414 | Contacts/sidebar markup | `#cm-root`, `.cm-side`, search, `.cm-contacts`, contact rows, unread badges, note, and empty sidebar. |
| 1,416–1,421 | List-only placeholder | `.cm-ph` / `.cm-empty` shown when no conversation is selected. |
| 1,423–1,467 | Conversation header/status | `.cm-main`, `.cm-head`, partner/back/switch UI, `#cm-status`, `#cm-typing`, `#cm-offline`. |
| 1,468–1,493 | Message list | `#cm-list` / `.cm-list`, empty-state `#cm-empty`, `#cm-newpill`, and attachment tray `#cm-tray`. Message rows are not Blade markup; `chat.js` creates them in `buildBubble()`. |
| 1,495–1,610 | Composer and recording controls | `#cm-form`, message input/buttons, attachment and emoji menus, file inputs, and `#cm-rec` voice-recording controls. |
| 1,614–1,690 | Overlays | Delete sheet, image lightbox, camera overlay, and toast (`#cm-sheet`, `#cm-lb`, `#cm-cam`, `#cm-toast`). |
| 1,692–1,701 | Inline JSON data | Sets `window.CHAT`, `window.CONVERSATION_ID`, `window.CHAT_PARTNER_ROLE`, `window.CURRENT_USER`, and `window.CHAT_LIST` using `Js::from` / `@json`. |
| 1,703–1,741 | Inline root-height script | `setH()` and `fit()` size `#cm-root` relative to the nearest `main` and viewport; listeners use window/visualViewport/load/fonts/ResizeObserver. |

### JavaScript hooks and routes

- `resources/js/chat.js:10–21`, `fitRoot()` / `setupFit()`: takes the `#cm-root` node and adjusts its height on viewport resize.
- `resources/js/chat.js:24–89`, `initList()`: uses `.cm-side`, `.cm-contacts`, `[data-iso]`, `.cm-search input`, `.cm-contact`, `.cm-pv`, `.cm-ct-time`, `.cm-badge`, `.cm-row2`, and contact `data-student-id` / `data-conversation-id`. It reads `window.CHAT_LIST`; `bump()` may set a contact href from `CHAT_LIST.openBase`.
- `resources/js/chat.js:90–247`, `initChat()` setup and `buildBubble()`: reads `#cm-root`, `window.CHAT`, and message data; generates `.cm-row`, `.cm-me` / `.cm-them`, `.cm-avatar`, `.cm-bubble`, `.cm-img`, `.cm-audio`, `.cm-file`, `.cm-meta`, `.cm-tick`, `.cm-more`, `.cm-prog`, and related message-item classes.
- `resources/js/chat.js:249–280`, message-list helpers: `nearBottom()`, `scrollBottom()`, `ensureDay()`, `addMessage()`, `rebuild()`, `patchMessage()`, and `setTicks()` use `#cm-list`, `#cm-empty`, `#cm-newpill`, `.cm-day`, `.cm-row`, and `.cm-tick`.
- `resources/js/chat.js:283–386`, network/read helpers: `xhrSend()` and `sendMessage()` POST to `CHAT.urls.store`; `markRead()` POSTs to `CHAT.urls.read`; `catchUp()` GETs `CHAT.urls.since?after=...`. `headers()`, `applyReadUpTo()`, and the realtime handlers update message rows/read markers.
- `resources/js/chat.js:388–405`, status/typing: `paintStatus()` / `setTyping()` use `#cm-status`, `#cm-status-text`, and `#cm-typing`; `whisperTyping()` sends the Echo typing event for the conversation.
- `resources/js/chat.js:408–490`, composer/attachments: uses `#cm-input`, `#cm-form`, `#cm-send`, `#cm-mic`, `#cm-tray`, `#cm-menu`, `#cm-emoji`, `#cm-attach`, `#cm-emoji-btn`, `#cm-pick-*`, and `#cm-in-*`; generated preview chips use `.cm-chip`, `.cm-chip-info`, `.cm-chip-x`, and `.cm-chip-ic`.
- `resources/js/chat.js:491–547`, camera/voice recording: `openWebcam()` / `closeWebcam()` use `#cm-cam`, `#cm-cam-video`, `#cm-cam-close`, and `#cm-cam-snap`; recording uses `#cm-rec`, `#cm-rec-time`, `#cm-rec-cancel`, and `#cm-rec-send`.
- `resources/js/chat.js:548–579`, deletion/lightbox: uses `#cm-sheet`, `#cm-del-me`, `#cm-del-everyone`, `#cm-del-cancel`, `#cm-lb`, `#cm-lb-img`, `#cm-lb-open`, and `#cm-lb-close`; `postDelete()` POSTs to `CHAT.urls.deleteBase/{message}/{delete-for-me|delete-for-everyone}`.
- `resources/js/chat.js:583–600`, online/lifecycle: uses `#cm-offline`, `#cm-list`, and `#cm-newpill`; `boot()` calls `initChat()`.
- `resources/js/app.js:39–102`, realtime glue: `isCurrentUserMember()`, `getOtherParticipantRole()`, `otherParticipantIsOnline()`, `leaveRealtimeChannels()`, and `initRealtimeChannels()` consume `window.CURRENT_USER`, `window.CHAT_PARTNER_ROLE`, and `window.CONVERSATION_ID`, subscribe to Echo conversation channels, and dispatch chat events. Logout lookup uses `a[href="/logout"]`.

The four role-scoped API URLs are explicitly built in the partial at lines 15–18:

| Config key | URL pattern | Use |
|---|---|---|
| `store` | `url("/chat/{$role}/conversations/{$cid}/messages")` | `chat.js` POST for text, file, and voice messages. |
| `since` | Same messages URL | `chat.js` GET with `?after=<message id>` to catch up. |
| `read` | `url("/chat/{$role}/conversations/{$cid}/read")` | `chat.js` POST to mark messages read. |
| `deleteBase` | Same messages URL | `chat.js` appends `/{message}/delete-for-me` or `/{message}/delete-for-everyone` and POSTs. |

Those role-prefixed API routes are defined without route names in `routes/web.php:148–156`; the separately named, non-role-scoped equivalents are `chat.store`, `chat.since`, `chat.read`, `messages.delete.for.me`, and `messages.delete.for.everyone` (`routes/web.php:158–169`). The view itself does not call `route()` for these APIs. Page navigation names are `student.messages.index`, `student.messages.supervisor`, `lecturer.messages.index` / `lecturer.messages.show`, and `supervisor.messages.index` / `supervisor.messages.show`; the client contact-list update uses the controller-provided `href` and `CHAT_LIST.openBase`, not route names.

## Chat-room refactor outcome

- Added render characterization tests for student, lecturer, and supervisor conversation pages. Each checks the response, message list/composer, all 42 chat DOM IDs, core JS-facing classes, serialized message data, current role/user ID, partner role, and all four role-scoped API URLs. The existing chat controller tests continue to pass.
- Split the Blade message list, composer, attachment preview, and overlays into focused partials. These fragments have no dynamic inputs; existing parent-supplied chat data remains in the parent and is explicitly consumed by the message-item renderer.
- The message item is browser-generated rather than Blade-rendered. Moved `buildBubble()` and its audio renderer into `resources/js/chat/message-item.js`, with helper dependencies passed explicitly. Compared `cm-*` JavaScript hook tokens before and after; all 79 are preserved.
- Moved the inline CSS byte-for-byte into `resources/css/chat.css` and imported it from `resources/css/app.css`. The chat routes render through `layouts.app`, which loads the Vite app entries; `npm run build` succeeded with the new stylesheet in the app bundle.
- Replaced the scattered conversation globals with a small `window.CHAT_CONFIG` object serialized with `@json` (routes, role, current user ID, conversation and partner role) plus initial `window.CHAT_DATA` serialized with `Js::from`. Updated `chat.js` and `app.js` to consume that config; message sending, deleting, read state, and attachments were not intentionally changed.
- Kept the viewport-sizing script inline in `partials/chat-room/fit-script.blade.php` because the suite has no browser-level test for viewport behavior. It remains at the same render position.
- Line counts: the target Blade file went from **1,741** to **178** lines. The target plus its five focused Blade partials total **433** lines; the extracted stylesheet is **1,316** lines. The original 42 markup IDs and 79 JavaScript `cm-*` hooks are unchanged.
- Verification after the refactor: **92 tests passed, 616 assertions**; Pint, PHPStan, and Vite build passed. Existing chat controller tests are included in the passing suite.

### Needs human review: chat JavaScript

- The viewport script remains inline in its own partial until browser-level sizing behavior can be tested. The existing `chat.js` `fitRoot()` also writes `style.height`, while the chat CSS declares `height: var(--cm-fit-h, ...) !important` and the inline script sets `--cm-fit-h`. This looks like overlapping sizing logic and may make `fitRoot()` ineffective; it was not changed without browser verification.

## Shared layout map (before refactoring)

Target: `resources/views/layouts/app.blade.php` (1,365 lines at mapping time). This map is for the shared authenticated layout only. No layout or application code had been changed for this task when the map was recorded.

### File sections

| Lines | Section | Responsibility |
|---|---|---|
| 1–55 | Head and assets | Document metadata, CSRF token, title, conditional Vite/CSS CDN loading, and Font Awesome CDN. The Tailwind and Font Awesome CDN references are left unchanged. |
| 57–108 | Shell check and simulator bar | Determines whether the current path gets the role dashboard shell; renders the role-switch bar and `#sidebar-overlay`. |
| 109–318 | Role and navigation data | Selects the role guard/user, display and role labels, per-role `$navItems` and their route-match patterns, page/portal titles, current date, and dashboard URL. |
| 320–579 | Sidebar and navigation | Renders the user/company summary, menu label, `#sidebar-nav`, active-link styling, logout link, and sidebar footer. The active state uses `request()->is()` against each item’s `match` patterns. |
| 581–711 | Main header before notifications | Main scroll region, decorative header, mobile sidebar button, page/portal title, and date. |
| 713–1018 | Notification area and script | Role-scoped bell, badge, dropdown/list, footer link, and notification fetch/read-state behavior. Present only on student, lecturer, and supervisor paths. |
| 1020–1105 | Profile menu and script | User display name, role label, dashboard/logout links, and profile dropdown behavior. |
| 1110–1118 | Page-content shell | `#page-content` container and `@yield('content')`. |
| 1121–1157 | Sidebar JavaScript | Opens/closes the responsive sidebar and closes it on navigation, Escape, overlay click, or desktop resize. |
| 1160–1357 | Progress and asynchronous navigation JavaScript | Progress bar, spinner, same-portal page fetch/replacement, click interception, and history navigation. It hard-loads chat pages rather than replacing them. |
| 1358–1365 | Non-dashboard fallback | Renders page content without the dashboard shell. |

### Layout JavaScript hooks and routes

- Notification script (`776–1018`): `csrf()` reads `meta[name="csrf-token"]`; `setUnread()`, `refreshCount()`, `showMessage()`, `markRead()`, `buildRow()`, `loadList()`, `openPanel()`, and `closePanel()` operate on `#notif-wrap`, `#notif-bell`, `#notif-badge`, `#notif-panel`, `#notif-panel-list`, `#notif-panel-sub`, and `#notif-panel-readall`. It toggles `hidden`, `bg-blue-50/60`, `font-bold`, `text-slate-900`, `font-semibold`, `text-slate-700`, `truncate`, `whitespace-pre-line`, and `break-words`; generated notification rows are selected by `[data-notif-item]`.
- Notification URLs are built in Blade from the current first path segment and emitted in `data-count-url`, `data-list-url`, and `data-read-all-url`, plus the “View all notifications” href: `/{role}/notifications/unread-count`, `/{role}/notifications`, and `/{role}/notifications/read-all`. The JavaScript fetches those data attributes; it does not call named route helpers.
- Profile script (`1079–1105`): uses `#profile-wrap`, `#profile-btn`, and `#profile-menu`; toggles the `hidden` class and closes on outside click or Escape.
- Sidebar script (`1121–1157`): uses `#sidebar`, `#sidebar-overlay`, `#sidebar-toggle`, and `#sidebar-close`; toggles `-translate-x-full` and `hidden`, and closes on sidebar-link clicks, Escape, overlay click, or desktop resize.
- Async-navigation script (`1164–1357`): uses `#page-content`, `main`, and `#nav-progress`; creates/removes `#nav-spinner` dynamically. It replaces `#page-title` and `#sidebar-nav`, selects links with `#sidebar a[href]` and `#page-content a[href]`, and checks `[data-live-nav]`, `[data-no-ajax]`, and `[data-hard-load]`. It checks for `#cm-root` to force normal navigation on chat pages; `#cm-root` is rendered by the separately scoped chat partial, not by this layout.
- The simulator uses named routes `login.home` and `module.switch`. The layout contains no role-scoped chat URL construction; chat requests remain in the chat partial/client.

### CSS and existing view composer

- There are no literal `<style>` blocks in this layout. Inline style attributes are at lines `71`, `323`, `437`, and `1112`; the async-navigation script creates a keyframe style element and inline spinner styles at lines `1223–1255`. The remaining styling is in the existing utility-class markup. No CSS has been moved.
- `AppServiceProvider`’s `layouts.app` view composer currently provides `$supervisorName` and `$industryName`. On student paths it loads missing `lecturer` and `supervisor` relationships, then assigns the lecturer’s name to `$supervisorName` and the supervisor’s name to `$industryName`. It does not currently provide navigation data; role labels, navigation items, and page titles are prepared in the layout’s `@php` block.
- The audit’s layout line count and inline-block ranges are stale relative to this version: the current file ends at line 1,365, and its reported CSS ranges include JavaScript. The exact current script/style locations are recorded above.

## Shared layout refactor outcome

- Moved the per-role menu definitions to `app/View/LayoutNavigation.php` and made them available through the existing `layouts.app` view composer. The navigation markup is now `resources/views/components/layouts/app-navigation.blade.php`, receiving the menu items explicitly.
- Extracted the role-scoped notification UI to `resources/views/components/layouts/app-notification-area.blade.php`, with its role passed explicitly.
- Extracted the sidebar and header to `resources/views/components/layouts/app-sidebar.blade.php` and `resources/views/components/layouts/app-header.blade.php`. Their display names, role labels, relationship names, menu items, titles, date, and dashboard URL are passed as component props. The outer dashboard wrapper, main content container, and `#page-content` stay in the layout so the async navigation hooks remain in their original place.
- Isolated the four inline scripts into Blade partials under `resources/views/components/layouts/scripts/`. Their script blocks were checked byte-for-byte against the versions immediately before extraction. They remain inline rather than moving to Vite because the tests do not exercise browser behavior, and keeping the partials in the layout preserves behavior when no Vite manifest/hot file is present.
- No literal `<style>` block existed in the layout. No CSS was moved: the utility classes, safe-area/profile inline style attributes, and styles dynamically created by async navigation remain in place. Tailwind and Font Awesome CDN links were not changed.
- Added role-specific render tests for admin, student, lecturer, and supervisor: status 200; every role’s nav item labels/links and active state; notification markup and role URLs where present; profile name/role; student lecturer/supervisor names; and static JavaScript hook IDs. Admin notification markup remains absent, as before.
- The layout file went from **1,365 lines** to **255 lines** (1,110 fewer in the layout itself; markup is now in focused components/partials). The suite went from **92 tests / 616 assertions** before this task to **96 tests / 829 assertions**.
- After each code commit, the full test suite, Pint check, PHPStan, and Vite build passed. Final results: **96 tests passed, 829 assertions**; Pint, PHPStan, and `npm run build` passed. Vite emitted its existing optional `fontaine` optimization notice but built successfully.
- Chat-room views/assets, `.env`, `composer.json`, routes, and CDN references were not changed.

### Needs human review: shared layout assets

- The four layout scripts remain inline in dedicated Blade partials until browser-level behavior tests are available. The render tests protect their required markup/hooks but do not execute notification, profile, sidebar, or asynchronous navigation interactions in a browser.
- The existing inline style attributes and JavaScript-generated spinner styles remain unchanged; moving these to CSS without visual/browser verification was deferred.

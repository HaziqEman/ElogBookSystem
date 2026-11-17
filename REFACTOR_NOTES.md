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

## Confirmed bugs fixed separately

- Fresh databases lacked `logbooks.supervisor_status`, causing dashboard queries to fail.
- Admin dashboard analytics crashed when a student had no internship start date.
- Supervisor comments could not be persisted because `feedback` lacked `supervisor_id` and required `lecturer_id`. Added supervisor attribution and made lecturer attribution nullable; existing feedback rows remain valid.
- Fresh attendance schemas lacked the fields written by clock-in/day-off flows and had no `attendance_files` table, causing those writes to fail. Added additive migration `2026_11_17_000003_add_student_attendance_detail_support.php`; existing migrations were not edited.
- Attendance date lookups used exact string equality against date-cast values, causing duplicate clock-ins, clock-outs, and day-off conflicts to fail on SQLite. Updated the attendance controller's date filters to use date-aware comparisons and added focused regression coverage.
- Fresh message schemas lacked the `type` and attachment metadata columns used by chat message creation. Added additive migration `2026_11_17_000004_add_chat_attachment_support_to_messages.php`; existing migrations were not edited.
- Deleting a chat message only for yourself incorrectly removed its shared Cloudinary attachment, while delete-for-everyone left the asset behind. Moved attachment cleanup to the delete-for-everyone path and added regression tests.

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
- Added [phpstan.neon](./phpstan.neon) at level 5 for `app`, including Larastan and [phpstan-baseline.neon](./phpstan-baseline.neon). The generated baseline contains 136 ignore entries for existing findings; `vendor/bin/phpstan analyse --no-progress` passes with the baseline.
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
- Next controller: `ChatController` and `MessageDeleteController`.

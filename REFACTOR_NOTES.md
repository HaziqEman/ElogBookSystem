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

- `AdminStudentController` combines listing/filtering, bulk actions, student CRUD, and internship calculations; it is a high-impact candidate for carefully separated requests/actions.
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

## Cleanup completed

- Applied Pint to application code, config, routes, scripts, tests, factories, and seeders. Existing migrations and the duplicated `export_context` snapshot were deliberately excluded.
- Extracted supervisor review validation into a Form Request with unchanged rules and error text.
- Verification after cleanup: **35 tests, 35 passed, 155 assertions**; Pint check passed on changed supervisor-review files.

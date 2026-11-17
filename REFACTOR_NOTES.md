# Refactor audit

## Phase 0: baseline

- Worktree was clean on `refactor/best-practices`; work is being done on `refactor/simplify-cleanup`.
- Baseline command: `php artisan test`.
- Baseline result: **33 tests, 21 passed, 12 failed, 101 assertions**.
- No production code has been changed during the audit.

### Baseline failures to resolve or review before behavior-sensitive refactors

- `AdminDashboardTest::test_admin_dashboard_renders_enhanced_analytics_sections` and `DummyDataSeederTest::test_seeded_accounts_can_open_role_dashboards` fail because migrated `logbooks` has no `supervisor_status` column, although application code queries and writes it. The existing logbook migration should not be edited; assess a new migration.
- `AdminStudentsPageTest::test_admin_students_page_renders`, `DashboardUiTest::test_student_dashboard_renders_new_portal_shell`, and all three `LogbookAiHelpTest` cases make requests to routes protected by role authentication without signing in as that role.
- Three lecturer dashboard assertions fail because their expected dashboard text does not match the current rendered output. Inspect and update only if the intended current UI behavior is clear.
- `DashboardUiTest::test_student_dashboard_shows_updated_status_after_lecturer_review` posts to `/lecturer/logbook/{id}/review`, for which no route exists. The current review flow is implemented for company supervisors; do not add a lecturer review route without human confirmation.
- `LogbookPdfExportTest::test_student_lecturer_and_assigned_supervisor_can_export_logbooks` expects an old deployment-host URL rather than the current named route.

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
- Confirm intended behavior for lecturer review before changing its stale route test or adding a route.
- Review generated exports, deployment host references, and the large Blade templates manually before deleting or moving code.
- Avoid migrations unless a fresh-schema failure confirms the missing field is required; do not edit deployed migrations.

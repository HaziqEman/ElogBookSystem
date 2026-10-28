<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Local dummy data for eLogBook development

The opt-in `dummy:seed` command creates demo role accounts and related internship, attendance, task, feedback, attachment-placeholder, conversation, message, and (when installed) reminder-notification records. It does not call Pusher, Firebase, Cloudinary, email, Gemini, or other external services. Message model events are suppressed while seeding.

### Safety and setup

Use a local or dedicated non-production database. The seeding and cleanup commands refuse `APP_ENV=production` and also require `DUMMY_DATA_SEEDING_ENABLED=true`. Set that variable only in the environment you intend to populate. The default is disabled. Before applying any pending migration or seeding Neon, compare the migration status with the actual branch schema in the Neon console. Do not mark a migration complete just because a table exists, and do not run migrations against a protected branch without reviewing their effects.

```text
DUMMY_DATA_SEEDING_ENABLED=true
```

For PostgreSQL, first confirm the exact host and database belong to your intended development branch. The command requires both values as explicit confirmations and performs a read-only database/schema check before it starts its fixture transaction. The example below uses the endpoint configured in the local development setup at the time this documentation was written; verify it in Neon before using it:

```bash
php artisan dummy:seed --expect-host=YOUR_CONFIRMED_DEVELOPMENT_HOST --expect-database=elogbooksys --yes
```

Counts can be changed per command (each count is limited to 0–100; weeks to 1–52):

```bash
php artisan dummy:seed --expect-host=YOUR_CONFIRMED_DEVELOPMENT_HOST --expect-database=elogbooksys --admins=1 --lecturers=3 --supervisors=3 --students=15 --weeks=16 --yes
```

For Laragon, use a PHP build with libpq 14 or later for Neon SNI. If `php -v` resolves to a different build, invoke the intended Laragon executable explicitly or select it in Laragon before running the command. Never add credentials to command arguments.

The generator is repeatable: it reuses only rows whose exact IDs are in its ledger, adds missing fixtures, and never changes the password/profile of an existing registered fixture. If a fixture identity exists without a ledger entry, it stops rather than taking ownership. Lowering a count does not delete accounts; use the cleanup command to remove the whole fixture set.

### Demo login credentials

The default shared password is `DummyPass2026!`. Accounts are created as needed, starting at index 001:

| Role | Example login | Default count |
| --- | --- | ---: |
| Admin | `seed-admin-001@example.test` | 1 |
| Lecturer | `seed-lecturer-001@example.test` | 2 |
| Company supervisor | `seed-supervisor-001@example.test` | 2 |
| Student | `seed-student-001@example.test` | 8 |

These credentials are intentionally public and must only be used in a development/test database. To select another shared password, set `DUMMY_DATA_PASSWORD` in the local environment before invoking the command; do not put passwords in command arguments or shell history. Existing registered fixture accounts are not modified on rerun.

### Safe cleanup

```bash
php artisan dummy:cleanup --yes
```

Cleanup deletes only the exact model/table IDs recorded in `dummy_data_records`, in dependency order, inside a transaction. Before deleting, it aborts if any parent has dependent rows not recorded by this fixture system. It never deletes by name/domain and never truncates tables. The ledger remains empty after successful cleanup. Do not manually repurpose seeded demo accounts or attach real users' records to them; cleanup will intentionally stop if it detects unregistered dependents.

### Tests and supported-schema limits

Run the focused, isolated SQLite tests with:

```bash
php artisan test --filter=DummyDataSeederTest
```

Tests use the `testing` environment configured in `phpunit.xml` and an in-memory SQLite database. Optional fields and tables are seeded only when present. The repository does not define a separate companies table; company names are stored on supervisor profiles. The base migrations do not include every internship schedule, supervisor-review, notification, or extended attendance field referenced by newer application code, so those dashboard sections are populated only when the columns/tables exist. Attachment rows point to clearly named placeholder paths; no physical file or Cloudinary asset is created.

Database fixtures do not prove that Pusher authorization/realtime delivery, Firebase push delivery, Cloudinary uploads, email sending, or Gemini requests work. Verify those integrations manually in a non-production environment with their own credentials and test endpoints.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Composer dependency audit

## Scope and method

- Audited the locked Composer dependencies on 2026-11-17 using Composer 2.11.2 and `composer audit --format=json`.
- The machine-installed Composer 2.2.24 does not provide the `audit` command. The audit used a checksum-verified Composer PHAR in a temporary directory; the machine-wide Composer installation was not changed.
- This is a package-advisory inventory, not confirmation that each issue is exploitable through this application.

## Baseline results before updates

Composer reported **39 advisories across 10 locked packages**: 1 critical, 17 high, 10 medium, 5 low, and 6 without a severity assigned by the advisory feed.

| Package | Locked version | Advisory count | Severity breakdown | Summary |
|---|---:|---:|---|---|
| `dompdf/dompdf` | `v3.1.5` | 6 | 4 medium, 2 low | SVG file disclosure/read, image resource exhaustion, file-existence oracle, and chroot validation issues. |
| `laravel/framework` | `v13.16.1` | 4 | 1 critical, 1 medium, 2 low | Includes TokenGuard authentication bypass, relationship-inclusion data destruction, task output injection, and debug-page XSS advisories. |
| `league/commonmark` | `2.8.2` | 12 | 9 high, 3 medium | Includes denial-of-service advisories and an `AttributesExtension` XSS advisory. |
| `league/flysystem` | `3.34.0` | 1 | 1 low | Path-normalization check can be bypassed with malformed UTF-8. |
| `maatwebsite/excel` | `3.1.69` | 1 | 1 high | A caller-controlled export path can write outside the configured filesystem disk. |
| `phpoffice/phpspreadsheet` | `1.30.5` | 7 | 6 high, 1 medium | Includes SSRF, XXE, and CPU/memory exhaustion advisories. |
| `symfony/http-foundation` | `v7.4.13` | 5 | 1 high, 4 unrated | Includes proxy trust, access-control method, URL-host, and request-header advisories. |
| `symfony/http-kernel` | `v7.4.13` | 1 | 1 unrated | Untrusted forwarded headers can influence generated request URLs. |
| `symfony/mime` | `v7.4.13` | 1 | 1 medium | Email header injection advisory. |
| `symfony/routing` | `v7.4.13` | 1 | 1 unrated | Host placeholders can affect path-based access-control matching. |

## Remediation results

The following lockfile-only updates were applied in separate commits, staying within the existing major-version constraints. The Laravel framework and its required `laravel/prompts` update were handled together.

| Package | Before | After |
|---|---:|---:|
| `laravel/framework` | `v13.16.1` | `v13.43.0` |
| `laravel/prompts` | `v0.3.18` | `v0.3.27` |
| `symfony/http-foundation` | `v7.4.13` | `v7.4.23` |
| `symfony/http-kernel` | `v7.4.13` | `v7.4.23` |
| `symfony/mime` | `v7.4.13` | `v7.4.22` |
| `symfony/routing` | `v7.4.13` | `v7.4.23` |
| `phpoffice/phpspreadsheet` | `1.30.5` | `1.30.8` |
| `maatwebsite/excel` | `3.1.69` | `3.1.70` |
| `dompdf/dompdf` | `v3.1.5` | `v3.1.6` |
| `league/commonmark` | `2.8.2` | `2.10.7` |
| `league/flysystem` | `3.34.0` | `3.36.0` |

After the updates, Composer 2.11.2 reports **0 advisories across 0 packages**. No major-version upgrades were needed, and no advisories remain to defer for human review. Each package group passed the test suite (96 tests, 829 assertions), Pint, PHPStan, the Vite build, and a Composer audit after commit. `composer.json` and `.env` were not changed.

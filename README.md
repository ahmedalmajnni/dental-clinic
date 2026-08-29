<div align="center">

# 🦷 Dental Clinic Management System

**A multi-branch dental clinic manager — patients, appointments, doctor availability,
treatments with integrated billing, payments, lab cases and media.**

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14%2B-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](#license)

</div>

---

Built with **PHP · Laravel 13 · Blade · PostgreSQL**. Server-rendered, no JavaScript
framework and no build step — the whole interface is Blade plus one stylesheet.

## Table of contents

- [Features](#features)
- [Screenshots](#screenshots)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database schema](#database-schema)
- [Authentication & roles](#authentication--roles)
- [Billing model](#billing-model)
- [Project structure](#project-structure)
- [Common commands](#common-commands)
- [Troubleshooting](#troubleshooting)
- [Design notes & gotchas](#design-notes--gotchas)
- [License](#license)

---

## Features

**Accounts & access**

- One `account` login table for everyone (admin / employee / patient), passwords hashed with bcrypt.
- Patients self-register. Staff sign-up is **invite-only** — there is no public link; send a new
  colleague to `/staff-register`, then approve them under **Accounts → Staff requests**. They
  cannot log in until approved, and cannot give themselves the `admin` job title.
- Role-based screens enforced by middleware: admin, employee and patient each get a different app.

**Scheduling**

- Appointments with calendar-style scheduling across branches.
- **Doctor availability** — each doctor defines their own weekly working hours and slot length,
  plus date-specific exceptions (days off, partial blocks, one-off extra hours). Booking screens
  offer only genuinely free slots, and the rules are enforced server-side.
- **Patient requests** — patients ask for an appointment; staff confirm it into a real slot or
  decline with a note the patient can see.

**Clinical**

- Patients, clinical reports and treatments.
- Lab cases (crowns, bridges, dentures…) tracked from received → delivered.
- Media: x-rays, scans and photos linked to a patient.

**Billing (integrated)**

- Saving a **treatment** automatically creates an **invoice line** on the patient's open invoice.
- **Payments** auto-allocate across a patient's unpaid invoices (oldest first); overpayment
  becomes account credit.
- Invoices track `total` / `balance` / `status` (open / partial / paid), always recalculated
  from lines and payments.

---

## Screenshots

> _Add screenshots here — e.g. `docs/screenshots/dashboard.png` — and link them:_
>
> `![Staff dashboard](docs/screenshots/dashboard.png)`

---

## Tech stack

| Layer | Choice |
|---|---|
| Language | PHP 8.3+ |
| Framework | Laravel 13 |
| Templating | Blade (server-rendered) |
| Database | PostgreSQL — 16 tables, UUID primary keys |
| Auth | Laravel session auth on a custom `account` model |
| Sessions | File driver (see [gotchas](#design-notes--gotchas)) |
| Styling | Plain CSS in `public/css/style.css` — no build step |

---

## Requirements

| Thing | Version | Why |
|---|---|---|
| **PHP** | **8.3+** (8.4 / 8.5 fine) | The language the app runs on. |
| **Composer** | 2+ | Installs Laravel and the other PHP libraries into `vendor/`. |
| **PostgreSQL** | **14+** | The schema is PostgreSQL-specific — MySQL and SQLite will *not* work. |
| Node.js | — | **Not required.** See below. |

Laravel itself is **not** downloaded separately — `composer install` fetches it.

**Required PHP extensions.** The app will not boot without these:

```
pdo_pgsql   pgsql     openssl   mbstring
curl        fileinfo  zip       tokenizer
xml         ctype     json
```

Check yours with `php -m`. On Windows they are enabled by removing the `;` in front of the
matching `extension=` line in `php.ini`.

**You do not need Node.** The interface is one hand-written stylesheet with no build step.
`package.json` (Vite + Tailwind) is left over from the Laravel starter and is referenced only by
`resources/views/welcome.blade.php`, which this app never routes to. Skip `npm install`.

---

## Installation

```bash
# 1. Get the code
git clone <your-repo-url>
cd laravel

# 2. Install PHP dependencies
composer install

# 3. Create your environment file and app key
cp .env.example .env
php artisan key:generate

# 4. Create the database (PostgreSQL must already be running)
createdb -U postgres dental_clinic

# 5. Point .env at it (see Configuration), then create the tables
php artisan migrate

# 6. Create logins — pick ONE:
php artisan db:seed --class=AdminSeeder      # just an admin
php artisan db:seed --class=DemoDataSeeder   # a full sample clinic

# 7. Run it
php artisan serve
```

Open **<http://127.0.0.1:8000>**.

| Seeder | Creates | Password |
|---|---|---|
| `AdminSeeder` | `admin@clinic.local` | `admin123` |
| `DemoDataSeeder` | branches, doctors, patients, appointments, invoices, payments | `password123` |

`AdminSeeder` is safe to run more than once, and honours `ADMIN_EMAIL`, `ADMIN_PASSWORD`,
`ADMIN_NAME` and `BRANCH_NAME` from `.env`.

> **Change the admin password before deploying anywhere real.**

**Notes**

- `php artisan serve` includes its own web server, so **Apache is not required**. To use
  Apache or nginx instead, point the document root at `public/` — never at the project root.
- The schema needs PostgreSQL's **pgcrypto** extension for `gen_random_uuid()`. The migration
  enables it automatically, but the database user must be allowed to create extensions
  (`postgres` is, by default).
- If you have more than one PHP installed, make sure `php` is the one you configured —
  check `php -v` and `php -m`, and call the full path if they disagree.
- Running a local, portable PostgreSQL with no service? See **[RUN.md](RUN.md)** and the
  `start-postgres.ps1` helper.

---

## Configuration

Database connection lives in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1        # or your cloud host
DB_PORT=5432
DB_DATABASE=dental_clinic
DB_USERNAME=postgres
DB_PASSWORD=postgres

SESSION_DRIVER=file      # see gotchas
```

---

## Database schema

16 tables with UUID primary keys. The core 13 are created verbatim from
`database/sql/dental_clinic_schema.sql` by a single migration — so the schema matches the
original design exactly, CHECK constraints and indexes included. Later migrations add
`appointment_request`, `doctor_availability` and `doctor_time_off`.

```
branch ─┬─ employee ──┬─ account (employee login)
        │             ├─ doctor_availability / doctor_time_off
        │             └─ appointment ─┬─ report
        │                             └─ treatment ── invoice_line ── invoice ──┐
        └─ media                                                                │
patient ─┬─ account (patient login)                          payment ── payment_allocation
         ├─ appointment / appointment_request / report
         ├─ treatment / media / lab_case
         └─ invoice / payment
```

Every `account` belongs to **exactly one** employee **or** one patient, enforced by a CHECK
constraint. Admins are employees with `job_title = 'admin'`.

---

## Authentication & roles

- The auth provider points at `App\Models\Account` (`config/auth.php`), not the default `User`.
  `Account::getAuthPassword()` returns the `password_hash` column.
- Three roles: `admin`, `employee`, `patient`.
- Routes are guarded by the `role` middleware:

```php
Route::middleware('role:admin')->group(...);            // admin only
Route::middleware('role:admin,employee')->group(...);   // staff
```

| Area | admin | employee | patient |
|---|:---:|:---:|:---:|
| Branches / Employees / Accounts | ✅ | — | — |
| Patients / Appointments / Reports / Treatments / Lab / Media | ✅ | ✅ | — |
| Invoices / Payments | ✅ | ✅ | — |
| Doctor availability | ✅ all | own only | — |
| Own dashboard (bills, appointments, treatments) | — | — | ✅ |

Staff are further split by `employee.job_title` (`admin` / `doctor` / `reception` / `lab_tech`).
A doctor sees only their own appointments and requests; admin and reception see everything.

---

## Billing model

The money logic lives in one place — `app/Services/Billing.php` — so charges and payments can
never disagree.

- **Charge:** a `treatment` creates an `invoice_line` on the patient's open invoice (in a DB
  transaction), then the invoice is recalculated.
- **Payment:** money received, recorded independently, then **allocated** to invoices.
- **Allocation:** `payment_allocation` links part of a payment to an invoice — so one payment
  can cover several bills, and one bill can be paid by several payments.
- **Recalculation:** `balance = total − paid`; status becomes `paid` / `partial` / `open`.
  Allocations are capped, so you can never over-pay a bill or over-spend a payment.

---

## Project structure

```
app/
  Http/Controllers/          One controller per feature (Auth, Dashboard, Branch, …)
  Http/Middleware/           RoleMiddleware.php  (the role: alias)
  Models/                    Eloquent models (UUID keys, no updated_at)
  Services/Billing.php       Invoice/payment math (single source of truth)
  Services/AvailabilityService.php   Doctor slot generation
config/auth.php              Auth provider → Account model
database/
  migrations/                Default tables + the SQL-schema migration + later features
  sql/                       dental_clinic_schema.sql (the canonical schema)
  seeders/                   AdminSeeder (minimal) and DemoDataSeeder (full sample clinic)
  dump/                      pg_dump of a populated demo database
resources/views/             Blade templates, one folder per feature
routes/web.php               All routes, grouped by role middleware
public/css/style.css         The entire stylesheet
RUN.md                       Day-to-day run steps for a local portable Postgres
start-postgres.ps1           Starts that local Postgres (Windows)
```

<details>
<summary>Origin — this is a Laravel port of an earlier Node.js/Express prototype</summary>

The data model and behaviour are identical; only the implementation changed.

| Node.js prototype | Laravel |
|---|---|
| `src/routes/*.js` | `app/Http/Controllers/*Controller.php` + `routes/web.php` |
| `src/billing.js` | `app/Services/Billing.php` |
| `src/middleware/auth.js` | `app/Http/Middleware/RoleMiddleware.php` |
| `src/views/**/*.ejs` | `resources/views/**/*.blade.php` |
| `src/db.js` | Eloquent models + `config/database.php` |
| `scripts/seed-admin.js` | `database/seeders/AdminSeeder.php` |

</details>

---

## Common commands

```bash
php artisan serve                         # run the dev server
php artisan migrate                       # apply migrations
php artisan migrate:status                # which migrations have run
php artisan migrate:fresh                 # drop everything and re-migrate
php artisan db:seed --class=AdminSeeder   # (re)create the admin login
php artisan route:list --except-vendor    # list all routes
php artisan config:clear                  # clear cached config after .env edits
php artisan view:clear                    # clear compiled Blade templates
php artisan tinker                        # interactive shell against the app
```

---

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| `Call to undefined function Illuminate\Encryption\openssl_cipher_iv_length()` | The `openssl` extension is not loaded. A stock XAMPP `php.ini` ships with **every** extension commented out and **no `extension_dir`**, so PHP searches a folder that does not exist. Set `extension_dir` to your `php/ext` folder and uncomment the extensions under [Requirements](#requirements). |
| `SQLSTATE[08006] … Connection refused` | PostgreSQL is not running. A portable install has no service and will **not** restart after a reboot. |
| `could not find driver` | `pdo_pgsql` / `pgsql` are not enabled in `php.ini`. |
| `No application encryption key has been specified` | Run `php artisan key:generate`. |
| Extensions still missing after editing `php.ini` | You edited a different PHP's ini. Check with `php -i \| grep "Loaded Configuration"` (`findstr` on Windows). |
| `syntax error, unexpected token "endif"` pointing at `storage/framework/views/…` | A Blade directive is glued to the preceding word (`Dentist@if(...)`). Blade only recognises `@if` after a non-word character — put a space before the `@`, or use an inline ternary. Map the compiled file back to its source via the `PATH … ENDPATH` comment at the end of it. |
| A CSS change does not appear | Hard-refresh. The stylesheet is cache-busted by file timestamp, so this should be rare. |

Runtime errors are logged to `storage/logs/laravel.log`.

---

## Design notes & gotchas

- **Sessions use the `file` driver.** Laravel's default `sessions` table stores `user_id` as a
  `BIGINT`, but account IDs here are UUIDs, which breaks DB-backed sessions. The file driver
  avoids this and still persists across restarts. To use database sessions, alter
  `sessions.user_id` to `varchar`/`uuid` first.
- **UUID primary keys.** Models use Laravel's `HasUuids` trait. Because the tables have only
  `created_at` and no `updated_at`, every model sets `$timestamps = false`.
- **Deactivation takes effect immediately.** An `active` middleware re-checks the account on
  every request, so disabling someone logs them out on their next click rather than at their
  next login.
- **No build step.** Editing `public/css/style.css` is all that styling requires.

---

## License

Released under the [MIT License](https://opensource.org/licenses/MIT).

# 🦷 Dental Clinic Management System (Laravel)

A multi-branch dental clinic management system: accounts & login, patients,
appointments, clinical reports, treatments with **integrated billing**, payments,
lab cases, and media (x-rays/scans/photos).

Built with **PHP 8.3 · Laravel 13 · Blade · PostgreSQL**. This is the Laravel port
of an earlier Node.js/Express prototype; the data model and behaviour are identical.

---

## Table of contents
- [Features](#features)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Database schema](#database-schema)
- [Authentication & roles](#authentication--roles)
- [Billing model](#billing-model)
- [Project structure](#project-structure)
- [Common commands](#common-commands)
- [Design notes & gotchas](#design-notes--gotchas)
- [Default login](#default-login)

---

## Features

**Accounts & access**
- Single `account` login table for everyone (admin / employee / patient), passwords hashed with bcrypt.
- Patients self-register; admins create staff logins (no public staff signup).
- Role-based screens enforced by middleware — admin, employee and patient each see a different app.

**Clinical**
- Patients, appointments (calendar-style scheduling), clinical reports, treatments.
- Lab cases (crowns, bridges, dentures…) tracked from received → delivered.
- Media: link x-rays, scans and photos to a patient.

**Billing (integrated)**
- Saving a **treatment** automatically creates an **invoice line** on the patient's open invoice.
- Record **payments** that auto-allocate across a patient's unpaid invoices (oldest first); overpayment becomes account credit.
- Invoices track `total` / `balance` / `status` (open / partial / paid), always recalculated from lines + payments.

---

## Tech stack

| Layer | Choice |
|---|---|
| Language | PHP 8.3 |
| Framework | Laravel 13 |
| Templating | Blade (server-rendered) |
| Database | PostgreSQL (13 tables, UUID primary keys) |
| Auth | Laravel session auth on a custom `account` model |
| Sessions | File driver (see [gotchas](#design-notes--gotchas)) |
| Styling | Plain CSS in `public/css/style.css` (no build step) |

---

## Requirements

- **PHP 8.3+** with extensions: `pdo_pgsql`, `pgsql`, `openssl`, `mbstring`, `curl`, `fileinfo`, `zip`
- **Composer 2+**
- **PostgreSQL 14+**

---

## Quick start

### On a normal machine (recommended for the team)

```bash
# 1. Install PHP dependencies
composer install

# 2. Create your environment file and app key
cp .env.example .env
php artisan key:generate

# 3. Point .env at your PostgreSQL database (see Configuration below), then:
php artisan migrate                       # creates all tables
php artisan db:seed --class=AdminSeeder   # creates the first admin login

# 4. Run it
php artisan serve
# open http://127.0.0.1:8000
```

### On the original Windows dev machine

That machine uses a **portable PostgreSQL** (no Windows service) because its
network blocks Postgres's port 5432 to the internet. See **[RUN.md](RUN.md)** for
the exact start commands and the `start-postgres.ps1` helper.

---

## Configuration

Database connection lives in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1        # or your Neon / cloud host
DB_PORT=5432
DB_DATABASE=dental_clinic
DB_USERNAME=postgres
DB_PASSWORD=postgres

SESSION_DRIVER=file      # see gotchas
```

The admin seeder honours these optional variables:
`ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`, `BRANCH_NAME`.

---

## Database schema

13 tables, created verbatim from `database/sql/dental_clinic_schema.sql` via a
single migration (so the schema is identical to the original design — UUID
defaults, CHECK constraints and indexes included).

```
branch ─┬─ employee ──┬─ account (employee login)
        │             └─ appointment ─┬─ report
        │                             └─ treatment ── invoice_line ── invoice ──┐
        └─ media                                                                │
patient ─┬─ account (patient login)                          payment ── payment_allocation
         ├─ appointment / report / treatment / media / lab_case
         └─ invoice / payment
```

Every `account` belongs to **exactly one** employee **or** one patient (enforced
by a CHECK constraint). Admins are employees with `job_title = 'admin'`.

---

## Authentication & roles

- The auth provider points at `App\Models\Account` (`config/auth.php`), not the
  default `User`. `Account::getAuthPassword()` returns the `password_hash` column.
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
| Own dashboard (bills, appointments, treatments) | — | — | ✅ |

---

## Billing model

The money logic lives in one place — `app/Services/Billing.php` — so charges and
payments can never disagree.

- **Charge:** a `treatment` creates an `invoice_line` on the patient's open invoice (in a DB transaction), then the invoice is recalculated.
- **Payment:** money received, recorded independently, then **allocated** to invoices.
- **Allocation:** `payment_allocation` links part of a payment to an invoice — so one payment can cover several bills, and one bill can be paid by several payments.
- **Recalculation:** `balance = total − paid`; status becomes `paid` / `partial` / `open` accordingly. Allocations are capped so you can never over-pay a bill or over-spend a payment.

---

## Project structure

```
app/
  Http/Controllers/     One controller per feature (Auth, Dashboard, Branch, …)
  Http/Middleware/       RoleMiddleware.php  (the role: alias)
  Models/                13 Eloquent models (UUID keys, no updated_at)
  Services/Billing.php   Invoice/payment math (single source of truth)
config/auth.php          Auth provider → Account model
database/
  migrations/            Default tables + one migration that runs the SQL schema
  sql/                   dental_clinic_schema.sql (the canonical schema)
  seeders/AdminSeeder.php
resources/views/         Blade templates (layouts/, auth/, dashboard/, one folder per feature)
routes/web.php           All routes + role middleware groups
public/css/style.css     Styling
RUN.md                   How to run on the original dev machine
start-postgres.ps1       Starts the local Postgres (Windows dev machine)
```

### Where each Node.js file went

| Node.js prototype | Laravel |
|---|---|
| `src/routes/*.js` | `app/Http/Controllers/*Controller.php` + `routes/web.php` |
| `src/billing.js` | `app/Services/Billing.php` |
| `src/middleware/auth.js` | `app/Http/Middleware/RoleMiddleware.php` |
| `src/views/**/*.ejs` | `resources/views/**/*.blade.php` |
| `src/db.js` | Eloquent models + `config/database.php` |
| `scripts/seed-admin.js` | `database/seeders/AdminSeeder.php` |

---

## Common commands

```bash
php artisan serve                         # run the dev server
php artisan migrate                       # apply migrations
php artisan migrate:fresh                 # drop everything and re-migrate
php artisan db:seed --class=AdminSeeder   # (re)create the admin login
php artisan route:list --except-vendor    # list all routes
php artisan config:clear                  # clear cached config after .env edits
```

---

## Design notes & gotchas

- **Sessions use the `file` driver.** Laravel's default `sessions` table stores
  `user_id` as a `BIGINT`, but our account IDs are UUIDs, which breaks DB-backed
  sessions. The file driver avoids this and still persists across restarts. To use
  database sessions instead, alter `sessions.user_id` to `varchar`/`uuid`.
- **UUID primary keys.** Models use Laravel's `HasUuids` trait. Because the tables
  have only `created_at` (no `updated_at`), every model sets `$timestamps = false`.
- **Timezones.** Appointment times are stored and displayed consistently through
  Carbon, so what you enter is what you see (no UTC-offset surprises).
- **Port 5432 on the original machine.** That PC's network blocks Postgres's port
  to the internet, so it runs a local database. A normal network can point `.env`
  straight at a hosted PostgreSQL (e.g. Neon).

---

## Default login

```
Email:    admin@clinic.local
Password: admin123
```

Change it after first login (Accounts screen), or set `ADMIN_PASSWORD` before
seeding. Patients register themselves at `/signup`.

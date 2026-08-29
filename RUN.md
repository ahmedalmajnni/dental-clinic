# Dental Clinic — Laravel version

The app converted from the Node.js prototype to **PHP 8.3 + Laravel 13 + Blade**,
using **PostgreSQL** (the same 13-table schema).

Admin login: **admin@clinic.local** / **admin123**

---

## Running it on THIS machine

This machine uses a **portable PostgreSQL** (no Windows service), so start the
database first, then the web app.

### 1. Start the database (once per reboot)
```powershell
powershell -File ".\start-postgres.ps1"
```
You should see `127.0.0.1:5432 - accepting connections`.

The script looks for the portable PostgreSQL under your own user folder
(`%USERPROFILE%\pgsql\bin` and `%USERPROFILE%\pgdata`). If yours lives elsewhere, pass the
paths in: `powershell -File ".\start-postgres.ps1" -Bin "D:\pgsql\bin" -Data "D:\pgdata"`.

### 2. Start the web app
```powershell
php artisan serve
```
Then open http://127.0.0.1:8000

> **Use the right PHP.** This machine has more than one PHP installed and the app runs on
> **XAMPP's** (`C:\xampp\php\php.exe`, currently 8.5), which is *not* first on `PATH`. If a bare
> `php artisan` gives you missing-extension or database-driver errors, call it explicitly:
>
> ```powershell
> C:\xampp\php\php.exe artisan serve
> ```
>
> Confirm which ini is in effect with `php -i | findstr "Loaded Configuration"`.

### Useful commands
```powershell
php artisan migrate                      # apply database schema
php artisan db:seed --class=AdminSeeder  # (re)create the admin login
php artisan route:list --except-vendor   # list all routes
```

---

## Running it on a TEAMMATE's machine (or against Neon)

Laravel's database connection is just configuration. In `.env` set:
```
DB_CONNECTION=pgsql
DB_HOST=...        # e.g. your Neon host
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```
Then: `composer install`, `php artisan key:generate`, `php artisan migrate`,
`php artisan db:seed --class=AdminSeeder`, `php artisan serve`.

> Note: this PC's network blocks PostgreSQL's port 5432 to the internet, which is
> why we run a local database here instead of Neon. A normal network can point
> `.env` straight at Neon.

---

## How the pieces map from the Node app

| Node.js app | Laravel app |
|---|---|
| `src/routes/*.js` | `app/Http/Controllers/*Controller.php` + `routes/web.php` |
| `src/billing.js` | `app/Services/Billing.php` |
| `src/middleware/auth.js` (requireRole) | `app/Http/Middleware/RoleMiddleware.php` (`role:` alias) |
| `src/views/**/*.ejs` | `resources/views/**/*.blade.php` |
| `db.js` (pg pool) | Eloquent models in `app/Models/` + `config/database.php` |
| `scripts/seed-admin.js` | `database/seeders/AdminSeeder.php` |

Session storage uses the **file** driver (`SESSION_DRIVER=file`) because Laravel's
default `sessions.user_id` column is a BIGINT and our account IDs are UUIDs.

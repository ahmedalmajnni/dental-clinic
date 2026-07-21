# 🦷 Dental Clinic Management System — Start Here

Everything you need is in this folder: the **Laravel framework** (`vendor/` is already
included, so you do **not** need Composer), the **backend**, the **frontend** (Blade
views + CSS), and a **database dump** with demo data.

---

## 1. Install the two things you need

| Software | Why | Where |
|---|---|---|
| **PHP 8.2 or newer** | runs the application | https://windows.php.net/download (or `winget install PHP.PHP.8.3`) |
| **PostgreSQL 14+** | the database | https://www.postgresql.org/download/ |

After installing PHP, make sure these extensions are enabled in your `php.ini`:

```ini
extension=pdo_pgsql
extension=pgsql
extension=openssl
extension=mbstring
extension=curl
extension=fileinfo
```

Check it worked:
```bash
php -v
php -m
```

---

## 2. Create the database

```bash
createdb -U postgres dental_clinic
```
(or create a database named `dental_clinic` using pgAdmin)

---

## 3. Load the data

This restores all tables **and** the demo data (patients, appointments, invoices, etc.):

```bash
psql -U postgres -d dental_clinic -f database/dump/dental_clinic.sql
```

> **Prefer to start empty instead?** Skip the line above and run:
> ```bash
> php artisan migrate
> php artisan db:seed --class=AdminSeeder
> php artisan db:seed --class=DemoDataSeeder
> ```

---

## 4. Check your settings

Open the `.env` file and make sure the database section matches your machine:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dental_clinic
DB_USERNAME=postgres
DB_PASSWORD=postgres      # <-- change to your own PostgreSQL password
```

If there is no `.env` file, copy `.env.example` to `.env` and then run:
```bash
php artisan key:generate
```

---

## 5. Run it

```bash
php artisan serve
```

Then open **http://127.0.0.1:8000**

---

## 6. Log in

| Email | Password | Role |
|---|---|---|
| `admin@clinic.local` | `admin123` | **Manager / admin** — sees everything |
| `dr.adam.hart@clinic.local` | `password123` | Doctor (Main Clinic) |
| `dr.lina.fares@clinic.local` | `password123` | Doctor (North Branch) |
| `rana.reception@clinic.local` | `password123` | Reception |
| `sami.lab@clinic.local` | `password123` | Lab technician |
| `patient1@example.com` … `patient5@example.com` | `password123` | Patients |

---

## What the system does

- **Accounts & roles** — admin, employee and patient each see a different app
- **Patients, appointments, clinical notes, treatments**
- **Integrated billing** — saving a treatment automatically creates an invoice line;
  payments are auto-applied to unpaid invoices (extra becomes account credit)
- **Lab cases** and **media** (x-rays / scans / photos)
- **Patient self-service** — patients request an appointment with a chosen doctor;
  staff approve and set the time, and the patient sees the confirmation
- **Staff self-registration** — new staff request access, and a manager approves
  them before they can log in

More detail: see **README.md** (full documentation) and **RUN.md**.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| `could not find driver` | enable `pdo_pgsql` in `php.ini`, then restart the terminal |
| `SQLSTATE... could not connect` | PostgreSQL isn't running, or `.env` credentials are wrong |
| `php` not recognised | reopen your terminal after installing PHP |
| Port 8000 already in use | `php artisan serve --port=8001` |

# MediSlot — Appointment Booking System

Laravel API backend + React SPA frontend, two portals: **Admin** (manage doctors, availability, breaks) and **Patient** (book/cancel appointments).

See [ARCHITECTURE.md](./ARCHITECTURE.md) for the full architecture, entities, and change-log of requirements.

## Stack

- Backend: Laravel 13 (PHP 8.3+), Sanctum SPA auth, Pest
- Frontend: React 19 + TypeScript + Vite, Tailwind CSS, TanStack Query

## Setup

### Backend

```bash
cd backend
composer install
cp .env.example .env      # already includes CORS/Sanctum config for the frontend at :5173
php artisan key:generate
```

Configure your DB in `.env` (`DB_*`), then:

```bash
php artisan migrate
php artisan db:seed        # seeds a demo admin, patients, and doctors (local/testing only)
php artisan serve --port=8000
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env       # VITE_API_BASE_URL=http://localhost:8000
npm run dev
```

Open **http://localhost:5173**.

## Login credentials (seeded, local dev only)

| Portal | URL | Email | Password |
|---|---|---|---|
| Admin | `/admin/login` | `admin@example.com` | `password` |
| Patient | `/patient/login` | `patient@example.com` | `password` |
| Patient (2nd) | `/patient/login` | `patient2@example.com` | `password` |

Patients can also self-register at `/patient/register`. Admin has no self-registration — seeded account only.

If you haven't seeded yet:

```bash
cd backend
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=DoctorSeeder
php artisan db:seed --class=PatientSeeder
```

## What each portal does

**Admin** (`/admin/...`)
- Log in, see a dashboard.
- Manage doctors: add/edit/deactivate, set weekly availability (multiple periods per day, e.g. 9–1 and 2–5), add/remove one-off breaks (date-specific — a break auto-moves any colliding booked appointment to the nearest free same-day slot, or cancels it if none is free).

**Patient** (`/patient/...`)
- Register / log in.
- Browse active doctors, view a doctor's available slots (derived from that doctor's availability minus breaks, bookings, and past times).
- Book a slot, view own appointments, cancel an upcoming one (past appointments can't be cancelled).

## Tests

```bash
cd backend
php artisan test
```

56 Pest feature tests covering both portals' auth, doctor/availability/break management, slot generation, booking, and cancellation, including concurrent-booking and no-slot-available edge cases.

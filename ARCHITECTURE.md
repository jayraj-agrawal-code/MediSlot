# MediSlot — Architecture & Development Guidelines

## 1. Overview

MediSlot, an appointment booking system, built module by module. Two-repo-in-one-tree layout:

```
practical/
├── backend/    Laravel API (PHP 8.3+, Laravel 13, Pest)
├── frontend/   React SPA (React 19, TypeScript, Vite)
└── ARCHITECTURE.md
```

Backend and frontend are decoupled. Frontend talks to backend only through the versioned JSON API. No server-rendered Blade views for the app UI.

### 1.1 Roles & Application Flow

Two independent login roles, each its own Sanctum SPA guard: **Admin** (`admin` guard, `admins` table) and **Patient** (`patient` guard, `patients` table). Doctors are managed data, not a login role.

**Admin side**
1. Admin logs in (seeded account; no self-registration).
2. Admin adds/edits/deactivates doctors.
3. Admin sets each doctor's recurring weekly availability. A day can have **multiple, non-overlapping periods** (e.g. Mon 9–1 and 2–5, to model a lunch break as a schedule gap).
4. Admin can add a **break** for a doctor on a specific date/time (e.g. "Sep 22, 10:30–11:30"), independent of the recurring weekly schedule. A break blocks new bookings for that window and automatically reschedules any already-booked appointment it collides with.

**Patient side**
5. Patient self-registers (`POST /patient/register`), then logs in.
6. Patient views active doctors.
7. Patient selects a doctor and views that doctor's bookable slots (derived from the doctor's availability periods for each day, exploded into fixed-length slots, for a rolling booking horizon, minus breaks, already-booked slots, and past slots).
8. Patient books a slot.
9. Patient views their own appointments (upcoming/past, booked/cancelled), including ones the system auto-rescheduled because of a new doctor break.
10. Patient cancels their own upcoming (not-yet-started) appointment; a cancelled slot becomes bookable again. Past appointments cannot be cancelled.

Core entities: `Admin`, `Patient`, `Doctor`, `DoctorAvailability` (day-of-week 1–7 + time range per doctor; **a doctor can have multiple non-overlapping rows for the same day**), `DoctorBreak` (doctor_id, break_date, start_time, end_time — a one-off block on a specific date, separate from the recurring weekly schedule), `Appointment` (doctor_id, patient_id, appointment_date, start_time, end_time, status: booked/cancelled). Slot math (availability periods → bookable slots for the booking horizon, excluding breaks/taken/past slots) lives in `SlotService`; booking/cancel lifecycle lives in `AppointmentService`; break creation + auto-reschedule of colliding appointments lives in `DoctorBreakService`. Slot length and booking horizon are configured in `config/appointments.php`.

Double-booking / concurrent-booking protection: `appointments.slot_lock` is a nullable, globally-unique column set to `"{doctor_id}_{date}_{start_time}"` while an appointment is booked, and cleared to `null` on cancellation. The unique index makes it impossible for two booked appointments to exist for the same doctor/date/time — including under concurrent requests, since only one insert can win — while `NULL` (cancelled) rows don't collide, so a freed slot can be rebooked.

### 1.2 Change Request: Split Working Hours & Doctor Breaks

Received as a developer change request (dated doc, "Developer Change Request"). Supersedes the original "one availability period per day" rule. Admin and Patient portals keep working as-is; this changes the availability/slot model underneath them.

**1. Multiple availability periods**
- Admin can add more than one availability period for the same day (e.g. Mon 9:00 AM–1:00 PM **and** 2:00 PM–5:00 PM).
- Periods for the same doctor/day must not overlap — validated the same way the old single-period rule was, just per-pair instead of per-day.
- Admin can view a doctor's complete availability (all periods, all days).
- Patient only ever sees slots inside a period; the gap between periods (e.g. 1–2 PM in the example) is never offered as a slot — this falls out naturally from generating slots per-period instead of per-day-window.

**2. Patient appointment slots**
- Unchanged in spirit: slots are still derived from availability minus booked minus past. The only change is that "availability" is now a set of periods per day instead of one window per day.
- Slots outside all of a doctor's periods for that day are never bookable (already true, still true).

**3. Doctor breaks**
- New entity, **`DoctorBreak`**: `doctor_id`, `break_date` (a specific calendar date — not recurring; see decision below), `start_time`, `end_time`.
- Admin can add a break for any doctor, any date/time. In practice a break only has an effect where it overlaps one of the doctor's availability periods for that day — a break outside all periods blocks nothing new (there were no slots there anyway).
- Any slot overlapping an active break is excluded from `SlotService`'s available-slots output, same as an already-booked slot.

**4. Existing appointments when a break is added**
- If a new break overlaps an existing **booked, upcoming** appointment for that doctor/date, the appointment is **automatically moved**, not cancelled outright.
- New slot must be: for the same doctor, outside all breaks, inside an availability period, and not already booked.
- "Nearest available slot" = smallest absolute time difference from the original `start_time` (search both earlier and later slots on the same day first).
- The patient sees the updated appointment time (their appointment list reflects the new slot; no separate notification channel exists yet — see open questions).

**5–6. Example & expected flow** — as specified: 9–1 & 2–5 availability, an 11:00 AM booked appointment, admin adds a 10:30–11:30 break → the 11:00 appointment is affected and auto-moved to the nearest free slot. Flow: *Admin sets multiple periods → Patient books → Admin adds a break → system finds affected appointments → each is auto-moved to its nearest free slot → patient sees the updated time.*

**Decisions made (asked/confirmed):**
- Breaks are **date-specific only**, not recurring — a distinct concept from `DoctorAvailability` (which stays recurring weekly). No "every day 1–2 PM" break for now.

**Open questions (need a decision before/during implementation, currently unspecified by the change request):**
- If no free slot exists anywhere in the booking horizon for an affected appointment, what happens? Candidates: cancel it and flag it for admin/patient follow-up, or leave it conflicting and surface a warning. Recommend: cancel + mark it distinguishably (not silently vanish) — needs confirmation.
- Same-day-only search, or spill into later days if the same day has nothing free? The doc's example is same-day; recommend same-day first, then nearest day forward within the horizon if same-day has nothing — needs confirmation.
- No patient notification system exists yet (email/SMS). "Patient sees the updated appointment time" is satisfied by the appointments list reflecting the new time; a push notification/email is out of scope unless requested separately.
- Editing/deleting an existing availability period the same way it can shrink/remove — should that also trigger auto-reschedule/cancellation for appointments that fall outside the new schedule? Not covered by this change request; treated as out of scope for now (only breaks trigger auto-reschedule).

## 2. Tech Stack

| Layer | Choice |
|---|---|
| API framework | Laravel 13 (PHP 8.3+) |
| API auth | Laravel Sanctum (SPA cookie-based session auth) |
| DB | MySQL/PostgreSQL (per environment) via Eloquent |
| Backend tests | Pest (feature-first) |
| Code style (PHP) | Laravel Pint |
| Frontend framework | React 19 + TypeScript |
| Build tool | Vite |
| Frontend data fetching | TanStack Query (server state/cache) |
| Frontend state | React Query for server state; local component state / Context for UI-only state — no global store unless a real cross-page need shows up |
| HTTP client | axios (shared instance with interceptors) |
| Frontend tests | Vitest + React Testing Library |
| Linting | ESLint (frontend), Pint (backend) |

Don't add a package (state library, UI kit, etc.) until a feature actually needs it.

## 3. Backend Architecture (Laravel)

### 3.1 Layered flow

```
Route → FormRequest (validate/authorize) → Controller (thin) → Service (business logic) → Model/Query (Eloquent)
                                                                        ↓
                                                                  API Resource (response shape)
```

**Controllers**
- Handle HTTP request/response only: call a FormRequest for validated input, call one Service method, return a Resource/response.
- No business logic, no raw query building, no direct multi-step orchestration in a controller method.
- One responsibility per action (`index`, `store`, `show`, `update`, `destroy`, or a single custom action per invokable controller).

```php
final class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $service) {}

    public function store(StoreAppointmentRequest $request): AppointmentResource
    {
        $appointment = $this->service->book($request->validated(), $request->user());

        return AppointmentResource::make($appointment);
    }
}
```

**Form Requests**
- All input validation and authorization checks live in `App\Http\Requests\*`, never inline in the controller (`$request->validate()` is not used).
- `authorize()` holds the access check (often delegating to a Policy); `rules()` holds validation.

**Services**
- `App\Services\*` hold business logic: orchestration, multi-model writes, side effects (notifications, events), external calls.
- One service per bounded concern (e.g. `AppointmentService`, `AvailabilityService`). Inject dependencies via constructor.
- Services return Models/DTOs, never HTTP responses — they know nothing about the request/response cycle.
- Wrap multi-step writes in `DB::transaction()` inside the service.

**Models & Eloquent**
- `App\Models\*`. All relationships declared with return types:
  ```php
  public function appointments(): HasMany
  {
      return $this->hasMany(Appointment::class);
  }
  ```
- Business/query rules that get reused go in **local scopes**, not repeated `where()` chains in services/controllers:
  ```php
  public function scopeUpcoming(Builder $query): Builder
  {
      return $query->where('starts_at', '>=', now());
  }
  ```
- Use casts (`$casts`), enums for status-like columns, and accessors/mutators (`Attribute::make()`) on the model — not in services or controllers.
- Guard against N+1: eager-load (`with()`) whenever a relation is used in a list response; add a test that asserts query count where it matters.

**API Resources**
- Every API response shape goes through `App\Http\Resources\*`. Controllers never return raw Models or arrays built ad hoc.

**Authorization**
- `App\Policies\*` for model-level authorization, wired through Form Request `authorize()` or `$this->authorize()`. No manual role/permission `if` checks scattered in controllers/services.

**Enums**
- PHP native backed enums for fixed value sets (appointment status, etc.), under `App\Enums`.

**Events/Listeners/Jobs**
- Side effects that aren't core to the request (emails, notifications, reminders) are dispatched as Events → Listeners, or queued Jobs — not called inline in the Service unless trivial and synchronous is correct.

**Directory structure (backend, additive to Laravel defaults)**

```
app/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Policies/
├── Providers/
└── Services/
```

### 3.2 API conventions

- All routes versioned and namespaced: `routes/api.php` → `Route::prefix('v1')->group(...)`, controllers under `Http/Controllers/Api/V1`.
- RESTful resource routing (`apiResource`) as the default shape; custom actions only when a resourceful verb doesn't fit (e.g. `POST /appointments/{id}/cancel`).
- Consistent success/error envelope. Errors use Laravel's exception handler (`App\Exceptions\Handler` / `bootstrap/app.php` `withExceptions`) rendered to JSON — no ad hoc `try/catch` → manual JSON error building in controllers.
- Pagination via Laravel's paginator, wrapped in Resource collections.

### 3.3 Testing (backend)

- Pest, feature-test-first (`tests/Feature`), hitting real routes with a real (SQLite/testing) DB and factories — no mocking the DB.
- Unit tests (`tests/Unit`) for Services/Enums/pure logic where isolation is valuable.
- Every new endpoint gets a feature test covering: happy path, validation failure, authorization failure.

### 3.4 Code style

- `vendor/bin/pint --dirty` before considering backend work done.
- Constructor property promotion, explicit return types and param types everywhere, curly braces always.

## 4. Frontend Architecture (React)

### 4.1 Directory structure

```
src/
├── api/            axios instance, per-resource API modules (appointmentsApi.ts, ...)
├── components/     shared/reusable UI components
├── features/       feature-scoped modules (e.g. appointments/, availability/)
│   └── appointments/
│       ├── components/
│       ├── hooks/        (useAppointments.ts — wraps React Query)
│       └── types.ts
├── hooks/          cross-feature shared hooks
├── pages/          route-level components
├── routes/         router config
├── types/          shared/global TS types
└── lib/            generic utilities (formatting, constants)
```

- Feature-first: code for a feature lives together under `features/<name>`; only genuinely shared UI goes in top-level `components/`.
- Components stay presentational where possible; data fetching lives in hooks (`useAppointments`), not inline in component bodies.

### 4.2 Data fetching

- All server communication goes through TanStack Query hooks calling functions in `api/*` (axios). No `fetch`/`axios` calls directly inside components.
- One `api/client.ts` axios instance: base URL, credentials, response/error interceptor (e.g. normalize API errors, handle 401).
- Mutations invalidate the relevant query keys; no manual re-fetch plumbing.

### 4.3 Types

- TypeScript strict mode. Types for API resources mirror backend API Resources and live in `features/<name>/types.ts` or `types/`.

### 4.4 Testing (frontend)

- Vitest + React Testing Library. Test behavior (what the user sees/does), not implementation details.

### 4.5 Code style

- ESLint clean before considering frontend work done. No `any` without justification.

## 5. Cross-Cutting Conventions

- **Naming**: PascalCase for PHP classes and React components; camelCase for PHP/TS methods, variables, props; snake_case for DB columns; kebab-case for API route segments.
- **Git**: feature branches, small focused commits, conventional-ish commit messages (`feat:`, `fix:`, `refactor:`, `test:`).
- **Docs**: only created when explicitly requested — code and tests are the source of truth otherwise.
- **No premature abstraction**: don't add a repository layer, DTO layer, generic CRUD base class, etc. until a second real use case justifies it.

## 6. How We'll Build This

Each Appointment System feature (e.g. Services/Providers, Availability, Booking, Notifications, Admin) is implemented as its own vertical slice through this stack: migration → model/relationships/scopes → policy → form requests → service → controller → resource → routes → backend tests, then api module → hooks → components → pages → frontend tests. Guidelines above apply to every slice.

# InternTrack v1.0

InternTrack is a university internship management system for tracking placements, GPS attendance, digital logbooks, supervisor approvals, weekly evaluations, lecturer monitoring, visits and institutional reporting. It is built with Laravel 12, MySQL 8, Bootstrap 5 and Chart.js.

## Roles

| Role | Main capabilities |
| --- | --- |
| **Administrator** | Manage students, companies, supervisors, lecturers, internship periods and placements; academic structure; CSV student import; attendance corrections; reports and PDF export; audit logs; system settings. |
| **Student** | GPS check-in/check-out; attendance history; daily logbook with evidence uploads; submit logs for review; evaluations and feedback; personal attendance/logbook PDF report. |
| **Company Supervisor** | Review assigned students; verify or reject flagged attendance; start review, approve, reject or comment on logbook entries; create weekly evaluations. |
| **Lecturer** | Monitor assigned students; view risk indicators and reasons; schedule and complete visits with evidence; reopen a past logbook date with an audited reason; view/download student reports. |

Every role is protected by authentication, active-account checks, role middleware and model policies. Resource lookups are scoped, so changing an ID in a URL cannot expose another student's or company's data.

## Core behaviour

- **Attendance** uses the Haversine formula to compare submitted coordinates with the company location. The allowed radius is configured per company (falling back to the system default). Check-in is blocked on non-working days and when an active placement/period is missing. A student can check in once per day. Late arrival is calculated against the configured late threshold. Check-out calculates worked time and flags incomplete days below the minimum hours. Outside-location and incomplete records require supervisor review. Records are never edited directly; corrections are restricted, require a reason and are written to the audit trail.
- **Logbooks** are tied to a qualifying attendance record, one per day, and can only be written for today or an audited reopened historical date. Submission requires all reflection fields and 0.5–24 hours. Submitted entries are locked; approved entries are immutable. A supervisor rejection requires a reason and unlocks correction and resubmission.
- **Evidence** (JPG, JPEG, PNG, PDF, DOCX up to 10 MB by default) is validated by MIME type, extension and size, stored under a generated UUID name on the private disk, and downloaded through an authorized controller — binaries are never stored in MySQL and public URLs are never exposed.
- **Approvals** support requested correction, approval and rejection with mandatory reasons. **Weekly evaluations** score six criteria on a configurable scale.
- **Risk monitoring** derives a level and explicit reasons from attendance percentage, consecutive absences, missed/rejected logs, incomplete check-outs and recent supervisor activity.
- **Reporting** provides student logbook PDFs, lecturer student reports and a filterable university report with PDF export. Dashboards use Chart.js with real data.
- **Notifications** are stored in-app and optionally emailed. Scheduled commands send attendance, logbook and approval reminders plus weekly risk alerts, with duplicate suppression.
- **Audit logs** record security- and workflow-relevant actions and are read-only in the application.

## Technology

- PHP 8.2+ (tested with PHP 8.3), Laravel 12
- MySQL 8 with `utf8mb4`
- Bootstrap 5, Bootstrap Icons, Chart.js 4
- Vite 7 and Node.js 20+
- barryvdh/laravel-dompdf for PDF reports
- PHPUnit 11

## Requirements

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `dom`, `xml`, `curl`, `tokenizer` and `ctype` extensions
- Composer 2
- MySQL 8
- Node.js 20+ and npm
- A web server (Nginx or Apache) for production

## Installation

```bash
composer install
cp .env.example .env
```

Edit `.env` and set the application URL, MySQL connection and mail settings. Then create the database (for example, `interntrack` with `utf8mb4_unicode_ci`) and a dedicated MySQL user with privileges on it.

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve
```

Open `http://localhost:8000`. For local development you can instead run `composer run dev`, which starts the web server, queue listener, log tail and Vite dev server together.

> The default seed creates realistic demo data and demo accounts. Never expose the demo accounts or run the demo seeder on a public production system.

## Demo accounts

`php artisan migrate --seed` creates the following local accounts (all with password `password`):

| Email | Role |
| --- | --- |
| `admin@test.com` | Administrator |
| `student@test.com` | Student (Daniel Mugisha, TechFlow Solutions) |
| `supervisor@test.com` | Company Supervisor (Sarah Nabirye, TechFlow Solutions) |
| `lecturer@test.com` | Lecturer (Dr. Samuel Kigongo) |

The demo set also contains 20 students, 5 companies, 5 supervisors, 4 lecturers, active and closed periods, placements, attendance, logbooks, evaluations and visits. Demo credential hints are displayed on the login page only when `APP_ENV=local`. Change all demo passwords or remove the accounts before any shared deployment. `DemoDataSeeder` refuses to run in production.

## Testing

The automated suite runs against a separate MySQL database. Create it once and grant the same application user access:

```sql
CREATE DATABASE interntrack_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`phpunit.xml` fixes the test database name to `interntrack_test` and takes host/credentials from `.env`. Then run:

```bash
php artisan test
# or a focused suite
php artisan test --testsuite=Feature
vendor/bin/pint --test
```

The tests cover authentication and role isolation, IDOR protection, geofencing boundaries and validation, attendance rules, logbook locking and evidence validation, supervisor approvals, evaluations, risk detection, visits, reports, CSV import, notifications, scheduled reminders and settings.

## Scheduled tasks and queue

| Command | Schedule | Purpose |
| --- | --- | --- |
| `interntrack:finalize-checkouts` | Daily 00:15 | Mark previous days without check-out as incomplete. |
| `interntrack:remind-attendance` | Daily 09:00 | Remind active students who have not checked in. |
| `interntrack:remind-logs` | Daily 17:15 | Remind students who attended but have not logged the day. |
| `interntrack:remind-approvals` | Daily 07:30 | Send supervisors a pending-review digest. |
| `interntrack:risk-alerts` | Monday 07:00 | Alert lecturers to placements at risk. |

Locally, Laravel's scheduler can be run with `php artisan schedule:work`. In production add the cron entry shown in [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). Run `php artisan schedule:list` to verify the schedule.

Set `MAIL_MAILER=log` for local development. To deliver email, configure SMTP and keep **Settings → Notifications → Email notifications** enabled. In-app notifications always work. If `QUEUE_CONNECTION=database` is used, run a queue worker; notifications can also be delivered synchronously when the queue is set to `sync`.

## Security

- Passwords are hashed with bcrypt; login, password reset and attendance endpoints are rate limited.
- CSRF protection, secure session cookies (`HttpOnly`, `SameSite=Lax`), session regeneration on login and active-user enforcement are enabled.
- Policies protect every model action; supervisor and lecturer queries are restricted to their own students/company, students to their own records, and admins receive an explicit policy bypass.
- All write endpoints use Form Requests and validated, whitelisted fields; there is no mass assignment from raw request arrays.
- Uploads use server-side MIME/extension/size validation and random file names, and are stored privately.
- Settings are whitelisted and audited. Audit logs have no mutation routes.
- Blade escapes output and chart data is emitted with safely encoded JSON.
- `.env` is ignored by Git. Never commit credentials, SMTP passwords or API keys.

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for the production checklist.

## Project structure

```
app/
  Console/Commands/     Scheduled reminder and check-out commands
  Enums/                Roles, statuses and workflow rules
  Http/Controllers/     Role-specific controllers and admin modules
  Http/Middleware/      Role and active-account enforcement
  Http/Requests/        Validation and authorization rules
  Models/               Eloquent models and domain helpers
  Notifications/        In-app/email notification classes
  Policies/             Per-model authorization
  Services/             Attendance, geolocation, logbook, approvals,
                        evaluations, risk, reports, visits, notifications,
                        auditing
  Support/              Helpers and risk assessment value object
database/
  migrations/           Full MySQL schema
  seeders/              Roles, settings, academic structure, demo data
docs/DEPLOYMENT.md      Production deployment guide
resources/
  views/                Role dashboards, forms, PDF templates, auth pages
  js/ css/              Vite entry points, Bootstrap and Chart.js bootstrap
routes/                 Web and console schedules
tests/                  Unit and feature suites
```

## Licence

Internal university project. Third-party dependencies retain their own licences.

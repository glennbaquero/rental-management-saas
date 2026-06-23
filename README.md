# Rental Management SaaS

> **Skill Demonstration Only** — This project is built solely to showcase software development skills and architectural patterns. It is not a production product and is not intended for commercial use.

A multi-tenant rental property management SaaS built with Laravel, Inertia.js, and Vue 3. Each organization (landlord/property management company) gets its own isolated tenant database via [Stancl/Tenancy](https://tenancyforlaravel.com/).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Vue 3, Inertia.js, TypeScript |
| Styling | Tailwind CSS v4 |
| Build Tool | Vite 8 |
| Multi-tenancy | Stancl/Tenancy v3 |
| Auth | Laravel Fortify (passkeys support) |
| Payments | Stripe |
| Database | SQLite (dev), MySQL/PostgreSQL (production) |
| Testing | Pest v4 |
| Queue | Laravel Database Queue |

---

## Features

### Auth
- Registration with tenant (organization) provisioning
- Login, password reset, email verification
- Passkey support via `@laravel/passkeys`

### Organization Management
- Custom RBAC — roles with dot-notation permissions (no Spatie)
- Built-in roles: Owner, Property Manager, Staff, Accountant
- User invitations via email with 7-day expiring tokens
- Organization settings

### Property Management
- Hierarchy: Properties → Buildings → Units
- Unit availability tracking with status management
- Amenities (system and custom)
- Property images

### Tenant / Customer Management
- Rental tenant profiles (distinct from SaaS tenants)
- ID document uploads and emergency contacts
- Statuses: prospect, active, moved_out, blacklisted
- Rental history timeline

### Lease Management
- Full lease lifecycle: draft → active → expired / terminated
- Lease renewals with history tracking
- Security and other deposit types
- Document attachments (polymorphic)
- Automated expiry via scheduled jobs
- Email reminders at 90, 60, 30, and 7 days before expiry

### Rent & Billing
- Invoice generation (manual and automated)
- Payment recording with proof of payment upload
- Late fee application (fixed or percentage)
- Configurable billing cycles (monthly, quarterly, semi-annual, annual)
- Billing settings per organization (invoice number format, grace period, etc.)
- Automated daily jobs: invoice generation, late fee application, reminders

---

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ with npm or pnpm
- SQLite (default, zero config) or MySQL 8+ / PostgreSQL 15+

---

## Setup

### Quick Setup

```bash
git clone <repository-url>
cd rental-management-saas
composer setup
```

The `composer setup` script runs `composer install`, copies `.env.example` to `.env`, generates an app key, runs migrations, installs npm packages, and builds frontend assets.

### Manual Setup

**1. Clone and install dependencies**

```bash
git clone <repository-url>
cd rental-management-saas
composer install
npm install
```

**2. Configure environment**

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your values — see [Environment Variables](#environment-variables) below.

**3. Run migrations**

```bash
php artisan migrate
```

**4. Build frontend assets**

```bash
npm run build
```

---

## Running the Dev Server

```bash
composer dev
```

This starts four processes concurrently via `concurrently`:

| Process | Description |
|---|---|
| `php artisan serve` | Laravel dev server at `http://localhost:8000` |
| `php artisan queue:listen` | Queue worker for jobs |
| `php artisan pail` | Real-time log viewer |
| `npm run dev` | Vite HMR dev server |

---

## Queue Worker

Several features depend on the queue worker:

- Billing: invoice generation, late fee application, payment email notifications
- Leases: expiry auto-processing, email reminders
- Organization: invitation emails

The queue worker is included in `composer dev`. For standalone use:

```bash
php artisan queue:listen --tries=3
```

---

## Environment Variables

Key variables to configure in `.env`:

| Variable | Description | Default |
|---|---|---|
| `APP_URL` | Application base URL | `http://localhost` |
| `DB_CONNECTION` | Database driver (`sqlite`, `mysql`, `pgsql`) | `sqlite` |
| `DB_HOST` | Database host (MySQL/PostgreSQL) | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name or SQLite path | `database/database.sqlite` |
| `DB_USERNAME` | Database username | — |
| `DB_PASSWORD` | Database password | — |
| `MAIL_MAILER` | Mail driver (`log`, `smtp`, `ses`, etc.) | `log` |
| `MAIL_HOST` | SMTP host | `127.0.0.1` |
| `MAIL_PORT` | SMTP port | `2525` |
| `MAIL_USERNAME` | SMTP username | — |
| `MAIL_PASSWORD` | SMTP password | — |
| `MAIL_FROM_ADDRESS` | Sender email address | `hello@example.com` |
| `STRIPE_KEY` | Stripe publishable key | — |
| `STRIPE_SECRET` | Stripe secret key | — |

For local development, the default `log` mail driver writes emails to `storage/logs/laravel.log` — no mail server needed.

---

## Testing

```bash
php artisan test
# or
composer test
```

`composer test` also runs PHP linting and static analysis before the test suite.

---

## Code Quality

```bash
# PHP formatting (Laravel Pint)
composer lint

# PHP static analysis (PHPStan / Larastan)
composer types:check

# JS/TS linting (ESLint)
npm run lint

# JS/TS type checking (vue-tsc)
npm run types:check

# Prettier formatting check
npm run format:check

# Run all checks (CI)
composer ci:check
```

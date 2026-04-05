# Subscription Lifecycle Engine

A robust, standalone **Subscription Management API** built with Laravel 12 that handles dynamic plan management, multi-currency/multi-cycle pricing, subscription lifecycle state management, and automated grace period enforcement.

> Built as a backend engineering challenge for **Trendline – UAE 🇦🇪**

---

## Table of Contents

- [Architecture Decisions](#architecture-decisions)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [Subscription State Machine](#subscription-state-machine)
- [Grace Period Logic](#grace-period-logic)
- [API Endpoints](#api-endpoints)
- [Setup & Installation](#setup--installation)
- [Running Tests](#running-tests)
- [Scheduled Tasks](#scheduled-tasks)
- [Postman Collection](#postman-collection)

---

## Architecture Decisions

### Design Patterns Used

| Pattern                       | Why                                                                                                                                                                                                    |
| :---------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **State Machine**             | Subscription lifecycle transitions are governed by explicit rules (`SubscriptionStatus` enum with `allowedTransitions()`). This prevents invalid state changes and makes the business logic auditable. |
| **Service Layer**             | All business logic lives in `SubscriptionService` and `PaymentService` — controllers stay thin, only handling HTTP concerns.                                                                           |
| **Backed Enums (PHP 8.1+)**   | `SubscriptionStatus`, `BillingCycle`, `Currency`, `PaymentStatus` are backed string enums with helper methods. They provide type safety and eliminate magic strings.                                   |
| **Form Request Validation**   | Dedicated `FormRequest` classes encapsulate validation + authorization, keeping controllers clean.                                                                                                     |
| **API Resources**             | `JsonResource` classes shape the JSON output, decoupling database structure from API contract.                                                                                                         |
| **Event-Driven Side Effects** | State transitions fire `SubscriptionStateChanged` events, enabling decoupled listeners (logging, notifications, webhooks) without polluting core logic.                                                |
| **Factory Pattern (Testing)** | Eloquent factories with fluent state methods (`->trialing()`, `->expiredTrial()`, `->pastDue()`) make test setup expressive and maintainable.                                                          |

### Key Design Choices

1. **Separate `plan_prices` table** — Instead of embedding prices in the plans table, prices are normalized into their own table with a unique constraint on `(plan_id, billing_cycle, currency)`. This cleanly supports the **matrix** of billing cycles × currencies per plan.

2. **Price snapshot on subscription** — When a user subscribes, the `price`, `currency`, and `billing_cycle` are copied into the subscription row. This decouples the subscription from future price changes.

3. **`is_active` flag over soft deletes** — Plans are deactivated rather than soft-deleted, so existing subscriptions referencing the plan remain fully functional.

4. **One active subscription per user** — A user can only have one accessible subscription (trialing, active, or past_due) at a time.

5. **Simulated payments** — Since this is a backend challenge, payments are recorded via API (no real gateway). The system reacts to payment outcomes (success/failure) with appropriate state transitions.

---

## Tech Stack

- **Framework:** Laravel 12 (PHP 8.2+)
- **Database:** SQLite (easily swappable via `.env`)
- **Authentication:** Laravel Sanctum (token-based API auth)
- **Testing:** PHPUnit with 42 feature tests

---

## Database Schema

```
users
├── id, name, email, password, timestamps

plans
├── id, name, description, trial_period_days, is_active, timestamps

plan_prices
├── id, plan_id (FK), billing_cycle, currency, price, is_active, timestamps
├── UNIQUE(plan_id, billing_cycle, currency)

subscriptions
├── id, user_id (FK), plan_id (FK), plan_price_id (FK)
├── status (trialing|active|past_due|canceled)
├── trial_ends_at, starts_at, ends_at, canceled_at, grace_period_ends_at
├── price, currency, billing_cycle, timestamps
├── INDEX(status, trial_ends_at)
├── INDEX(status, grace_period_ends_at)

payments
├── id, subscription_id (FK), amount, currency
├── status (succeeded|failed|refunded)
├── transaction_id (unique), metadata (JSON), paid_at, timestamps
```

---

## Subscription State Machine

```
                    ┌─────────────────────────────────────────┐
                    │                                         │
    ┌───────────┐   │   ┌───────────┐   ┌──────────┐   ┌─────┴─────┐
    │           │───┼──►│           │──►│          │──►│           │
    │  (start)  │   │   │  Trialing │   │  Active  │   │ Canceled  │
    │           │───┼──►│           │◄──│          │   │           │
    └───────────┘   │   └───────────┘   └──────────┘   └─────▲─────┘
                    │                         │              │
                    │                         ▼              │
                    │                   ┌──────────┐         │
                    │                   │ Past Due │─────────┘
                    │                   │(3-day GP)│
                    │                   └──────────┘
                    │                         │
                    │                         ▼
                    │                   ┌──────────┐
                    └───────────────────│  Active  │
                                        └──────────┘
```

### Transition Rules

| From     | To       | Trigger                                             |
| :------- | :------- | :-------------------------------------------------- |
| —        | Trialing | Subscribe to plan with `trial_period_days > 0`      |
| —        | Active   | Subscribe to plan with no trial                     |
| Trialing | Active   | Successful payment recorded OR trial ends + payment |
| Trialing | Canceled | Trial expires (cron) OR user cancels                |
| Active   | Past Due | Failed payment recorded                             |
| Active   | Canceled | User cancels subscription                           |
| Past Due | Active   | Successful payment within grace period              |
| Past Due | Canceled | Grace period expires (cron, 3 days)                 |

---

## Grace Period Logic

When a payment fails on an **active** subscription:

1. Status transitions to `past_due`
2. `grace_period_ends_at` is set to **now + 3 days**
3. **Access remains granted** (`isAccessible()` returns `true` for `past_due`)
4. If a successful payment is recorded within those 3 days → status returns to `active`
5. If no payment is recorded → the daily cron job finds the expired grace period and transitions to `canceled`

The grace period duration is defined as a constant in `SubscriptionService::GRACE_PERIOD_DAYS = 3`.

---

## API Endpoints

### Authentication

| Method | Endpoint           | Auth | Description                            |
| :----- | :----------------- | :--- | :------------------------------------- |
| POST   | `/api/register` | ✗    | Register a new user, returns API token |
| POST   | `/api/login`    | ✗    | Login, returns API token               |
| POST   | `/api/logout`   | ✓    | Revoke current token                   |

### Plan Management

| Method | Endpoint             | Auth | Description                         |
| :----- | :------------------- | :--- | :---------------------------------- |
| GET    | `/api/plans`      | ✗    | List all active plans with prices   |
| GET    | `/api/plans/{id}` | ✗    | Get single plan with prices         |
| POST   | `/api/plans`      | ✓    | Create a new plan with prices       |
| PUT    | `/api/plans/{id}` | ✓    | Update plan (and optionally prices) |
| DELETE | `/api/plans/{id}` | ✓    | Deactivate a plan                   |

### Subscription Management

| Method | Endpoint                            | Auth | Description                       |
| :----- | :---------------------------------- | :--- | :-------------------------------- |
| GET    | `/api/subscriptions`             | ✓    | List current user's subscriptions |
| POST   | `/api/subscriptions`             | ✓    | Subscribe to a plan               |
| GET    | `/api/subscriptions/{id}`        | ✓    | Get subscription detail           |
| POST   | `/api/subscriptions/{id}/cancel` | ✓    | Cancel a subscription             |

### Payment Management

| Method | Endpoint                              | Auth | Description                                   |
| :----- | :------------------------------------ | :--- | :-------------------------------------------- |
| GET    | `/api/subscriptions/{id}/payments` | ✓    | List payments for a subscription              |
| POST   | `/api/subscriptions/{id}/payments` | ✓    | Record a payment (triggers state transitions) |

---

## Setup & Installation

### Prerequisites

- PHP 8.2+
- Composer
- SQLite enabled (or MySQL/PostgreSQL via `.env`)

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/YOUR_USERNAME/SubscriptionLifecycleEngine.git
cd SubscriptionLifecycleEngine

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Run migrations and seed the database
php artisan migrate --seed

# 6. Start the development server
php artisan serve
```

The API will be available at `http://localhost:8000/api/`

### Seeded Data

The seeder creates 3 plans:

- **Basic** (7-day trial) — from $9.99/month
- **Professional** (14-day trial) — from $29.99/month
- **Enterprise** (no trial) — from $99.99/month

Each plan has 6 price variants (2 billing cycles × 3 currencies: USD, AED, EGP).

A test user is also created: `test@example.com` (password: `password`)

---

## Running Tests

```bash
php artisan test
```

**42 tests, 121 assertions** covering:

- Authentication (register, login, logout, validation)
- Plan CRUD (create, read, update, deactivate, validation)
- Subscription lifecycle (subscribe with/without trial, cancellation, duplicate prevention, access control)
- Payment recording (state transitions: active→past_due, past_due→active, trialing→active)
- Scheduled command (expired trials, expired grace periods, batch processing)

---

## Scheduled Tasks

A cron job runs daily to automatically process subscription lifecycle events:

```bash
# Register this in your server's crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### What it does

The `subscription:process-lifecycle` command (runs daily):

1. **Expired Trials** — Finds all `trialing` subscriptions where `trial_ends_at` has passed and cancels them.
2. **Expired Grace Periods** — Finds all `past_due` subscriptions where `grace_period_ends_at` has passed and cancels them.

You can also run it manually:

```bash
php artisan subscription:process-lifecycle
```

---

## Postman Collection

A complete Postman collection is included at `postman/SubscriptionLifecycleEngine.postman_collection.json`.

### How to import:

1. Open Postman
2. Click **Import** → **Upload Files**
3. Select the JSON file from the `postman/` directory
4. Set the `base_url` variable to `http://localhost:8000` (or your server URL)

The collection includes examples for all 14 API endpoints with sample request/response bodies.

---

## Project Structure

```
app/
├── Console/Commands/          # Artisan commands (cron job)
├── Enums/                     # PHP 8.1 backed enums
├── Events/                    # Domain events
├── Exceptions/                # Custom exceptions
├── Http/
│   ├── Controllers/Api/   # API controllers (thin)
│   ├── Requests/              # Form request validation
│   └── Resources/             # API resource transformers
├── Listeners/                 # Event listeners (logging)
├── Models/                    # Eloquent models
├── Providers/                 # Service providers
└── Services/                  # Business logic layer
database/
├── factories/                 # Test factories
├── migrations/                # Schema definitions
└── seeders/                   # Seed data
tests/Feature/                 # Feature tests (42 tests)
routes/
├── api.php                    # API routes
└── console.php                # Scheduled tasks
```

---

## License

This project is built for evaluation purposes as part of the Trendline backend challenge.

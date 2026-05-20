# snapstamp — Claude Project Guide

## Project Overview

**snapstamp** is a digital loyalty rewards platform (think digital stamp cards) connecting customers with local businesses across India. Built with Symfony 7.x as a REST API backend.

**Stack:**
- PHP 8.2+ / Symfony 7.x
- MySQL 8.0 (primary DB)
- Redis (caching + JWT blacklist)
- RabbitMQ (async message queue, optional)
- React.js (frontend, separate repo)

---

## Directory Layout

```
src/
├── Command/          # Cron-triggered console commands
├── Controller/Api/   # Route handlers — thin, delegate to services
├── Dto/              # Input validation objects (Symfony Validator)
├── Entity/           # Doctrine ORM entities
├── Enum/             # PHP 8.1+ backed enums
├── EventListener/    # Symfony event subscribers
├── Exception/        # Domain exceptions (not generic \Exception)
├── Message/          # Async message value objects
├── MessageHandler/   # Handlers for Message objects
├── Repository/       # Custom Doctrine query methods
├── Security/         # JWT authenticator, voters
├── Service/          # Business logic lives here
├── Utils/            # Stateless helpers (QR, JWT generation)
└── Validator/        # Custom constraint validators
```

---

## Entity Hierarchy

```
User (abstract, SINGLE_TABLE inheritance)
├── Customer   — ROLE_CUSTOMER
└── Merchant   — ROLE_MERCHANT

StampCard  (Customer ←→ Merchant)
└── Stamp  (individual stamps on a card)

Reward     (owned by Merchant)
└── RewardRedemption  (Customer redeeming a Reward)

Payment / Transaction / Notification
```

**Inheritance strategy:** `SINGLE_TABLE` on `users` table with `user_type` discriminator column.

---

## Coding Conventions

### Entities
- IDs are UUIDs generated in `__construct()` via `Ramsey\Uuid\Uuid::uuid4()`
- Use PHP 8.1 backed enums for all status/type fields (e.g. `UserStatusEnum`, `StampCardStatusEnum`)
- Soft deletes via `deletedAt` field — never hard-delete users
- Timestamps: `createdAt` / `updatedAt` as `\DateTimeImmutable`
- Decimal money fields stored as `string` (e.g. `$totalSpent = '0.00'`) to avoid float precision issues

### Services
- All business logic goes in `Service/` — controllers only parse requests and call services
- Services receive typed DTOs, not raw arrays
- Use `EntityManagerInterface` + `flush()` at service level (not controller)
- Cache with `RedisAdapter::get($key, $callback, $ttl)` pattern
- Always invalidate related cache keys after writes
- Inject `LoggerInterface` — log info on success, error on failure

### Controllers
- Extend `AbstractController`
- Return `JsonResponse` with shape `{success: bool, message: string, data: mixed}`
- Use `#[IsGranted]` attribute for authorization
- Catch domain exceptions and return appropriate HTTP status codes (400/401/403/404)
- Never expose raw exception messages from unexpected `\Exception` to clients

### DTOs
- Public typed properties with `#[Assert\*]` annotations
- Use `#[Assert\Optional]` for nullable fields
- Validate in the controller before passing to service

### Enums
- All enums are string-backed
- Stored in `src/Enum/`
- Compare with `.value` only when needed (e.g. `$status->value === 'ACTIVE'`), prefer `match` or direct enum comparison

---

## Authentication

- JWT tokens signed with HS256 (dev) / RS256 (production)
- Token payload: `sub`, `email`, `role`, `iat`, `exp`, `jti`
- Access token TTL: 24h (`JWT_TTL=86400`)
- Refresh token TTL: 7 days (`JWT_REFRESH_TTL=604800`)
- Logged-out tokens blacklisted in Redis: `blacklist:{jti}` with remaining TTL
- `UserPasswordHasherInterface` for password hashing — never call `password_hash()` directly in service code after initial setup

---

## Caching Conventions

Cache key patterns:
```
customer:{id}:profile
merchant:{id}:details
stampcard:{id}:stats
rewards:nearby:{lat}:{lon}:{radius}
blacklist:{jti}
```

Default TTLs:
- User profiles: 1 hour
- Merchant details: 2 hours
- Nearby rewards: 30 minutes
- Stamp card stats: 30 minutes

Always use `cache->delete(key)` after mutating the underlying data.

---

## Domain Rules

### Stamps
- A stamp can only be issued to an ACTIVE, non-expired `StampCard`
- `StampCard::incrementStampCount()` auto-sets status to `COMPLETED` when count reaches `totalSlotsRequired`
- Expired check: `expiresAt < new \DateTimeImmutable()`
- Stamps expire 1 year after issuance

### Rewards
- Redemption requires: reward not expired + max redemptions not reached + customer has enough active stamps
- When redeeming, set the associated `StampCard` status to `COMPLETED`
- Always send notification after successful redemption (PUSH + EMAIL)
- `redeemCode` generated as `REDEEM-{16 hex chars}`

### Merchants
- New merchants start at `onboardingStatus = PENDING`
- `isVerified = false` until admin approves
- Each merchant gets an `apiKey` (`pk_...`) and `apiSecret` (`sk_...`) on registration

### Customer Tiers (computed, not stored)
```
BRONZE  → default
SILVER  → totalSpent >= 5000
GOLD    → totalSpent >= 20000
PLATINUM → totalSpent >= 50000
```

---

## API Shape

Base URL: `/api/v1/`

Standard response:
```json
{"success": true, "message": "...", "data": {...}}
{"success": false, "message": "error reason"}
```

HTTP status codes:
- `200` GET success
- `201` POST create success
- `400` Validation / domain error
- `401` Auth failure
- `403` Authorization failure
- `404` Resource not found

---

## Testing

- Unit tests in `tests/Unit/` — mock all dependencies with `createMock()`
- Integration tests in `tests/Integration/` — use real DB
- Functional tests in `tests/Functional/` — full HTTP journey
- Run: `php bin/phpunit`
- Target: >70% coverage

---

## Key Commands

```bash
# Database
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate

# Dev server
symfony serve

# Tests
php bin/phpunit
php bin/phpunit tests/Unit/Service/StampServiceTest.php

# Cache
php bin/console cache:clear

# JWT keys (first time setup)
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem
```

---

## Environment Variables (required)

```
DATABASE_URL          PostgreSQL DSN
REDIS_URL             Redis DSN
JWT_SECRET_KEY        Path to private.pem
JWT_PUBLIC_KEY        Path to public.pem
JWT_TTL               Access token TTL in seconds (86400)
JWT_REFRESH_TTL       Refresh token TTL in seconds (604800)
RAZORPAY_KEY_ID       Payment gateway
RAZORPAY_KEY_SECRET
RAZORPAY_WEBHOOK_SECRET
TWILIO_ACCOUNT_SID    SMS notifications
TWILIO_AUTH_TOKEN
TWILIO_PHONE_NUMBER
FIREBASE_API_KEY      Push notifications
```

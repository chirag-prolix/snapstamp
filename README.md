# snapstamp

Digital loyalty rewards platform — digital stamp cards connecting customers with local businesses across India.

**Backend:** Symfony 7 REST API · MySQL 8 · Redis · RabbitMQ

---

## Table of Contents

- [Requirements](#requirements)
- [Local Setup](#local-setup)
- [Environment Variables](#environment-variables)
- [Build Status](#build-status)
- [API Reference](#api-reference)
- [Project Structure](#project-structure)
- [Data Model](#data-model)
- [Roadmap](#roadmap)

---

## Requirements

| Tool | Version |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Docker + Docker Compose | any recent |
| openssl | for JWT key generation |

---

## Local Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Start services

```bash
docker compose up -d db phpmyadmin redis rabbitmq
```

| Service | URL | Credentials |
|---|---|---|
| MySQL | `localhost:3306` | `snapstamp_user` / `snapstamp_pass` |
| phpMyAdmin | http://localhost:8080 | `snapstamp_user` / `snapstamp_pass` |
| Redis | `localhost:6379` | — |
| RabbitMQ management | http://localhost:15672 | `guest` / `guest` |

### 3. Configure environment

```bash
cp .env .env.local
# Edit .env.local with your actual credentials
```

### 4. Generate JWT key pair (one-time)

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem
```

### 5. Set up the database

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Start the dev server

```bash
php -S 127.0.0.1:8000 public/index.php
# or if symfony CLI is installed:
symfony serve
```

---

## Environment Variables

| Variable | Description | Example |
|---|---|---|
| `DATABASE_URL` | MySQL DSN | `mysql://snapstamp_user:snapstamp_pass@127.0.0.1:3306/snapstamp_db?serverVersion=8.0&charset=utf8mb4` |
| `REDIS_URL` | Redis DSN | `redis://localhost:6379` |
| `JWT_SECRET_KEY` | Path to RSA private key | `%kernel.project_dir%/config/jwt/private.pem` |
| `JWT_PUBLIC_KEY` | Path to RSA public key | `%kernel.project_dir%/config/jwt/public.pem` |
| `JWT_TTL` | Access token TTL (seconds) | `86400` |
| `JWT_REFRESH_TTL` | Refresh token TTL (seconds) | `604800` |
| `RAZORPAY_KEY_ID` | Razorpay payment gateway | — |
| `RAZORPAY_KEY_SECRET` | Razorpay secret | — |
| `TWILIO_ACCOUNT_SID` | SMS notifications | — |
| `TWILIO_AUTH_TOKEN` | Twilio auth | — |
| `TWILIO_PHONE_NUMBER` | Sender number | — |
| `FIREBASE_API_KEY` | Push notifications | — |

---

## Build Status

### ✅ Step 1 — Documentation & Planning
- Architecture design (`snapstamp_symfony_architecture.md`)
- Implementation guide (`snapstamp_implementation_guide.md`)
- `CLAUDE.md` — project guide and coding conventions
- `SKILLS.md` — reusable development recipes

### ✅ Step 2 — Data Layer (Entities, Enums, Repositories, Migrations)

**15 enums** in `src/Enum/`:

| Enum | Values |
|---|---|
| `UserStatusEnum` | ACTIVE, INACTIVE, SUSPENDED, DELETED |
| `GenderEnum` | MALE, FEMALE, OTHER |
| `StampCardStatusEnum` | ACTIVE, COMPLETED, EXPIRED, CANCELLED |
| `StampStatusEnum` | ACTIVE, EXPIRED, REDEEMED |
| `RewardTypeEnum` | DISCOUNT, FREE_ITEM, CASHBACK, EXPERIENCE |
| `RewardStatusEnum` | ACTIVE, INACTIVE, EXPIRED, DRAFT |
| `RewardRedemptionStatusEnum` | PENDING, COMPLETED, CANCELLED, EXPIRED |
| `MerchantStatusEnum` | PENDING, VERIFIED, REJECTED, ACTIVE |
| `PaymentGatewayEnum` | RAZORPAY, INSTAMOJO, PAYPAL |
| `PaymentStatusEnum` | INITIATED, PENDING, COMPLETED, FAILED, REFUNDED |
| `PaymentTypeEnum` | REWARD_CASH_OUT, MERCHANT_PAYOUT, DEPOSIT |
| `PaymentMethodEnum` | CARD, UPI, WALLET, BANK_TRANSFER |
| `TransactionTypeEnum` | STAMP_ISSUED, STAMP_EXPIRED, REWARD_REDEEMED, PAYMENT_RECEIVED, PAYOUT_ISSUED, REFUND |
| `TransactionStatusEnum` | COMPLETED, PENDING, FAILED |
| `NotificationTypeEnum` | STAMP_RECEIVED, REWARD_AVAILABLE, REWARD_REDEEMED, PROMOTION, SYSTEM |

**11 entities** in `src/Entity/`:

| Entity | Notes |
|---|---|
| `User` | Abstract, SINGLE_TABLE inheritance, soft delete via `deletedAt` |
| `Customer` | Extends User · tier computed from `totalStampsCollected` (BRONZE/SILVER/GOLD/PLATINUM) |
| `Merchant` | Extends User · API key/secret · onboarding status |
| `MerchantCategory` | Lookup table with auto-generated slug |
| `StampCard` | Auto-completes when `currentStampCount >= totalSlotsRequired` |
| `Stamp` | Individual stamps · sequence tracking |
| `Reward` | Merchant-owned · supports DISCOUNT/FREE_ITEM/CASHBACK/EXPERIENCE |
| `RewardRedemption` | Auto-generates `REDEEM-{hex}` voucher code |
| `Payment` | Razorpay integration-ready · webhook tracking |
| `Transaction` | Immutable audit log |
| `Notification` | Multi-channel (push, email, SMS) |

**11 repositories** in `src/Repository/` with domain-specific query methods:
- `MerchantRepository::findNearby()` — Haversine SQL for radius search
- `StampCardRepository::findActiveByCustomerAndMerchant()`
- `RewardRepository::search(array $filters)`
- `NotificationRepository::findUnreadByCustomer()`
- and more

### ✅ Step 3 — JWT Authentication

**Endpoints:**

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/api/v1/auth/register/customer` | Public | Register a customer |
| `POST` | `/api/v1/auth/register/merchant` | Public | Register a merchant |
| `POST` | `/api/v1/auth/login` | Public | Login (customer or merchant) |
| `POST` | `/api/v1/auth/refresh` | Public | Refresh access token |
| `POST` | `/api/v1/auth/logout` | Bearer token | Blacklist current token |
| `GET` | `/api/v1/auth/me` | Bearer token | Get current user profile |

**Key details:**
- RS256 signed JWTs (4096-bit RSA key pair)
- Access token TTL: 24 hours · Refresh token TTL: 7 days
- Token blacklist stored in Redis (`blacklist.{jti}`)
- Refresh token rotation — old refresh JTI is blacklisted on use
- Merchant registration generates `pk_...` API key and `sk_...` API secret
- New merchants start with `onboardingStatus: PENDING`

**Quick test:**

```bash
# Register
curl -X POST http://localhost:8000/api/v1/auth/register/customer \
  -H "Content-Type: application/json" \
  -d '{"email":"you@example.com","password":"secret123","firstName":"Alice","lastName":"Smith","phone":"+919876543210"}'

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"you@example.com","password":"secret123"}'

# Profile (use accessToken from login response)
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer <accessToken>"
```

### ✅ Step 5 — Merchant Onboarding & Admin Approval

**New command:**

```bash
php bin/console app:create-admin --email=admin@snapstamp.com --password=Admin1234!
```

**Admin endpoints** (`ROLE_ADMIN` required):

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/v1/admin/merchants` | List all merchants (optional `?status=PENDING\|ACTIVE\|REJECTED\|VERIFIED`) |
| `GET` | `/api/v1/admin/merchants/{id}` | Get single merchant detail |
| `POST` | `/api/v1/admin/merchants/{id}/approve` | Approve merchant (PENDING → ACTIVE) |
| `POST` | `/api/v1/admin/merchants/{id}/reject` | Reject merchant with reason |

**Merchant self-service endpoints** (`ROLE_MERCHANT` required):

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/v1/merchant/profile` | Own merchant profile + onboarding status |
| `PATCH` | `/api/v1/merchant/profile` | Update profile fields (description, address, website, coords, etc.) |

**Key details:**
- Admin user is seeded via CLI command (creates a `Customer` with `ROLE_ADMIN`) — no migration needed
- Approval sets `onboardingStatus = ACTIVE` and `isVerified = true`
- Rejection sets `onboardingStatus = REJECTED` (only PENDING merchants can be approved/rejected)
- Merchants receive an email notification on approval or rejection (via Symfony Mailer)
- Sensitive fields (taxId, bank details, apiKey, apiSecret) are not updatable via PATCH — registration only
- Admin view exposes `apiKey`; merchant self-view exposes `apiKey` + `apiSecret`

**Quick test:**

```bash
# 1. Create admin
php bin/console app:create-admin --email=admin@snapstamp.com --password=Admin1234!

# 2. Login as admin
ADMIN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@snapstamp.com","password":"Admin1234!"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

# 3. List pending merchants
curl -s "http://localhost:8000/api/v1/admin/merchants?status=PENDING" \
  -H "Authorization: Bearer $ADMIN"

# 4. Approve a merchant
curl -s -X POST http://localhost:8000/api/v1/admin/merchants/{MERCHANT_ID}/approve \
  -H "Authorization: Bearer $ADMIN"
# Expect: {"success":true,"data":{"onboardingStatus":"ACTIVE","isVerified":true,...}}

# 5. Login as merchant and update profile
MERCH=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"merchant@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

curl -s -X PATCH http://localhost:8000/api/v1/merchant/profile \
  -H "Authorization: Bearer $MERCH" \
  -H "Content-Type: application/json" \
  -d '{"businessDescription":"Best shop in town","website":"https://myshop.in"}'
```

---

### ✅ Step 6 — Stamp Issuance Service

**Merchant endpoints** (`ROLE_MERCHANT` required):

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/v1/stamps/issue` | Issue stamps to a customer |
| `GET` | `/api/v1/stamps/card/{customerId}` | View a customer's stamp card at this merchant |

**Customer endpoints** (`ROLE_CUSTOMER` required):

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/v1/customer/stamp-cards` | List all stamp cards |
| `GET` | `/api/v1/customer/stamp-cards/{id}` | Get a specific card with all stamps |

**Key details:**
- Stamp card auto-created on first visit (`SC-{hex}` card number, 10 slots by default)
- Identify customer by `customerId` (UUID) or `customerPhone` (E.164)
- Issue 1–10 stamps per request; stamp sequences increment correctly across calls
- Cannot issue more stamps than remaining slots on the card — request is rejected with a clear error (e.g. _"Card only has 1 slot(s) remaining"_)
- Optional `transactionId` field acts as an idempotency key — re-submitting the same `transactionId` returns a 400 instead of double-issuing
- Card auto-completes (`status: COMPLETED`) when `currentStampCount >= totalSlotsRequired`
- After a card completes, the next stamp issuance automatically starts a new card
- Every issuance writes a `STAMP_ISSUED` transaction record and a `STAMP_RECEIVED` notification
- `Customer.totalStampsCollected` and `Merchant.totalStampsIssued` counters updated on each call

**Quick test:**

```bash
# Login as merchant
MTOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"shop@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

# Issue 1 stamp to a customer
curl -s -X POST http://localhost:8000/api/v1/stamps/issue \
  -H "Authorization: Bearer $MTOKEN" \
  -H "Content-Type: application/json" \
  -d '{"customerId":"<customer-uuid>","count":1}'
# Expect: 201, stampCard.currentStampCount=1, stamps[0].stampSequence=1

# Customer views their cards
CTOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"customer@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

curl -s http://localhost:8000/api/v1/customer/stamp-cards \
  -H "Authorization: Bearer $CTOKEN"
```

---

### ✅ Step 9 — Async Queue Workers (Symfony Messenger)

All blocking I/O (email, SMS, push) is now dispatched to an async queue instead of running inline on the HTTP request thread.

**Transport:** Doctrine (`doctrine://default` by default — no extra service needed; swap `MESSENGER_TRANSPORT_DSN` for `amqp://...` to use RabbitMQ in production).

**Message → Handler mapping:**

| Message | Handler | What it does |
|---|---|---|
| `SendEmailMessage` | `SendEmailHandler` | Sends email via Symfony Mailer |
| `SendSmsMessage` | `SendSmsHandler` | Sends SMS via Twilio (dev guard skips if unconfigured) |
| `SendPushNotificationMessage` | `SendPushNotificationHandler` | Stub — logs payload (Firebase integration pending) |

**What dispatches messages:**

| Service | Trigger | Message |
|---|---|---|
| `OtpService` | Email OTP request | `SendEmailMessage` |
| `OtpService` | Phone OTP request | `SendSmsMessage` |
| `MerchantOnboardingService` | Merchant approved/rejected | `SendEmailMessage` |
| `StampService` | Stamp issued | `SendPushNotificationMessage` |
| `RewardService` | Redemption initiated | `SendPushNotificationMessage` |
| `RewardService` | Redemption approved | `SendPushNotificationMessage` |
| `ReferralService` | Referral processed | `SendPushNotificationMessage` |

**Key details:**
- Failed messages land in the `failed` Doctrine transport for manual retry
- Worker command: `php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M`
- Dev OTP codes still appear in the application log immediately after dispatch

**Quick test:**
```bash
# 1. Set up Doctrine transport tables (one-time)
php bin/console messenger:setup-transports

# 2. Request OTP (queues SendEmailMessage — returns immediately)
curl -X POST http://localhost:8000/api/v1/verification/request \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type":"email"}'

# 3. Consume one message from queue
php bin/console messenger:consume async --limit=1 -vv
# Expect: "Message App\Message\SendEmailMessage handled by App\MessageHandler\SendEmailHandler"

# 4. Issue stamp (queues SendPushNotificationMessage)
curl -X POST http://localhost:8000/api/v1/stamps/issue \
  -H "Authorization: Bearer $MERCHANT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"customerId":"<id>","count":1}'
php bin/console messenger:consume async --limit=1 -vv
# Expect: "Push notification queued (Firebase not configured)" in logs
```

---

### ✅ Step 10 — Geolocation Reward Search & Analytics

**Geo reward search** — `GET /api/v1/rewards` now accepts `?lat`, `?lon`, `?radius` (km, default 10). When provided, results are sorted nearest-first and each item includes a `distanceKm` field. Uses Haversine formula with ACOS domain clamping for robustness.

**Analytics endpoints:**

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/api/v1/merchant/stats` | `ROLE_MERCHANT` | Merchant performance dashboard |
| `GET` | `/api/v1/admin/stats` | `ROLE_ADMIN` | Platform-wide aggregate stats |

**Merchant stats response shape:**
```json
{
  "totals": { "stampsIssued": 150, "rewardsRedeemed": "12.00", "customers": 45 },
  "last30Days": { "stampsIssued": 30, "redemptions": 3, "activeCustomers": 8 },
  "topRewards": [{ "id": "...", "title": "Free Coffee", "redemptions": 5 }]
}
```

**Admin stats response shape:**
```json
{
  "totals": { "customers": 500, "merchants": 50, "activeRewards": 120, "stampsIssued": 5000, "rewardsRedeemed": 200 },
  "last30Days": { "newCustomers": 40, "newMerchants": 5 }
}
```

**Quick test:**
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

# Geo search — rewards near a location, sorted by distance
curl "http://localhost:8000/api/v1/rewards?lat=12.97&lon=77.59&radius=20" \
  -H "Authorization: Bearer $TOKEN"
# Each reward includes "distanceKm" field; sorted nearest-first

# Non-geo search unchanged (no distanceKm field)
curl "http://localhost:8000/api/v1/rewards" -H "Authorization: Bearer $TOKEN"
```

---

### ✅ Step 12 — React Frontend

**Separate repo:** `/home/troth/snapstamp-frontend/`

**Stack:** Vite + React 18 + TypeScript, React Router v6, TanStack Query v5, Axios, Tailwind CSS v4, React Hook Form + Zod v4, react-hot-toast.

**Three portals, 17 pages:**

| Portal | Pages |
|---|---|
| Customer | Dashboard, Stamp Cards (list + detail), Rewards (geo-search toggle), Redemptions, Referral, Profile/OTP verification |
| Merchant | Dashboard (analytics), Rewards (list + create modal), Issue Stamps, Approve Redemptions, Profile editor |
| Admin | Platform stats dashboard, Merchants list (filter tabs + approve/reject modal) |

**Key features:**
- Axios interceptor: auto-attach Bearer token + silent token refresh on 401 (with queued request replay)
- `AuthContext`: role detection, localStorage persistence, `/api/v1/auth/me` rehydration on mount
- Role-based protected routes — wrong role is redirected to correct home, unauthenticated → `/login`
- Geo reward search: browser geolocation → `?lat&lon&radius` query, results sorted by `distanceKm`
- All 32 backend endpoints wired up — no mocked data

**Start:**
```bash
cd /home/troth/snapstamp-frontend
npm run dev          # http://localhost:5173

# Production build (0 TypeScript errors)
npm run build
```

---

### ✅ Step 11 — Referral System

Every customer receives a unique 8-character referral code (e.g. `ALIC4F9B`) at registration. Codes are returned in the auth response and exposed via a dedicated endpoint.

**Referral flow:**
1. Customer A registers → code `ALIC4F9B` auto-generated and stored
2. Customer B registers with `"referralCode": "ALIC4F9B"` in the request body
3. On success:
   - A's `referralCount` increments
   - A is credited **+5 bonus stamps** to their `totalStampsCollected` (counts toward tier progression)
   - A `REFERRAL_BONUS` transaction is recorded with `stamps: 5`
   - A `SYSTEM` in-app notification is persisted and a push notification dispatched: _"Bob joined using your referral code. You've earned 5 bonus stamps!"_
4. Invalid or unknown codes are silently ignored — registration always succeeds

**Endpoint:**

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/api/v1/customer/referral` | `ROLE_CUSTOMER` | Own referral code, count, bonus info, and referral list |

**Response shape:**
```json
{
  "referralCode": "ALIC4F9B",
  "referralCount": 2,
  "bonusStampsPerReferral": 5,
  "totalBonusStamps": 10,
  "referrals": [
    { "id": "...", "firstName": "Bob", "lastName": "Smith", "joinedAt": "2026-05-18T11:25:54+00:00" }
  ]
}
```

**Registration with referral code:**
```bash
# Register referrer — note referralCode in response
NEW=$(curl -s -X POST http://localhost:8000/api/v1/auth/register/customer \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"secret123","firstName":"Alice","lastName":"A","phone":"+919900000001"}')
CODE=$(echo $NEW | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['user']['referralCode'])")

# Register referred customer using Alice's code
curl -s -X POST http://localhost:8000/api/v1/auth/register/customer \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"bob@example.com\",\"password\":\"secret123\",\"firstName\":\"Bob\",\"lastName\":\"B\",\"phone\":\"+919900000002\",\"referralCode\":\"$CODE\"}"
# Alice now has +5 bonus stamps and her referralCount = 1

# Check Alice's referral stats
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")
curl http://localhost:8000/api/v1/customer/referral -H "Authorization: Bearer $TOKEN"
# Expect: referralCount=1, totalBonusStamps=5
```

**Key files:**
- `src/Service/ReferralService.php` — `generateUniqueCode()`, `processReferral()`, `getReferralStats()`
- `src/Controller/Api/CustomerReferralController.php` — `GET /api/v1/customer/referral`
- `src/Entity/Customer.php` — `referredBy` self-referencing FK, `referralCount`, `referralCode`
- `src/Enum/TransactionTypeEnum.php` — `REFERRAL_BONUS`

**Migration:** `referred_by_id` nullable FK on `users` table (self-reference).

---

### ✅ Step 8 — Razorpay Payment Integration

**Merchant endpoints** (`ROLE_MERCHANT` required):

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/v1/merchant/payment/subscribe` | Create a Razorpay subscription order |
| `GET`  | `/api/v1/merchant/payments` | List own payment history |

**Public endpoints** (no auth):

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/v1/payment/webhook` | Razorpay webhook receiver |

**Subscription plans:**

| Plan | Amount |
|---|---|
| `monthly` | ₹999 |
| `annual` | ₹9,999 |

**Key details:**
- Order+Webhook flow: server creates a Razorpay Order → frontend opens Razorpay checkout with `orderId` + `keyId` → Razorpay posts signed webhook to confirm result
- Webhook signature verified with `X-Razorpay-Signature` header using `RAZORPAY_WEBHOOK_SECRET`; invalid signature → 400
- Handles `payment.captured` (→ COMPLETED) and `payment.failed` (→ FAILED) events; unknown events logged and ignored with 200
- Idempotent: re-delivery of an already-COMPLETED webhook is a no-op
- Payment method (`UPI`, `CARD`, etc.) set from webhook payload; unknown methods skipped gracefully
- `Payment.customer` is nullable — merchant subscription payments have no associated customer

**Quick test:**

```bash
# Login as merchant
MTOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"shop@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

# Create subscription order (requires real Razorpay test-mode keys in .env)
curl -s -X POST http://localhost:8000/api/v1/merchant/payment/subscribe \
  -H "Authorization: Bearer $MTOKEN" \
  -H "Content-Type: application/json" \
  -d '{"plan":"monthly"}'
# Expect: 201, { orderId, amount: 99900, currency: "INR", keyId, receipt, plan }
# → Pass orderId + keyId to Razorpay.js on frontend to open checkout

# Simulate a valid webhook (replace WEBHOOK_SECRET with your value)
BODY='{"event":"payment.captured","payload":{"payment":{"entity":{"id":"pay_xxx","order_id":"order_xxx","method":"upi"}}}}'
SIG=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "your_webhook_secret" | awk '{print $2}')
curl -s -X POST http://localhost:8000/api/v1/payment/webhook \
  -H "Content-Type: application/json" \
  -H "X-Razorpay-Signature: $SIG" \
  -d "$BODY"
# Expect: 200 OK
```

---

### ✅ Step 7 — Reward Redemption Service

**Merchant endpoints** (`ROLE_MERCHANT` required):

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/v1/merchant/rewards` | Create a reward |
| `GET` | `/api/v1/merchant/rewards` | List own active rewards |
| `GET` | `/api/v1/merchant/redemptions` | List all redemptions for this merchant |
| `POST` | `/api/v1/merchant/redemptions/{redeemCode}/approve` | Approve a pending redemption |

**Customer endpoints** (`ROLE_CUSTOMER` required):

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/v1/rewards` | Browse all active rewards (optional `?merchantId=` filter) |
| `POST` | `/api/v1/rewards/{rewardId}/redeem` | Initiate a redemption |
| `GET` | `/api/v1/customer/redemptions` | List own redemption history |

**Key details:**
- Redemption requires a COMPLETED stamp card for the reward's merchant — no completed card → 400
- Guards prevent double-redeeming the same completed card (second attempt → 400)
- `redeemCode` auto-generated as `REDEEM-{16 hex chars}` on each redemption
- Two-step flow: customer initiates (status `PENDING`), merchant approves with code (status `COMPLETED`)
- Approval writes a `REWARD_REDEEMED` transaction record and sends two `REWARD_REDEEMED` notifications (one per step)
- `Reward.currentRedemptions` and `Customer.totalRewardsRedeemed` incremented on approval
- `maxRedemptions: null` means unlimited; `stampRequirement` defaults to 10

**Quick test:**

```bash
# Login as merchant
MTOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"shop@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

# Create a reward
REWARD_ID=$(curl -s -X POST http://localhost:8000/api/v1/merchant/rewards \
  -H "Authorization: Bearer $MTOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Free Coffee","description":"One free coffee","rewardType":"FREE_ITEM","value":"150.00","stampRequirement":10,"expiresAt":"2027-01-01T00:00:00+00:00"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['id'])")

# Customer redeems (must have a COMPLETED stamp card for this merchant)
CTOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"bob@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

REDEEM_CODE=$(curl -s -X POST "http://localhost:8000/api/v1/rewards/$REWARD_ID/redeem" \
  -H "Authorization: Bearer $CTOKEN" \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['redeemCode'])")
# Expect: 201, status=PENDING

# Merchant approves
curl -s -X POST "http://localhost:8000/api/v1/merchant/redemptions/$REDEEM_CODE/approve" \
  -H "Authorization: Bearer $MTOKEN"
# Expect: 200, status=COMPLETED, merchantApprovedAt set
```

---

### ✅ Step 4 — Email / Phone OTP Verification

**Endpoints:**

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/api/v1/verification/request` | Bearer token | Send OTP to email or phone |
| `POST` | `/api/v1/verification/verify` | Bearer token | Submit 6-digit code to verify |

**Key details:**
- 6-digit numeric codes, SHA-256 hashed before DB storage
- OTP TTL: 10 minutes · Max attempts: 5 · Resend cooldown: 60 seconds
- Old tokens are invalidated when a new OTP is requested
- Single-use — verified tokens cannot be reused
- Email delivery via Symfony Mailer (Mailtrap sandbox configured)
- SMS delivery via Twilio SDK (uses placeholder creds by default — set real `TWILIO_*` vars to enable)
- OTP codes logged to `var/log/dev.log` for dev testing

**Quick test:**

```bash
# 1. Login and grab token
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"you@example.com","password":"secret123"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['accessToken'])")

# 2. Request OTP — check Mailtrap inbox for the code
curl -s -X POST http://localhost:8000/api/v1/verification/request \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type":"email"}'

# 3. Submit the 6-digit code
curl -s -X POST http://localhost:8000/api/v1/verification/verify \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type":"email","code":"123456"}'
# Expect: {"success":true,"data":{"isEmailVerified":true,"isPhoneVerified":false}}
```

---

### ✅ Customer Loyalty Tier

Customers are automatically assigned a tier based on their **total lifetime stamps collected** (`Customer.totalStampsCollected`). The tier is computed on-the-fly in `Customer::getTier()` — no separate DB column needed.

**Tier thresholds:**

| Tier | Stamps required | How to reach it |
|---|---|---|
| BRONZE | 0 – 9 | Default for new customers |
| SILVER | 10 – 49 | Complete ~1 stamp card |
| GOLD | 50 – 99 | Complete ~5 stamp cards |
| PLATINUM | 100+ | Complete ~10 stamp cards |

**What counts toward stamps:**
- Every stamp issued by a merchant via `POST /api/v1/stamps/issue` increments `totalStampsCollected`
- Every successful referral awards the referrer **+5 bonus stamps**, also counted toward their tier

**Where tier is used:**
- Returned in the customer profile (`GET /api/v1/auth/me` and `GET /api/v1/customer/profile`)
- Passed to the Claude AI reward recommendation engine — higher-tier customers receive more personalised, premium suggestions
- Displayed on the customer dashboard and top bar in the frontend

**Example:** A customer who has collected 52 lifetime stamps (including 10 bonus stamps from 2 referrals) is **GOLD** tier and will receive AI-curated premium reward recommendations.

---

## API Reference

Base URL: `http://localhost:8000/api/v1`

**Standard response shape:**
```json
{ "success": true,  "message": "...", "data": {} }
{ "success": false, "message": "error reason" }
{ "success": false, "message": "Validation failed.", "errors": { "field": "message" } }
```

**HTTP status codes:**

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Created |
| `400` | Validation / domain error |
| `401` | Authentication failure |
| `403` | Authorization failure |
| `404` | Resource not found |
| `409` | Conflict (duplicate email/phone) |
| `429` | Too many requests (OTP resend cooldown) |

---

## Project Structure

```
src/
├── Command/            # Console commands (app:create-admin)
├── Controller/Api/     # Route handlers — thin, delegate to services
├── Dto/Auth/           # Auth input DTOs
├── Dto/Admin/          # Admin action DTOs
├── Dto/Merchant/       # Merchant profile DTOs
├── Dto/Verification/   # OTP input DTOs
├── Entity/             # Doctrine ORM entities
├── Enum/               # PHP 8.1+ string-backed enums
├── Repository/         # Custom Doctrine query methods
├── Security/           # JwtAuthenticator, UserProvider
└── Service/            # Business logic (AuthService, JwtService, StampService, ...)
```

---

## Data Model

```
User (abstract, users table, SINGLE_TABLE)
├── Customer   — ROLE_CUSTOMER
└── Merchant   — ROLE_MERCHANT

StampCard  (Customer ←→ Merchant)
└── Stamp  (individual stamps on a card)

Reward     (owned by Merchant)
└── RewardRedemption  (Customer redeeming a Reward)

Payment    (Customer → Merchant)
Transaction (immutable audit log)
Notification (Customer or Merchant)
MerchantCategory (lookup)
```

---

## Roadmap

- [x] Step 1 — Documentation & planning
- [x] Step 2 — Data layer (entities, enums, repositories, migrations)
- [x] Step 3 — JWT authentication
- [x] Step 4 — Email / phone OTP verification
- [x] Step 5 — Merchant onboarding & admin approval
- [x] Step 6 — Stamp issuance service
- [x] Step 7 — Reward redemption service
- [x] Step 8 — Razorpay payment integration
- [x] Step 9 — Async queue workers (Symfony Messenger)
- [x] Step 10 — Geolocation reward search & analytics
- [x] Step 11 — Referral system
- [x] Step 12 — React frontend (separate repo)

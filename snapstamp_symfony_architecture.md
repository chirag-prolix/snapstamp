# snapstamp Loyalty System - Symfony Architecture Design

## Executive Summary

This document outlines a production-ready Symfony architecture for snapstamp, a digital loyalty rewards platform connecting customers with local businesses across India. The system handles customer stamp collection, reward management, merchant integration, and payment processing through a scalable, modular approach.

**Target Stack:**
- Backend: Symfony 7.x (PHP 8.2+)
- Database: PostgreSQL
- Cache: Redis
- Queue: RabbitMQ (optional, for async operations)
- Frontend: React.js / Vue 3 (separate repo)
- Mobile: React Native / Flutter (future expansion)

---

## 1. High-Level Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     Client Applications                      │
│  (Web Browser | Mobile App | Merchant Dashboard)             │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│               API Gateway / Load Balancer                    │
│  (Nginx / AWS ELB - Rate Limiting, CORS, Logging)           │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│              Symfony REST API Layer                          │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Controllers → Services → Repositories               │    │
│  │ - Authentication (JWT/OAuth)                        │    │
│  │ - Customer Routes                                   │    │
│  │ - Merchant Routes                                   │    │
│  │ - Stamp Management Routes                           │    │
│  │ - Reward Routes                                     │    │
│  │ - Payment Routes                                    │    │
│  └─────────────────────────────────────────────────────┘    │
└──────────────────┬──────────────────────────────────────────┘
                   │
      ┌────────────┼────────────┬─────────────┐
      ▼            ▼            ▼             ▼
┌─────────┐  ┌──────────┐  ┌────────┐  ┌──────────┐
│PostgreSQL│  │  Redis   │  │RabbitMQ│  │File Storage│
│Database  │  │  Cache   │  │ Queue  │  │(S3/Local) │
└─────────┘  └──────────┘  └────────┘  └──────────┘
```

---

## 2. Directory Structure

```
snapstamp-backend/
├── bin/
│   ├── console
│   └── phpunit
├── config/
│   ├── packages/
│   │   ├── api_platform.yaml
│   │   ├── doctrine.yaml
│   │   ├── messenger.yaml
│   │   ├── redis.yaml
│   │   └── security.yaml
│   ├── routes/
│   │   ├── api_customers.yaml
│   │   ├── api_merchants.yaml
│   │   ├── api_stamps.yaml
│   │   ├── api_rewards.yaml
│   │   └── api_payments.yaml
│   ├── services.yaml
│   └── bundles.php
├── migrations/
│   ├── Version*.php
├── public/
│   ├── index.php
│   └── health
├── src/
│   ├── Command/
│   │   ├── GenerateReportCommand.php
│   │   ├── ProcessExpiredStampsCommand.php
│   │   └── SendNotificationsCommand.php
│   ├── Controller/
│   │   ├── Api/
│   │   │   ├── CustomerController.php
│   │   │   ├── MerchantController.php
│   │   │   ├── StampController.php
│   │   │   ├── RewardController.php
│   │   │   └── PaymentController.php
│   │   └── HealthController.php
│   ├── Entity/
│   │   ├── User.php
│   │   ├── Customer.php
│   │   ├── Merchant.php
│   │   ├── MerchantCategory.php
│   │   ├── StampCard.php
│   │   ├── Stamp.php
│   │   ├── Reward.php
│   │   ├── RewardRedemption.php
│   │   ├── Payment.php
│   │   ├── Notification.php
│   │   └── Transaction.php
│   ├── Repository/
│   │   ├── CustomerRepository.php
│   │   ├── MerchantRepository.php
│   │   ├── StampCardRepository.php
│   │   ├── StampRepository.php
│   │   ├── RewardRepository.php
│   │   └── PaymentRepository.php
│   ├── Service/
│   │   ├── StampService.php
│   │   ├── RewardService.php
│   │   ├── PaymentService.php
│   │   ├── NotificationService.php
│   │   ├── AuthService.php
│   │   └── ReportService.php
│   ├── EventListener/
│   │   ├── ExceptionListener.php
│   │   ├── StampCreatedListener.php
│   │   ├── RewardRedeemedListener.php
│   │   └── JwtListener.php
│   ├── Message/
│   │   ├── SendNotificationMessage.php
│   │   ├── ProcessPaymentMessage.php
│   │   └── GenerateReportMessage.php
│   ├── MessageHandler/
│   │   ├── SendNotificationHandler.php
│   │   ├── ProcessPaymentHandler.php
│   │   └── GenerateReportHandler.php
│   ├── Security/
│   │   ├── JwtAuthenticator.php
│   │   ├── UserProvider.php
│   │   └── Voter/
│   │       ├── StampCardVoter.php
│   │       └── MerchantVoter.php
│   ├── Dto/
│   │   ├── CreateStampDto.php
│   │   ├── CreateRewardDto.php
│   │   ├── RedeemRewardDto.php
│   │   ├── ProcessPaymentDto.php
│   │   └── PaginationDto.php
│   ├── Exception/
│   │   ├── InvalidStampException.php
│   │   ├── InsufficientStampsException.php
│   │   ├── PaymentFailedException.php
│   │   └── MerchantNotFoundException.php
│   ├── Validator/
│   │   ├── UniqueEmailValidator.php
│   │   └── ValidPhoneValidator.php
│   ├── Utils/
│   │   ├── JwtTokenGenerator.php
│   │   ├── StampCardGenerator.php
│   │   └── QrCodeGenerator.php
│   └── Kernel.php
├── tests/
│   ├── Unit/
│   │   ├── Service/
│   │   ├── Repository/
│   │   └── Validator/
│   ├── Integration/
│   │   ├── Controller/
│   │   └── Service/
│   ├── Functional/
│   │   └── Api/
│   └── TestCase.php
├── .env
├── .env.example
├── composer.json
├── symfony.lock
├── phpunit.xml.dist
└── README.md
```

---

## 3. Database Schema (Entity Relationship Diagram)

### Core Entities

#### 3.1 User (Base Authentication Entity)
```php
/**
 * User - Abstract base for Customer/Merchant
 */
Entity: User
├── id (UUID, Primary Key)
├── email (String, Unique)
├── phone (String, Unique)
├── passwordHash (String)
├── firstName (String)
├── lastName (String)
├── userType (Enum: CUSTOMER, MERCHANT) 
├── status (Enum: ACTIVE, INACTIVE, SUSPENDED, DELETED)
├── isEmailVerified (Boolean)
├── isPhoneVerified (Boolean)
├── createdAt (DateTime)
├── updatedAt (DateTime)
└── deletedAt (DateTime, Soft Delete)
```

#### 3.2 Customer
```php
Entity: Customer (extends User)
├── id (UUID, FK to User)
├── displayName (String)
├── profilePictureUrl (String)
├── dateOfBirth (Date, Nullable)
├── gender (Enum: MALE, FEMALE, OTHER, Nullable)
├── city (String)
├── state (String)
├── country (String)
├── latitude (Float, Nullable)
├── longitude (Float, Nullable)
├── preferredLanguage (String, Default: 'en')
├── totalStampsCollected (Integer, Cached)
├── totalRewardsRedeemed (Integer, Cached)
├── totalSpent (Decimal, Cached)
├── tier (Enum: BRONZE, SILVER, GOLD, PLATINUM, Computed)
├── referralCode (String, Unique, Indexed)
├── referralCount (Integer, Cached)
├── lastActiveAt (DateTime)
├── stampCards (OneToMany → StampCard)
├── notifications (OneToMany → Notification)
└── preferences (JSON, Nullable)
```

#### 3.3 Merchant
```php
Entity: Merchant (extends User)
├── id (UUID, FK to User)
├── businessName (String, Indexed)
├── businessDescription (Text)
├── businessLogo (String)
├── category (ManyToOne → MerchantCategory)
├── subcategory (String)
├── latitude (Float)
├── longitude (Float)
├── city (String)
├── state (String)
├── country (String)
├── address (String)
├── postalCode (String)
├── phoneForBusiness (String)
├── website (String, Nullable)
├── taxId (String, Unique)
├── bankAccountNumber (String, Encrypted)
├── bankIfscCode (String)
├── bankAccountHolderName (String)
├── averageRating (Decimal, Cached)
├── totalCustomers (Integer, Cached)
├── totalStampsIssued (Integer, Cached)
├── totalRewardsGiven (Decimal, Cached)
├── commissionRate (Decimal, Default: 0.05)
├── isVerified (Boolean)
├── verificationDocuments (JSON)
├── onboardingStatus (Enum: PENDING, VERIFIED, REJECTED, ACTIVE)
├── stampCards (OneToMany → StampCard)
├── rewards (OneToMany → Reward)
├── transactions (OneToMany → Transaction)
├── apiKey (String, Unique, Encrypted, Indexed)
├── apiSecret (String, Encrypted)
├── webhookUrl (String, Nullable)
├── lastApiAccessAt (DateTime, Nullable)
├── termsAcceptedAt (DateTime)
└── preferences (JSON, Nullable)
```

#### 3.4 MerchantCategory
```php
Entity: MerchantCategory
├── id (UUID, Primary Key)
├── name (String, Unique, Indexed)
├── slug (String, Unique)
├── description (String)
├── icon (String)
├── color (String)
├── merchants (OneToMany → Merchant)
└── createdAt (DateTime)
```

#### 3.5 StampCard
```php
Entity: StampCard
├── id (UUID, Primary Key)
├── merchant (ManyToOne → Merchant, Indexed)
├── customer (ManyToOne → Customer, Indexed)
├── cardNumber (String, Unique, Indexed)
├── displayName (String)
├── totalSlotsRequired (Integer, Default: 10)
├── currentStampCount (Integer, Default: 0)
├── stamps (OneToMany → Stamp)
├── status (Enum: ACTIVE, COMPLETED, EXPIRED, CANCELLED)
├── createdAt (DateTime, Indexed)
├── lastStampAt (DateTime, Nullable)
├── completedAt (DateTime, Nullable)
├── expiresAt (DateTime, Indexed)
├── bonusStamps (Integer, Default: 0)
├── metadata (JSON, Nullable) // Custom data per merchant
└── isDigital (Boolean, Default: true)
```

#### 3.6 Stamp
```php
Entity: Stamp
├── id (UUID, Primary Key)
├── stampCard (ManyToOne → StampCard, Indexed)
├── customer (ManyToOne → Customer, Indexed)
├── merchant (ManyToOne → Merchant, Indexed)
├── stampSequence (Integer)
├── isBonus (Boolean, Default: false)
├── collectedAt (DateTime, Indexed)
├── expiresAt (DateTime, Indexed)
├── status (Enum: ACTIVE, EXPIRED, REDEEMED)
├── transactionId (String, FK from Payment, Nullable)
├── metadata (JSON) // Store device info, location, etc.
└── notes (String, Nullable)
```

#### 3.7 Reward
```php
Entity: Reward
├── id (UUID, Primary Key)
├── merchant (ManyToOne → Merchant, Indexed)
├── title (String, Indexed)
├── description (String)
├── rewardType (Enum: DISCOUNT, FREE_ITEM, CASHBACK, EXPERIENCE)
├── value (Decimal) // Discount % or Rupees
├── currency (String, Default: 'INR')
├── stampRequirement (Integer) // Min stamps needed
├── maxRedemptions (Integer, Nullable) // Null = Unlimited
├── currentRedemptions (Integer, Default: 0, Cached)
├── expiresAt (DateTime, Indexed)
├── imageUrl (String)
├── terms (Text)
├── redemptions (OneToMany → RewardRedemption)
├── status (Enum: ACTIVE, INACTIVE, EXPIRED, DRAFT)
├── createdAt (DateTime)
├── updatedAt (DateTime)
└── metadata (JSON, Nullable)
```

#### 3.8 RewardRedemption
```php
Entity: RewardRedemption
├── id (UUID, Primary Key)
├── reward (ManyToOne → Reward, Indexed)
├── customer (ManyToOne → Customer, Indexed)
├── stampCard (ManyToOne → StampCard, Nullable, Indexed)
├── redeemedAt (DateTime, Indexed)
├── status (Enum: PENDING, COMPLETED, CANCELLED, EXPIRED)
├── redeemCode (String, Unique, Indexed)
├── voucherUrl (String, Nullable)
├── merchantApprovedAt (DateTime, Nullable)
├── approvedByMerchant (ManyToOne → Merchant, Nullable)
├── notes (String, Nullable)
└── metadata (JSON, Nullable)
```

#### 3.9 Payment
```php
Entity: Payment
├── id (UUID, Primary Key)
├── transactionId (String, Unique, Indexed)
├── paymentGateway (Enum: RAZORPAY, INSTAMOJO, PAYPAL)
├── paymentGatewayId (String, Unique, Indexed)
├── customer (ManyToOne → Customer, Indexed)
├── merchant (ManyToOne → Merchant, Nullable, Indexed)
├── amount (Decimal)
├── currency (String, Default: 'INR')
├── paymentType (Enum: REWARD_CASH_OUT, MERCHANT_PAYOUT, DEPOSIT)
├── status (Enum: INITIATED, PENDING, COMPLETED, FAILED, REFUNDED)
├── paymentMethod (Enum: CARD, UPI, WALLET, BANK_TRANSFER)
├── failureReason (String, Nullable)
├── retryCount (Integer, Default: 0)
├── createdAt (DateTime, Indexed)
├── processedAt (DateTime, Nullable)
├── webhookReceivedAt (DateTime, Nullable)
├── receiptUrl (String, Nullable)
├── metadata (JSON, Nullable)
└── ipAddress (String, Nullable)
```

#### 3.10 Transaction
```php
Entity: Transaction
├── id (UUID, Primary Key)
├── transactionType (Enum: STAMP_ISSUED, STAMP_EXPIRED, REWARD_REDEEMED, PAYMENT_RECEIVED, PAYOUT_ISSUED, REFUND)
├── customer (ManyToOne → Customer, Nullable, Indexed)
├── merchant (ManyToOne → Merchant, Nullable, Indexed)
├── amount (Decimal, Nullable)
├── stamps (Integer, Nullable)
├── referenceId (String, Nullable) // FK to Stamp/Reward/Payment
├── status (Enum: COMPLETED, PENDING, FAILED)
├── createdAt (DateTime, Indexed)
├── description (String)
└── metadata (JSON, Nullable)
```

#### 3.11 Notification
```php
Entity: Notification
├── id (UUID, Primary Key)
├── customer (ManyToOne → Customer, Indexed)
├── merchant (ManyToOne → Merchant, Nullable, Indexed)
├── title (String)
├── message (String)
├── type (Enum: STAMP_RECEIVED, REWARD_AVAILABLE, REWARD_REDEEMED, PROMOTION, SYSTEM)
├── channels (JSON) // Array: [EMAIL, SMS, PUSH]
├── isRead (Boolean, Default: false)
├── readAt (DateTime, Nullable)
├── sentAt (DateTime, Indexed)
├── createdAt (DateTime)
├── externalMessageId (String, Nullable)
└── metadata (JSON, Nullable)
```

### Database Relationships Summary
```
Customer ──┬─→ StampCard ──→ Stamp
           ├─→ RewardRedemption ──→ Reward ←─→ Merchant
           ├─→ Payment
           ├─→ Transaction
           └─→ Notification

Merchant ──┬─→ StampCard
           ├─→ Reward
           ├─→ Payment
           ├─→ Transaction
           ├─→ MerchantCategory
           └─→ Notification
```

---

## 4. API Endpoints Structure

### 4.1 Authentication Endpoints
```
POST   /api/v1/auth/register              # Customer/Merchant registration
POST   /api/v1/auth/login                 # Login (JWT token)
POST   /api/v1/auth/refresh-token         # Refresh JWT
POST   /api/v1/auth/logout                # Logout (blacklist token)
POST   /api/v1/auth/forgot-password       # Request password reset
POST   /api/v1/auth/reset-password        # Reset password
POST   /api/v1/auth/verify-email          # Verify email OTP
POST   /api/v1/auth/verify-phone          # Verify phone OTP
POST   /api/v1/auth/resend-otp            # Resend OTP
```

### 4.2 Customer Endpoints
```
GET    /api/v1/customers/profile          # Get customer profile
PATCH  /api/v1/customers/profile          # Update profile
GET    /api/v1/customers/dashboard        # Dashboard summary
GET    /api/v1/customers/stamp-cards      # List stamp cards (paginated)
GET    /api/v1/customers/stamp-cards/{id} # Get single stamp card
GET    /api/v1/customers/rewards          # Available rewards (nearby/all)
GET    /api/v1/customers/rewards/{id}     # Get reward details
POST   /api/v1/customers/rewards/{id}/redeem  # Redeem reward
GET    /api/v1/customers/redemptions      # My redemptions history
GET    /api/v1/customers/transactions     # Transaction history
GET    /api/v1/customers/notifications    # Notification list
POST   /api/v1/customers/notifications/{id}/read # Mark as read
POST   /api/v1/customers/referral         # Get referral code
POST   /api/v1/customers/referral/invite  # Invite friend
GET    /api/v1/customers/settings         # Get preferences
PATCH  /api/v1/customers/settings         # Update preferences
```

### 4.3 Merchant Endpoints
```
GET    /api/v1/merchants/profile          # Get merchant profile
PATCH  /api/v1/merchants/profile          # Update profile
GET    /api/v1/merchants/dashboard        # Merchant dashboard
GET    /api/v1/merchants/customers        # List customers (paginated)
GET    /api/v1/merchants/stamp-cards      # Created stamp cards
POST   /api/v1/merchants/stamp-cards      # Create stamp card
GET    /api/v1/merchants/stamp-cards/{id} # Get stamp card details
PATCH  /api/v1/merchants/stamp-cards/{id} # Update stamp card
DELETE /api/v1/merchants/stamp-cards/{id} # Delete stamp card
GET    /api/v1/merchants/rewards          # List own rewards
POST   /api/v1/merchants/rewards          # Create reward
GET    /api/v1/merchants/rewards/{id}     # Get reward details
PATCH  /api/v1/merchants/rewards/{id}     # Update reward
DELETE /api/v1/merchants/rewards/{id}     # Delete reward
GET    /api/v1/merchants/analytics        # Analytics dashboard
GET    /api/v1/merchants/transactions     # Transaction history
GET    /api/v1/merchants/payouts          # Payout history
POST   /api/v1/merchants/payouts/request  # Request payout
GET    /api/v1/merchants/settings         # Get settings
PATCH  /api/v1/merchants/settings         # Update settings
POST   /api/v1/merchants/api-keys         # Generate API key
```

### 4.4 Stamp Endpoints
```
POST   /api/v1/stamps                     # Issue stamp (Merchant auth)
POST   /api/v1/stamps/verify              # Verify stamp QR
GET    /api/v1/stamps/{id}                # Get stamp details
POST   /api/v1/stamps/{id}/validate       # Backend validation
DELETE /api/v1/stamps/{id}                # Cancel stamp (Merchant only)
```

### 4.5 Reward Endpoints
```
GET    /api/v1/rewards                    # List all rewards (public)
GET    /api/v1/rewards/{id}               # Get reward details
POST   /api/v1/rewards/{id}/redeem        # Redeem reward
GET    /api/v1/rewards/nearby             # Rewards nearby (geolocation)
POST   /api/v1/rewards/search             # Search rewards
```

### 4.6 Payment Endpoints
```
POST   /api/v1/payments/initiate          # Start payment
POST   /api/v1/payments/webhook           # Payment gateway webhook
GET    /api/v1/payments/{id}              # Get payment status
POST   /api/v1/payments/{id}/retry        # Retry failed payment
```

### 4.7 Admin Endpoints (Protected)
```
GET    /api/v1/admin/statistics           # Platform statistics
GET    /api/v1/admin/users                # User management
GET    /api/v1/admin/merchants            # Merchant management
POST   /api/v1/admin/merchants/{id}/verify  # Verify merchant
POST   /api/v1/admin/users/{id}/suspend   # Suspend user
GET    /api/v1/admin/reports              # Generate reports
```

---

## 5. Core Services Architecture

### 5.1 StampService
**Responsibilities:** Stamp lifecycle management

```php
class StampService {
    // Issue stamp to customer (called by Merchant)
    public function issueStamp(
        StampCard $stampCard, 
        CreateStampDto $dto
    ): Stamp

    // Verify stamp authenticity
    public function verifyStamp(string $stampId): bool

    // Expire old stamps (Cron job)
    public function expireOldStamps(): int

    // Check if stamp card is complete
    public function isCardComplete(StampCard $card): bool

    // Add bonus stamps
    public function addBonusStamps(
        StampCard $card, 
        int $count, 
        string $reason
    ): void

    // Get stamp card statistics
    public function getStampCardStats(StampCard $card): array
}
```

### 5.2 RewardService
**Responsibilities:** Reward management and redemption

```php
class RewardService {
    // Create reward by merchant
    public function createReward(
        Merchant $merchant, 
        CreateRewardDto $dto
    ): Reward

    // Check if customer can redeem
    public function canCustomerRedeem(
        Customer $customer, 
        Reward $reward
    ): bool

    // Redeem reward
    public function redeemReward(
        Customer $customer, 
        Reward $reward, 
        ?StampCard $stampCard = null
    ): RewardRedemption

    // Approve redemption (by merchant)
    public function approveRedemption(
        RewardRedemption $redemption, 
        Merchant $merchant
    ): void

    // Get nearby rewards
    public function getNearbyRewards(
        float $latitude, 
        float $longitude, 
        float $radiusKm = 5
    ): array

    // Search rewards
    public function searchRewards(array $filters): array
}
```

### 5.3 PaymentService
**Responsibilities:** Payment processing and integration

```php
class PaymentService {
    // Initialize payment
    public function initiatePayment(
        ProcessPaymentDto $dto
    ): array // Returns gateway redirect URL

    // Handle webhook
    public function handleGatewayWebhook(array $payload): void

    // Process successful payment
    private function processSuccessfulPayment(Payment $payment): void

    // Handle failed payment
    private function handleFailedPayment(Payment $payment): void

    // Request merchant payout
    public function requestMerchantPayout(
        Merchant $merchant, 
        Decimal $amount
    ): Payment

    // Get payment status
    public function getPaymentStatus(string $paymentId): array

    // Refund payment
    public function refundPayment(Payment $payment): bool
}
```

### 5.4 NotificationService
**Responsibilities:** Multi-channel notifications

```php
class NotificationService {
    // Send notification to customer
    public function notifyCustomer(
        Customer $customer, 
        string $title, 
        string $message, 
        array $channels = ['PUSH', 'EMAIL']
    ): Notification

    // Send SMS
    private function sendSms(Customer $customer, string $message): bool

    // Send email
    private function sendEmail(Customer $customer, array $data): bool

    // Send push notification
    private function sendPush(Customer $customer, array $data): bool

    // Get unread notifications
    public function getUnreadNotifications(Customer $customer): array

    // Mark as read
    public function markAsRead(Notification $notification): void
}
```

### 5.5 AuthService
**Responsibilities:** Authentication and JWT management

```php
class AuthService {
    // Register user
    public function register(array $data): User

    // Login
    public function login(string $email, string $password): array // Returns token

    // Refresh token
    public function refreshToken(string $token): string

    // Logout (blacklist)
    public function logout(string $token): void

    // Send OTP
    public function sendOtp(string $email, string $type): void

    // Verify OTP
    public function verifyOtp(string $email, string $otp): bool

    // Reset password
    public function resetPassword(string $email, string $token, string $newPassword): void
}
```

### 5.6 ReportService
**Responsibilities:** Analytics and reporting

```php
class ReportService {
    // Get platform statistics
    public function getPlatformStats(): array

    // Get merchant analytics
    public function getMerchantAnalytics(Merchant $merchant): array

    // Get customer insights
    public function getCustomerInsights(Customer $customer): array

    // Generate CSV report (async)
    public function generateReport(array $filters): void

    // Get revenue breakdown
    public function getRevenueBreakdown(DatePeriod $period): array
}
```

---

## 6. Security Architecture

### 6.1 Authentication
```php
// JWT-based authentication
// Token structure:
{
  "sub": "customer_id",
  "email": "user@example.com",
  "role": "CUSTOMER|MERCHANT|ADMIN",
  "iat": 1234567890,
  "exp": 1234571490,
  "jti": "unique_token_id"
}

// Implementation:
- Secret key: Environment variable
- Algorithm: RS256 (asymmetric)
- TTL: 24 hours
- Refresh token: 7 days
- Token blacklist: Redis cache
```

### 6.2 Authorization (Role-Based)
```php
// Roles:
- ROLE_CUSTOMER: Access personal data, rewards, stamp cards
- ROLE_MERCHANT: Manage business, issue stamps, manage rewards
- ROLE_ADMIN: Full platform access

// Security Voters:
- StampCardVoter: Only owner/merchant can view/modify
- MerchantVoter: Only merchant can modify own data
```

### 6.3 Data Protection
```php
// Encryption:
- Sensitive fields: bank account, API secrets (Doctrine encryption)
- TLS/SSL: All API calls
- HTTPS: Force redirect

// Input Validation:
- DTO validation (Symfony Validator)
- SQL injection prevention: Doctrine ORM
- CSRF protection: SameSite cookies
- Rate limiting: API Gateway level

// Audit:
- Soft deletes for data recovery
- Transaction logging
- API access logging
```

---

## 7. Performance & Scalability Strategy

### 7.1 Caching Layers
```php
// Query cache (Redis):
- Customer profile data (TTL: 1 hour)
- Merchant details (TTL: 2 hours)
- Nearby rewards (TTL: 30 minutes)
- User session cache (TTL: 24 hours)

// Application-level cache:
- Stamp card counts (Cached entity property)
- Total rewards issued (Cached decimal)
- Merchant ratings (Cached average)

// Cache keys pattern:
customer:{id}:profile
merchant:{id}:details
rewards:{merchant_id}:list
nearby_rewards:{lat}:{lon}:{radius}
```

### 7.2 Database Optimization
```php
// Indexing strategy:
- B-tree indexes: id, email, phone, foreign keys
- Hash indexes: API keys, tokens
- Full-text indexes: business name, reward title
- Geospatial indexes: latitude/longitude

// Query optimization:
- Eager loading with JOIN FETCH
- Query batching
- Read replicas for analytics
- Archive old transactions (>2 years)

// Connection pooling:
- PgBouncer: 50-100 connections
- Replica lag tolerance: <1 second
```

### 7.3 Asynchronous Processing
```php
// Message Queue (RabbitMQ):
- SendNotificationMessage: Async email/SMS/push
- ProcessPaymentMessage: Async payment validation
- GenerateReportMessage: Heavy analytics
- ExpireStampsMessage: Scheduled cron
- SendMerchantPayoutMessage: Batch processing

// Priority:
- High: Payment webhooks
- Medium: Notifications
- Low: Reports, analytics
```

---

## 8. Implementation Roadmap

### Phase 1: MVP (8-10 weeks)
**Core Features:**
- Customer registration/login
- Merchant onboarding
- Basic stamp issuance
- Simple rewards
- Email notifications
- Razorpay integration

**Deliverables:**
- ✅ Database schema
- ✅ User authentication (JWT)
- ✅ Customer stamp cards API
- ✅ Merchant reward management API
- ✅ Payment processing
- ✅ Unit tests (>70% coverage)

### Phase 2: Enhancements (6-8 weeks)
**Features:**
- Push notifications (Firebase)
- SMS notifications (Twilio)
- Advanced analytics dashboard
- Referral system
- Geolocation-based rewards
- Merchant verification system
- Customer tier system

**Deliverables:**
- ✅ Push notification service
- ✅ Analytics endpoints
- ✅ Geospatial queries
- ✅ Referral service

### Phase 3: Scale & Optimize (4-6 weeks)
**Tasks:**
- Load testing (1M+ users)
- Redis optimization
- CDN for static assets
- Database read replicas
- Monitoring & alerting (DataDog/New Relic)
- Performance tuning

**Deliverables:**
- ✅ Production deployment
- ✅ Monitoring dashboard
- ✅ Auto-scaling configuration

### Phase 4: Advanced Features (Ongoing)
- Loyalty tier system (Bronze → Platinum)
- Merchant leaderboards
- Customer recommendations engine
- Integration with more payment gateways
- Mobile app (React Native)
- Admin portal (separate Vue.js app)

---

## 9. Deployment Architecture

### 9.1 Development Environment
```yaml
Local Setup:
- PHP 8.2 (via Docker)
- PostgreSQL 15
- Redis 7.x
- RabbitMQ 3.12
- Symfony local server
```

### 9.2 Production Environment
```yaml
Infrastructure (AWS/DigitalOcean):
├── Load Balancer
│   └── Nginx (SSL termination)
├── Application Tier
│   ├── EC2/Droplet 1 (Symfony app)
│   ├── EC2/Droplet 2 (Symfony app)
│   └── EC2/Droplet 3 (Symfony app)
├── Data Tier
│   ├── RDS PostgreSQL (Primary + Read Replica)
│   ├── ElastiCache Redis (Cluster mode)
│   ├── RabbitMQ (Managed or self-hosted)
│   └── S3 (Document/image storage)
├── Monitoring
│   ├── CloudWatch / DataDog
│   ├── ELK Stack (Logs)
│   └── Sentry (Error tracking)
└── Backup
    ├── Automated daily RDS snapshots
    └── S3 cross-region backup
```

### 9.3 Docker Compose for Local
```yaml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "8000:8000"
    environment:
      - DATABASE_URL=postgresql://user:pass@db:5432/snapstamp
      - REDIS_URL=redis://redis:6379
      - RABBITMQ_URL=amqp://guest:guest@rabbitmq:5672
  
  db:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: snapstamp
      POSTGRES_PASSWORD: password
    volumes:
      - pg_data:/var/lib/postgresql/data
  
  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
  
  rabbitmq:
    image: rabbitmq:3.12-management-alpine
    ports:
      - "15672:15672"
    environment:
      RABBITMQ_DEFAULT_USER: guest
      RABBITMQ_DEFAULT_PASS: guest

volumes:
  pg_data:
  redis_data:
```

---

## 10. Testing Strategy

### 10.1 Unit Tests (50% of code)
```php
// Example: StampService test
class StampServiceTest extends TestCase {
    public function testIssueStampAddsToCard(): void { }
    public function testCannotIssueDuplicateStamp(): void { }
    public function testExpiredStampsAreInvalidated(): void { }
    public function testBonusStampsCalculatedCorrectly(): void { }
}
```

### 10.2 Integration Tests (30% of code)
```php
// Example: Customer API test
class CustomerStampCardControllerTest extends WebTestCase {
    public function testCreateStampCardSuccessfully(): void { }
    public function testOnlyMerchantCanCreateStampCard(): void { }
    public function testStampCardExpirationWorks(): void { }
}
```

### 10.3 Functional Tests (20% of code)
```php
// Example: Full user journey test
class LoyaltyJourneyTest extends WebTestCase {
    public function testCompleteCustomerJourney(): void {
        // Register → Get stamp card → Collect stamps → Redeem → Receive cashback
    }
}
```

### 10.4 Load Testing
```bash
# Using Apache JMeter or k6
# Scenarios:
- 1000 concurrent users issuing stamps
- 500 simultaneous reward redemptions
- API response time <200ms (p99)
- Database connection pool stress test
```

---

## 11. Key Dependencies

```json
{
  "php": "^8.2",
  "symfony/symfony": "^7.0",
  "doctrine/orm": "^3.0",
  "api-platform/core": "^3.0",
  "firebase/php-jwt": "^6.0",
  "symfony/messenger": "^7.0",
  "symfony/serializer": "^7.0",
  "symfony/validator": "^7.0",
  "symfony/cache": "^7.0",
  "predis/predis": "^2.0",
  "razorpay/razorpay": "^2.0",
  "twilio/sdk": "^8.0",
  "google/cloud-storage": "^1.0",
  "amphp/http-client": "^5.0",
  "phpunit/phpunit": "^11.0",
  "symfony/test-pack": "^1.0"
}
```

---

## 12. Monitoring & Observability

### 12.1 Metrics to Track
```php
// Application metrics:
- API response times (p50, p95, p99)
- Error rates by endpoint
- Database query performance
- Cache hit ratios
- Message queue depth
- Active concurrent users

// Business metrics:
- New customer signups
- Active merchants
- Total stamps issued
- Reward redemption rate
- Payment success rate
- Customer churn rate
```

### 12.2 Alerting Rules
```yaml
High Priority:
  - API error rate > 5%
  - Database connection errors > 10
  - Payment gateway timeout > 30s
  - Redis cache unavailable

Medium Priority:
  - API response time p99 > 500ms
  - Message queue depth > 10000
  - Disk usage > 80%

Low Priority:
  - API response time p95 > 200ms
  - Cache hit ratio < 70%
```

---

## 13. Conclusion & Next Steps

This architecture provides:
✅ Scalability: Supports 1M+ users with horizontal scaling
✅ Security: JWT auth, encryption, audit logging
✅ Performance: Redis caching, async processing, query optimization
✅ Maintainability: Service layer, repositories, DTOs
✅ Extensibility: Event-driven, message queues, webhooks

**Next Steps:**
1. Set up local development environment (Docker Compose)
2. Create database migrations
3. Implement authentication service
4. Build customer & merchant controllers
5. Integrate payment gateway
6. Add comprehensive tests
7. Deploy to staging
8. Load testing & optimization
9. Production deployment

---

**Document Version:** 1.0  
**Last Updated:** May 2026  
**Author:** Symfony Architecture Team  
**Status:** Ready for Implementation

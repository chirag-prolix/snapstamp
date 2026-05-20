# snapstamp Loyalty System - Symfony Implementation Guide
## Hands-On Code Examples & Configuration

---

## Part 1: Project Setup & Configuration

### 1.1 Create Symfony Project
```bash
# Create new Symfony project
symfony new snapstamp-api --version=7.0 --full

cd snapstamp-api

# Install additional packages
composer require api-platform/core
composer require firebase/php-jwt
composer require symfony/messenger
composer require predis/predis
composer require razorpay/razorpay
composer require twilio/sdk
composer require --dev symfony/test-pack phpunit/phpunit
```

### 1.2 Environment Configuration (.env)
```dotenv
###> Database ###
DATABASE_URL="postgresql://snapstamp_user:snapstamp_pass@127.0.0.1:5432/snapstamp_db"
###< Database ###

###> Redis ###
REDIS_URL=redis://127.0.0.1:6379
###< Redis ###

###> Messenger (RabbitMQ) ###
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@127.0.0.1:5672/
###< Messenger ###

###> JWT ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_TTL=86400
JWT_REFRESH_TTL=604800
###< JWT ###

###> Payment Gateway ###
RAZORPAY_KEY_ID=your_key_id
RAZORPAY_KEY_SECRET=your_key_secret
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
###< Payment Gateway ###

###> Notification Services ###
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+1234567890
FIREBASE_API_KEY=your_firebase_key
###< Notification Services ###

###> Application ###
APP_ENV=dev
APP_SECRET=MySecretKeyChangeInProduction
APP_DEBUG=true
###< Application ###
```

---

## Part 2: Entity Definitions

### 2.1 User Entity (Base Class)
```php
// src/Entity/User.php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'user_type', type: 'string')]
#[ORM\DiscriminatorMap(['customer' => Customer::class, 'merchant' => Merchant::class])]
abstract class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: false)]
    #[Assert\Email(message: 'Invalid email address')]
    private string $email;

    #[ORM\Column(type: 'string', length: 20, unique: true, nullable: false)]
    #[Assert\Regex(pattern: '/^\+?[1-9]\d{1,14}$/', message: 'Invalid phone number')]
    private string $phone;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private string $lastName;

    #[ORM\Column(type: 'string', enumType: UserStatusEnum::class, options: ['default' => 'ACTIVE'])]
    private UserStatusEnum $status = UserStatusEnum::ACTIVE;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isEmailVerified = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPhoneVerified = false;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $roles = [];

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters
    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower($email);
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
        return $this;
    }

    public function getRoles(): array
    {
        return array_unique(array_merge($this->roles, ['ROLE_USER']));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getStatus(): UserStatusEnum
    {
        return $this->status;
    }

    public function setStatus(UserStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
```

### 2.2 Customer Entity
```php
// src/Entity/Customer.php
namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer extends User
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePictureUrl = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeImmutable $dateOfBirth = null;

    #[ORM\Column(type: 'string', enumType: GenderEnum::class, nullable: true)]
    private ?GenderEnum $gender = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $country = 'IN';

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'en'])]
    private string $preferredLanguage = 'en';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalStampsCollected = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalRewardsRedeemed = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    private string $totalSpent = '0.00';

    #[ORM\Column(type: 'string', length: 20, unique: true, nullable: true)]
    private ?string $referralCode = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $referralCount = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $lastActiveAt = null;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $preferences = [];

    #[ORM\OneToMany(targetEntity: StampCard::class, mappedBy: 'customer', cascade: ['remove'])]
    private Collection $stampCards;

    #[ORM\OneToMany(targetEntity: RewardRedemption::class, mappedBy: 'customer', cascade: ['remove'])]
    private Collection $redemptions;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'customer', cascade: ['remove'])]
    private Collection $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->stampCards = new ArrayCollection();
        $this->redemptions = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->setRoles(['ROLE_CUSTOMER']);
    }

    // Getters and setters
    public function getDisplayName(): ?string
    {
        return $this->displayName ?? $this->getFullName();
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    public function setReferralCode(?string $referralCode): self
    {
        $this->referralCode = $referralCode;
        return $this;
    }

    public function getStampCards(): Collection
    {
        return $this->stampCards;
    }

    public function addStampCard(StampCard $stampCard): self
    {
        if (!$this->stampCards->contains($stampCard)) {
            $this->stampCards->add($stampCard);
            $stampCard->setCustomer($this);
        }
        return $this;
    }

    public function getTotalStampsCollected(): int
    {
        return $this->totalStampsCollected;
    }

    public function incrementTotalStamps(int $count = 1): self
    {
        $this->totalStampsCollected += $count;
        return $this;
    }

    public function getRedemptions(): Collection
    {
        return $this->redemptions;
    }

    public function getTier(): string
    {
        return match(true) {
            $this->totalSpent >= 50000 => 'PLATINUM',
            $this->totalSpent >= 20000 => 'GOLD',
            $this->totalSpent >= 5000 => 'SILVER',
            default => 'BRONZE',
        };
    }
}
```

### 2.3 Merchant Entity
```php
// src/Entity/Merchant.php
namespace App\Entity;

use App\Repository\MerchantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MerchantRepository::class)]
class Merchant extends User
{
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Business name is required')]
    private string $businessName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $businessDescription = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $businessLogoUrl = null;

    #[ORM\ManyToOne(targetEntity: MerchantCategory::class)]
    #[ORM\JoinColumn(nullable: false)]
    private MerchantCategory $category;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $subcategory = null;

    #[ORM\Column(type: 'float', nullable: false)]
    private float $latitude;

    #[ORM\Column(type: 'float', nullable: false)]
    private float $longitude;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private string $city;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private string $state;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private string $country = 'IN';

    #[ORM\Column(type: 'text', nullable: false)]
    private string $address;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    private string $phoneForBusiness;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: 'string', length: 20, unique: true, nullable: false)]
    private string $taxId;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $bankAccountNumber; // Encrypted in real app

    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    private string $bankIfscCode;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $bankAccountHolderName;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2, options: ['default' => 0.05])]
    private string $commissionRate = '0.05';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', enumType: MerchantStatusEnum::class, options: ['default' => 'PENDING'])]
    private MerchantStatusEnum $onboardingStatus = MerchantStatusEnum::PENDING;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: false)]
    private string $apiKey; // Encrypted

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $apiSecret; // Encrypted

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $webhookUrl = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $lastApiAccessAt = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeImmutable $termsAcceptedAt;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $preferences = [];

    #[ORM\OneToMany(targetEntity: StampCard::class, mappedBy: 'merchant', cascade: ['remove'])]
    private Collection $stampCards;

    #[ORM\OneToMany(targetEntity: Reward::class, mappedBy: 'merchant', cascade: ['remove'])]
    private Collection $rewards;

    public function __construct()
    {
        parent::__construct();
        $this->stampCards = new ArrayCollection();
        $this->rewards = new ArrayCollection();
        $this->termsAcceptedAt = new \DateTimeImmutable();
        $this->setRoles(['ROLE_MERCHANT']);
    }

    public function getBusinessName(): string
    {
        return $this->businessName;
    }

    public function setBusinessName(string $businessName): self
    {
        $this->businessName = $businessName;
        return $this;
    }

    public function getCategory(): MerchantCategory
    {
        return $this->category;
    }

    public function setCategory(MerchantCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getStampCards(): Collection
    {
        return $this->stampCards;
    }

    public function getRewards(): Collection
    {
        return $this->rewards;
    }

    public function addReward(Reward $reward): self
    {
        if (!$this->rewards->contains($reward)) {
            $this->rewards->add($reward);
            $reward->setMerchant($this);
        }
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getOnboardingStatus(): MerchantStatusEnum
    {
        return $this->onboardingStatus;
    }

    public function setOnboardingStatus(MerchantStatusEnum $status): self
    {
        $this->onboardingStatus = $status;
        return $this;
    }
}
```

### 2.4 Stamp Card Entity
```php
// src/Entity/StampCard.php
namespace App\Entity;

use App\Repository\StampCardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StampCardRepository::class)]
#[ORM\Table(name: 'stamp_cards', indexes: [
    new ORM\Index(columns: ['customer_id']),
    new ORM\Index(columns: ['merchant_id']),
    new ORM\Index(columns: ['status']),
    new ORM\Index(columns: ['expires_at']),
])]
class StampCard
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Merchant::class, inversedBy: 'stampCards')]
    #[ORM\JoinColumn(nullable: false)]
    private Merchant $merchant;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'stampCards')]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\Column(type: 'string', length: 50, unique: true, nullable: false)]
    private string $cardNumber;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'integer', options: ['default' => 10])]
    private int $totalSlotsRequired = 10;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $currentStampCount = 0;

    #[ORM\Column(type: 'string', enumType: StampCardStatusEnum::class, options: ['default' => 'ACTIVE'])]
    private StampCardStatusEnum $status = StampCardStatusEnum::ACTIVE;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $lastStampAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $bonusStamps = 0;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isDigital = true;

    #[ORM\OneToMany(targetEntity: Stamp::class, mappedBy: 'stampCard', cascade: ['remove'])]
    private Collection $stamps;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = (new \DateTimeImmutable())->modify('+1 year');
        $this->stamps = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMerchant(): Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(Merchant $merchant): self
    {
        $this->merchant = $merchant;
        return $this;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(string $cardNumber): self
    {
        $this->cardNumber = $cardNumber;
        return $this;
    }

    public function getCurrentStampCount(): int
    {
        return $this->currentStampCount;
    }

    public function incrementStampCount(int $count = 1): self
    {
        $this->currentStampCount += $count;
        $this->lastStampAt = new \DateTimeImmutable();

        if ($this->currentStampCount >= $this->totalSlotsRequired) {
            $this->status = StampCardStatusEnum::COMPLETED;
            $this->completedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getStatus(): StampCardStatusEnum
    {
        return $this->status;
    }

    public function setStatus(StampCardStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function getStamps(): Collection
    {
        return $this->stamps;
    }

    public function getProgress(): float
    {
        return ($this->currentStampCount / $this->totalSlotsRequired) * 100;
    }

    public function addBonusStamps(int $count): self
    {
        $this->bonusStamps += $count;
        return $this;
    }
}
```

---

## Part 3: DTOs (Data Transfer Objects)

### 3.1 CreateStampDto
```php
// src/Dto/CreateStampDto.php
namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateStampDto
{
    #[Assert\NotBlank(message: 'Stamp card ID is required')]
    public string $stampCardId;

    #[Assert\NotBlank(message: 'Customer ID is required')]
    public string $customerId;

    #[Assert\Optional]
    #[Assert\Type('array')]
    public ?array $metadata = null;

    #[Assert\Optional]
    public ?string $notes = null;

    #[Assert\NotBlank(message: 'Transaction ID is required')]
    public string $transactionId;
}
```

### 3.2 CreateRewardDto
```php
// src/Dto/CreateRewardDto.php
namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRewardDto
{
    #[Assert\NotBlank(message: 'Reward title is required')]
    #[Assert\Length(min: 5, max: 255)]
    public string $title;

    #[Assert\NotBlank(message: 'Description is required')]
    #[Assert\Length(min: 10, max: 1000)]
    public string $description;

    #[Assert\Choice(choices: ['DISCOUNT', 'FREE_ITEM', 'CASHBACK', 'EXPERIENCE'])]
    public string $rewardType = 'DISCOUNT';

    #[Assert\NotBlank(message: 'Reward value is required')]
    #[Assert\Positive(message: 'Value must be positive')]
    public float $value;

    #[Assert\Positive(message: 'Stamp requirement must be positive')]
    public int $stampRequirement = 10;

    #[Assert\Optional]
    #[Assert\GreaterThan(0)]
    public ?int $maxRedemptions = null;

    #[Assert\Optional]
    public ?string $imageUrl = null;

    #[Assert\Optional]
    public ?string $terms = null;
}
```

### 3.3 RedeemRewardDto
```php
// src/Dto/RedeemRewardDto.php
namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RedeemRewardDto
{
    #[Assert\NotBlank(message: 'Reward ID is required')]
    public string $rewardId;

    #[Assert\Optional]
    public ?string $stampCardId = null;

    #[Assert\Optional]
    public ?string $notes = null;
}
```

---

## Part 4: Services Implementation

### 4.1 StampService
```php
// src/Service/StampService.php
namespace App\Service;

use App\Dto\CreateStampDto;
use App\Entity\Stamp;
use App\Entity\StampCard;
use App\Exception\InvalidStampException;
use App\Repository\StampRepository;
use App\Repository\StampCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Psr\Log\LoggerInterface;

class StampService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StampRepository $stampRepository,
        private StampCardRepository $stampCardRepository,
        private RedisAdapter $cache,
        private LoggerInterface $logger,
    ) {}

    public function issueStamp(CreateStampDto $dto): Stamp
    {
        try {
            $stampCard = $this->stampCardRepository->find($dto->stampCardId);
            if (!$stampCard) {
                throw new InvalidStampException('Stamp card not found');
            }

            // Check if card is already completed
            if ($stampCard->getStatus()->value === 'COMPLETED') {
                throw new InvalidStampException('This stamp card is already completed');
            }

            // Check if card is expired
            if ($stampCard->isExpired()) {
                throw new InvalidStampException('This stamp card has expired');
            }

            // Create stamp entity
            $stamp = new Stamp();
            $stamp->setStampCard($stampCard);
            $stamp->setCustomer($stampCard->getCustomer());
            $stamp->setMerchant($stampCard->getMerchant());
            $stamp->setTransactionId($dto->transactionId);
            $stamp->setMetadata($dto->metadata ?? []);
            $stamp->setNotes($dto->notes);
            $stamp->setCollectedAt(new \DateTimeImmutable());
            $stamp->setExpiresAt((new \DateTimeImmutable())->modify('+1 year'));

            // Increment stamp count on card
            $stampCard->incrementStampCount();

            // Persist entities
            $this->entityManager->persist($stamp);
            $this->entityManager->persist($stampCard);
            $this->entityManager->flush();

            // Invalidate cache
            $this->invalidateStampCardCache($stampCard->getId());

            $this->logger->info('Stamp issued', [
                'stamp_id' => $stamp->getId(),
                'card_id' => $stampCard->getId(),
            ]);

            return $stamp;
        } catch (\Exception $e) {
            $this->logger->error('Failed to issue stamp', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function verifyStamp(string $stampId): bool
    {
        $stamp = $this->stampRepository->find($stampId);

        if (!$stamp) {
            return false;
        }

        if ($stamp->getStatus()->value !== 'ACTIVE') {
            return false;
        }

        if ($stamp->getExpiresAt() < new \DateTimeImmutable()) {
            return false;
        }

        return true;
    }

    public function expireOldStamps(): int
    {
        $now = new \DateTimeImmutable();
        $expiredStamps = $this->stampRepository->findBy([
            'expiresAt' => ['<' => $now],
        ]);

        foreach ($expiredStamps as $stamp) {
            $stamp->setStatus(StampStatusEnum::EXPIRED);
            $this->entityManager->persist($stamp);
        }

        $this->entityManager->flush();

        return count($expiredStamps);
    }

    public function getStampCardStats(StampCard $card): array
    {
        $cacheKey = "stampcard:{$card->getId()}:stats";
        $cached = $this->cache->get($cacheKey, function () use ($card) {
            return [
                'total_stamps' => $card->getCurrentStampCount(),
                'required_stamps' => $card->getTotalSlotsRequired(),
                'progress_percentage' => $card->getProgress(),
                'status' => $card->getStatus()->value,
                'expires_at' => $card->getExpiresAt()->format('Y-m-d H:i:s'),
                'is_completed' => $card->getStatus()->value === 'COMPLETED',
            ];
        }, \DateInterval::createFromDateString('30 minutes'));

        return $cached;
    }

    private function invalidateStampCardCache(string $stampCardId): void
    {
        $this->cache->delete("stampcard:{$stampCardId}:stats");
    }
}
```

### 4.2 RewardService
```php
// src/Service/RewardService.php
namespace App\Service;

use App\Dto\CreateRewardDto;
use App\Dto\RedeemRewardDto;
use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\Reward;
use App\Entity\RewardRedemption;
use App\Entity\StampCard;
use App\Exception\InsufficientStampsException;
use App\Repository\RewardRepository;
use App\Repository\RewardRedemptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RewardService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RewardRepository $rewardRepository,
        private RewardRedemptionRepository $redemptionRepository,
        private NotificationService $notificationService,
        private RedisAdapter $cache,
        private LoggerInterface $logger,
    ) {}

    public function createReward(Merchant $merchant, CreateRewardDto $dto): Reward
    {
        $reward = new Reward();
        $reward->setMerchant($merchant);
        $reward->setTitle($dto->title);
        $reward->setDescription($dto->description);
        $reward->setRewardType(RewardTypeEnum::from($dto->rewardType));
        $reward->setValue((string)$dto->value);
        $reward->setStampRequirement($dto->stampRequirement);
        $reward->setMaxRedemptions($dto->maxRedemptions);
        $reward->setImageUrl($dto->imageUrl);
        $reward->setTerms($dto->terms);
        $reward->setExpiresAt((new \DateTimeImmutable())->modify('+3 months'));
        $reward->setStatus(RewardStatusEnum::ACTIVE);

        $this->entityManager->persist($reward);
        $this->entityManager->flush();

        $this->logger->info('Reward created', ['reward_id' => $reward->getId()]);

        return $reward;
    }

    public function canCustomerRedeem(Customer $customer, Reward $reward): bool
    {
        // Check if reward is not expired
        if ($reward->getExpiresAt() < new \DateTimeImmutable()) {
            return false;
        }

        // Check max redemptions
        if ($reward->getMaxRedemptions() !== null) {
            if ($reward->getCurrentRedemptions() >= $reward->getMaxRedemptions()) {
                return false;
            }
        }

        // Check if customer has enough active stamps
        $totalActiveStamps = 0;
        foreach ($customer->getStampCards() as $card) {
            if ($card->getStatus()->value === 'ACTIVE') {
                $totalActiveStamps += $card->getCurrentStampCount();
            }
        }

        return $totalActiveStamps >= $reward->getStampRequirement();
    }

    public function redeemReward(
        Customer $customer,
        Reward $reward,
        ?StampCard $stampCard = null
    ): RewardRedemption
    {
        if (!$this->canCustomerRedeem($customer, $reward)) {
            throw new InsufficientStampsException('Customer does not have enough stamps or reward unavailable');
        }

        // Create redemption
        $redemption = new RewardRedemption();
        $redemption->setReward($reward);
        $redemption->setCustomer($customer);
        $redemption->setStampCard($stampCard);
        $redemption->setRedeemCode($this->generateRedeemCode());
        $redemption->setStatus(RewardRedemptionStatusEnum::PENDING);
        $redemption->setRedeemedAt(new \DateTimeImmutable());

        // Deduct stamps if applicable
        if ($stampCard) {
            $stampCard->setStatus(StampCardStatusEnum::COMPLETED);
        }

        $this->entityManager->persist($redemption);
        $reward->incrementCurrentRedemptions();
        $this->entityManager->persist($reward);
        $this->entityManager->flush();

        // Send notification
        $this->notificationService->notifyCustomer(
            $customer,
            'Reward Redeemed!',
            "You've successfully redeemed: {$reward->getTitle()}",
            ['PUSH', 'EMAIL']
        );

        $this->logger->info('Reward redeemed', [
            'reward_id' => $reward->getId(),
            'customer_id' => $customer->getId(),
            'redemption_id' => $redemption->getId(),
        ]);

        return $redemption;
    }

    public function getNearbyRewards(
        float $latitude,
        float $longitude,
        float $radiusKm = 5
    ): array
    {
        $cacheKey = "rewards:nearby:{$latitude}:{$longitude}:{$radiusKm}";

        return $this->cache->get($cacheKey, function () use ($latitude, $longitude, $radiusKm) {
            return $this->rewardRepository->findNearby($latitude, $longitude, $radiusKm);
        }, \DateInterval::createFromDateString('30 minutes'));
    }

    public function searchRewards(array $filters): array
    {
        return $this->rewardRepository->search($filters);
    }

    private function generateRedeemCode(): string
    {
        return 'REDEEM-' . strtoupper(bin2hex(random_bytes(8)));
    }
}
```

### 4.3 AuthService
```php
// src/Service/AuthService.php
namespace App\Service;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\User;
use App\Exception\AuthenticationException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    private string $jwtSecret;
    private int $jwtTtl;
    private int $refreshTtl;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private RedisAdapter $cache,
        private LoggerInterface $logger,
        string $jwtSecret,
        int $jwtTtl = 86400,
        int $refreshTtl = 604800,
    ) {
        $this->jwtSecret = $jwtSecret;
        $this->jwtTtl = $jwtTtl;
        $this->refreshTtl = $refreshTtl;
    }

    public function registerCustomer(array $data): Customer
    {
        // Check if user exists
        $existing = $this->userRepository->findOneBy(['email' => strtolower($data['email'])]);
        if ($existing) {
            throw new AuthenticationException('Email already registered');
        }

        $customer = new Customer();
        $customer->setEmail(strtolower($data['email']));
        $customer->setPhone($data['phone']);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, $data['password']));
        $customer->setFirstName($data['first_name']);
        $customer->setLastName($data['last_name']);
        $customer->setReferralCode($this->generateReferralCode());

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $this->logger->info('Customer registered', ['email' => $customer->getEmail()]);

        return $customer;
    }

    public function registerMerchant(array $data): Merchant
    {
        $existing = $this->userRepository->findOneBy(['email' => strtolower($data['email'])]);
        if ($existing) {
            throw new AuthenticationException('Email already registered');
        }

        $merchant = new Merchant();
        $merchant->setEmail(strtolower($data['email']));
        $merchant->setPhone($data['phone']);
        $merchant->setPassword($this->passwordHasher->hashPassword($merchant, $data['password']));
        $merchant->setFirstName($data['first_name']);
        $merchant->setLastName($data['last_name']);
        $merchant->setBusinessName($data['business_name']);
        $merchant->setAddress($data['address']);
        $merchant->setCity($data['city']);
        $merchant->setState($data['state']);
        $merchant->setTaxId($data['tax_id']);
        $merchant->setBankAccountNumber($data['bank_account']);
        $merchant->setBankIfscCode($data['bank_ifsc']);
        $merchant->setBankAccountHolderName($data['bank_holder_name']);
        $merchant->setApiKey($this->generateApiKey());
        $merchant->setApiSecret($this->generateApiSecret());

        $this->entityManager->persist($merchant);
        $this->entityManager->flush();

        $this->logger->info('Merchant registered', ['email' => $merchant->getEmail()]);

        return $merchant;
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findOneBy(['email' => strtolower($email)]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new AuthenticationException('Invalid email or password');
        }

        if ($user->getStatus()->value === 'SUSPENDED') {
            throw new AuthenticationException('Your account has been suspended');
        }

        $token = $this->generateToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        return [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtTtl,
        ];
    }

    public function refreshToken(string $token): string
    {
        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->jwtSecret, 'HS256')
            );

            $user = $this->userRepository->find($decoded->sub);
            if (!$user) {
                throw new AuthenticationException('User not found');
            }

            return $this->generateToken($user);
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid refresh token');
        }
    }

    public function logout(string $token): void
    {
        // Blacklist token in Redis
        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->jwtSecret, 'HS256')
            );

            $ttl = $decoded->exp - time();
            if ($ttl > 0) {
                $this->cache->set("blacklist:{$decoded->jti}", true, $ttl);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to blacklist token', ['error' => $e->getMessage()]);
        }
    }

    private function generateToken(User $user): string
    {
        $now = time();
        $payload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRoles()[0] ?? 'ROLE_USER',
            'iat' => $now,
            'exp' => $now + $this->jwtTtl,
            'jti' => uniqid('jti_', true),
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function generateRefreshToken(User $user): string
    {
        $now = time();
        $payload = [
            'sub' => $user->getId(),
            'type' => 'refresh',
            'iat' => $now,
            'exp' => $now + $this->refreshTtl,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function generateReferralCode(): string
    {
        return strtoupper(substr(md5(uniqid()), 0, 8));
    }

    private function generateApiKey(): string
    {
        return 'pk_' . bin2hex(random_bytes(16));
    }

    private function generateApiSecret(): string
    {
        return 'sk_' . bin2hex(random_bytes(24));
    }
}
```

---

## Part 5: Controllers

### 5.1 Customer Authentication Controller
```php
// src/Controller/Api/AuthController.php
namespace App\Controller\Api;

use App\Dto\LoginRequest;
use App\Dto\RegisterRequest;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private ValidatorInterface $validator,
    ) {}

    #[Route('/register/customer', name: 'register_customer', methods: ['POST'])]
    public function registerCustomer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $customer = $this->authService->registerCustomer($data);

            return $this->json([
                'success' => true,
                'message' => 'Customer registered successfully',
                'data' => [
                    'id' => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'name' => $customer->getFullName(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $tokens = $this->authService->login($data['email'], $data['password']);

            return $this->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $tokens,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    #[Route('/refresh-token', name: 'refresh_token', methods: ['POST'])]
    public function refreshToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $newToken = $this->authService->refreshToken($data['refresh_token']);

            return $this->json([
                'success' => true,
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(Request $request): JsonResponse
    {
        $token = str_replace('Bearer ', '', $request->headers->get('Authorization', ''));
        $this->authService->logout($token);

        return $this->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
```

### 5.2 Stamp Controller
```php
// src/Controller/Api/StampController.php
namespace App\Controller\Api;

use App\Dto\CreateStampDto;
use App\Service\StampService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/stamps', name: 'api_stamps_')]
#[IsGranted('ROLE_USER')]
class StampController extends AbstractController
{
    public function __construct(
        private StampService $stampService,
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function createStamp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new CreateStampDto();
        $dto->stampCardId = $data['stamp_card_id'];
        $dto->customerId = $data['customer_id'];
        $dto->transactionId = $data['transaction_id'];
        $dto->metadata = $data['metadata'] ?? null;
        $dto->notes = $data['notes'] ?? null;

        try {
            $stamp = $this->stampService->issueStamp($dto);

            return $this->json([
                'success' => true,
                'message' => 'Stamp issued successfully',
                'data' => [
                    'id' => $stamp->getId(),
                    'stamp_card_id' => $stamp->getStampCard()->getId(),
                    'collected_at' => $stamp->getCollectedAt()->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/{id}/verify', name: 'verify', methods: ['GET'])]
    public function verifyStamp(string $id): JsonResponse
    {
        try {
            $isValid = $this->stampService->verifyStamp($id);

            return $this->json([
                'success' => true,
                'data' => [
                    'stamp_id' => $id,
                    'is_valid' => $isValid,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

---

## Part 6: Enums

### 6.1 User Status Enum
```php
// src/Enum/UserStatusEnum.php
namespace App\Enum;

enum UserStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case DELETED = 'DELETED';
}
```

### 6.2 Stamp Card Status Enum
```php
// src/Enum/StampCardStatusEnum.php
namespace App\Enum;

enum StampCardStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
}
```

### 6.3 Other Enums
```php
// src/Enum/GenderEnum.php
enum GenderEnum: string
{
    case MALE = 'MALE';
    case FEMALE = 'FEMALE';
    case OTHER = 'OTHER';
}

// src/Enum/RewardTypeEnum.php
enum RewardTypeEnum: string
{
    case DISCOUNT = 'DISCOUNT';
    case FREE_ITEM = 'FREE_ITEM';
    case CASHBACK = 'CASHBACK';
    case EXPERIENCE = 'EXPERIENCE';
}

// src/Enum/MerchantStatusEnum.php
enum MerchantStatusEnum: string
{
    case PENDING = 'PENDING';
    case VERIFIED = 'VERIFIED';
    case REJECTED = 'REJECTED';
    case ACTIVE = 'ACTIVE';
}
```

---

## Part 7: Database Migration Example

```php
// migrations/Version2024050701000000.php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version2024050701000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and stamp_cards tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (
            id UUID NOT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            user_type VARCHAR(255) NOT NULL,
            status VARCHAR(255) NOT NULL DEFAULT \'ACTIVE\',
            is_email_verified BOOLEAN NOT NULL DEFAULT false,
            is_phone_verified BOOLEAN NOT NULL DEFAULT false,
            roles JSON NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64939B0BF2C ON users (phone)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
```

---

## Part 8: Unit Test Example

```php
// tests/Unit/Service/StampServiceTest.php
namespace App\Tests\Unit\Service;

use App\Dto\CreateStampDto;
use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\StampCard;
use App\Enum\StampCardStatusEnum;
use App\Exception\InvalidStampException;
use App\Repository\StampCardRepository;
use App\Repository\StampRepository;
use App\Service\StampService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class StampServiceTest extends TestCase
{
    private StampService $stampService;
    private EntityManagerInterface $entityManager;
    private StampRepository $stampRepository;
    private StampCardRepository $stampCardRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->stampRepository = $this->createMock(StampRepository::class);
        $this->stampCardRepository = $this->createMock(StampCardRepository::class);
        $cache = $this->createMock(RedisAdapter::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->stampService = new StampService(
            $this->entityManager,
            $this->stampRepository,
            $this->stampCardRepository,
            $cache,
            $logger
        );
    }

    public function testIssueStampSuccessfully(): void
    {
        $merchant = new Merchant();
        $merchant->setId('merchant-123');

        $customer = new Customer();
        $customer->setId('customer-456');

        $stampCard = new StampCard();
        $stampCard->setId('card-789');
        $stampCard->setMerchant($merchant);
        $stampCard->setCustomer($customer);
        $stampCard->setStatus(StampCardStatusEnum::ACTIVE);
        $stampCard->setExpiresAt((new \DateTimeImmutable())->modify('+1 month'));
        $stampCard->setTotalSlotsRequired(10);
        $stampCard->setCurrentStampCount(0);

        $this->stampCardRepository
            ->expects($this->once())
            ->method('find')
            ->with('card-789')
            ->willReturn($stampCard);

        $dto = new CreateStampDto();
        $dto->stampCardId = 'card-789';
        $dto->customerId = 'customer-456';
        $dto->transactionId = 'txn-123';

        $stamp = $this->stampService->issueStamp($dto);

        $this->assertNotNull($stamp->getId());
        $this->assertEquals('card-789', $stamp->getStampCard()->getId());
        $this->assertEquals('customer-456', $stamp->getCustomer()->getId());
    }

    public function testCannotIssueStampToExpiredCard(): void
    {
        $merchant = new Merchant();
        $customer = new Customer();

        $stampCard = new StampCard();
        $stampCard->setId('card-789');
        $stampCard->setMerchant($merchant);
        $stampCard->setCustomer($customer);
        $stampCard->setStatus(StampCardStatusEnum::ACTIVE);
        $stampCard->setExpiresAt((new \DateTimeImmutable())->modify('-1 day'));

        $this->stampCardRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($stampCard);

        $dto = new CreateStampDto();
        $dto->stampCardId = 'card-789';
        $dto->customerId = 'customer-456';
        $dto->transactionId = 'txn-123';

        $this->expectException(InvalidStampException::class);
        $this->stampService->issueStamp($dto);
    }
}
```

---

## Quick Start Commands

```bash
# Install dependencies
composer install

# Generate JWT keys
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem

# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Start development server
symfony serve

# Run tests
php bin/phpunit

# Run specific test
php bin/phpunit tests/Unit/Service/StampServiceTest.php

# Clear cache
php bin/console cache:clear
```

---

**This guide provides the foundation to build a production-ready snapstamp loyalty system with Symfony. Extend and customize based on your specific requirements.**

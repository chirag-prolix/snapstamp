<?php

namespace App\Entity;

use App\Enum\MerchantStatusEnum;
use App\Repository\MerchantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MerchantRepository::class)]
class Merchant extends User
{
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Business name is required')]
    private string $businessName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $businessDescription = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $businessLogoUrl = null;

    #[ORM\ManyToOne(targetEntity: MerchantCategory::class, inversedBy: 'merchants')]
    #[ORM\JoinColumn(nullable: true)]
    private ?MerchantCategory $category = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $subcategory = null;

    #[ORM\Column(type: 'float')]
    private float $latitude = 0.0;

    #[ORM\Column(type: 'float')]
    private float $longitude = 0.0;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private string $city;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private string $state;

    #[ORM\Column(type: 'string', length: 100, options: ['default' => 'IN'])]
    private string $country = 'IN';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $address;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    private string $phoneForBusiness;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: 'string', length: 30, unique: true)]
    #[Assert\NotBlank]
    private string $taxId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $bankAccountNumber;

    #[ORM\Column(type: 'string', length: 20)]
    private string $bankIfscCode;

    #[ORM\Column(type: 'string', length: 255)]
    private string $bankAccountHolderName;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 4, options: ['default' => '0.0500'])]
    private string $commissionRate = '0.0500';

    #[ORM\Column(type: 'decimal', precision: 3, scale: 1, nullable: true)]
    private ?string $averageRating = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalCustomers = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalStampsIssued = 0;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $totalRewardsGiven = '0.00';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $verificationDocuments = null;

    #[ORM\Column(type: 'string', length: 20, enumType: MerchantStatusEnum::class, options: ['default' => 'PENDING'])]
    private MerchantStatusEnum $onboardingStatus = MerchantStatusEnum::PENDING;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $apiKey;

    #[ORM\Column(type: 'string', length: 255)]
    private string $apiSecret;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $webhookUrl = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastApiAccessAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $termsAcceptedAt;

    #[ORM\Column(type: 'json')]
    private array $preferences = [];

    #[ORM\OneToMany(targetEntity: StampCard::class, mappedBy: 'merchant', cascade: ['remove'])]
    private Collection $stampCards;

    #[ORM\OneToMany(targetEntity: Reward::class, mappedBy: 'merchant', cascade: ['remove'])]
    private Collection $rewards;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'merchant')]
    private Collection $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->stampCards = new ArrayCollection();
        $this->rewards = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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

    public function getBusinessDescription(): ?string
    {
        return $this->businessDescription;
    }

    public function setBusinessDescription(?string $businessDescription): self
    {
        $this->businessDescription = $businessDescription;
        return $this;
    }

    public function getBusinessLogoUrl(): ?string
    {
        return $this->businessLogoUrl;
    }

    public function setBusinessLogoUrl(?string $businessLogoUrl): self
    {
        $this->businessLogoUrl = $businessLogoUrl;
        return $this;
    }

    public function getCategory(): ?MerchantCategory
    {
        return $this->category;
    }

    public function setCategory(?MerchantCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getSubcategory(): ?string
    {
        return $this->subcategory;
    }

    public function setSubcategory(?string $subcategory): self
    {
        $this->subcategory = $subcategory;
        return $this;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getPhoneForBusiness(): string
    {
        return $this->phoneForBusiness;
    }

    public function setPhoneForBusiness(string $phoneForBusiness): self
    {
        $this->phoneForBusiness = $phoneForBusiness;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function getTaxId(): string
    {
        return $this->taxId;
    }

    public function setTaxId(string $taxId): self
    {
        $this->taxId = $taxId;
        return $this;
    }

    public function getBankAccountNumber(): string
    {
        return $this->bankAccountNumber;
    }

    public function setBankAccountNumber(string $bankAccountNumber): self
    {
        $this->bankAccountNumber = $bankAccountNumber;
        return $this;
    }

    public function getBankIfscCode(): string
    {
        return $this->bankIfscCode;
    }

    public function setBankIfscCode(string $bankIfscCode): self
    {
        $this->bankIfscCode = $bankIfscCode;
        return $this;
    }

    public function getBankAccountHolderName(): string
    {
        return $this->bankAccountHolderName;
    }

    public function setBankAccountHolderName(string $bankAccountHolderName): self
    {
        $this->bankAccountHolderName = $bankAccountHolderName;
        return $this;
    }

    public function getCommissionRate(): string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(string $commissionRate): self
    {
        $this->commissionRate = $commissionRate;
        return $this;
    }

    public function getAverageRating(): ?string
    {
        return $this->averageRating;
    }

    public function setAverageRating(?string $averageRating): self
    {
        $this->averageRating = $averageRating;
        return $this;
    }

    public function getTotalCustomers(): int
    {
        return $this->totalCustomers;
    }

    public function incrementTotalCustomers(): self
    {
        $this->totalCustomers++;
        return $this;
    }

    public function getTotalStampsIssued(): int
    {
        return $this->totalStampsIssued;
    }

    public function incrementTotalStampsIssued(): self
    {
        $this->totalStampsIssued++;
        return $this;
    }

    public function getTotalRewardsGiven(): string
    {
        return $this->totalRewardsGiven;
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

    public function getVerificationDocuments(): ?array
    {
        return $this->verificationDocuments;
    }

    public function setVerificationDocuments(?array $verificationDocuments): self
    {
        $this->verificationDocuments = $verificationDocuments;
        return $this;
    }

    public function getOnboardingStatus(): MerchantStatusEnum
    {
        return $this->onboardingStatus;
    }

    public function setOnboardingStatus(MerchantStatusEnum $onboardingStatus): self
    {
        $this->onboardingStatus = $onboardingStatus;
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

    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    public function setApiSecret(string $apiSecret): self
    {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(?string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;
        return $this;
    }

    public function getLastApiAccessAt(): ?\DateTimeImmutable
    {
        return $this->lastApiAccessAt;
    }

    public function touchApiAccess(): self
    {
        $this->lastApiAccessAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTermsAcceptedAt(): \DateTimeImmutable
    {
        return $this->termsAcceptedAt;
    }

    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function setPreferences(array $preferences): self
    {
        $this->preferences = $preferences;
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

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }
}

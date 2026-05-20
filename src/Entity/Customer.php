<?php

namespace App\Entity;

use App\Enum\GenderEnum;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer extends User
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePictureUrl = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateOfBirth = null;

    #[ORM\Column(type: 'string', length: 10, enumType: GenderEnum::class, nullable: true)]
    private ?GenderEnum $gender = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true, options: ['default' => 'IN'])]
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

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $totalSpent = '0.00';

    #[ORM\Column(type: 'string', length: 20, unique: true, nullable: true)]
    private ?string $referralCode = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $referralCount = 0;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Customer $referredBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastActiveAt = null;

    #[ORM\Column(type: 'json')]
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

    public function getDisplayName(): string
    {
        return $this->displayName ?? $this->getFullName();
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getProfilePictureUrl(): ?string
    {
        return $this->profilePictureUrl;
    }

    public function setProfilePictureUrl(?string $profilePictureUrl): self
    {
        $this->profilePictureUrl = $profilePictureUrl;
        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeImmutable $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    public function getGender(): ?GenderEnum
    {
        return $this->gender;
    }

    public function setGender(?GenderEnum $gender): self
    {
        $this->gender = $gender;
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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
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

    public function getPreferredLanguage(): string
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(string $preferredLanguage): self
    {
        $this->preferredLanguage = $preferredLanguage;
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

    public function getTotalRewardsRedeemed(): int
    {
        return $this->totalRewardsRedeemed;
    }

    public function incrementTotalRewardsRedeemed(): self
    {
        $this->totalRewardsRedeemed++;
        return $this;
    }

    public function getTotalSpent(): string
    {
        return $this->totalSpent;
    }

    public function addToTotalSpent(string $amount): self
    {
        $this->totalSpent = (string) (bcadd($this->totalSpent, $amount, 2));
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

    public function getReferralCount(): int
    {
        return $this->referralCount;
    }

    public function incrementReferralCount(): self
    {
        $this->referralCount++;
        return $this;
    }

    public function getReferredBy(): ?Customer
    {
        return $this->referredBy;
    }

    public function setReferredBy(?Customer $referredBy): self
    {
        $this->referredBy = $referredBy;
        return $this;
    }

    public function getLastActiveAt(): ?\DateTimeImmutable
    {
        return $this->lastActiveAt;
    }

    public function touchLastActive(): self
    {
        $this->lastActiveAt = new \DateTimeImmutable();
        return $this;
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

    public function getRedemptions(): Collection
    {
        return $this->redemptions;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function getTier(): string
    {
        $spent = (float) $this->totalSpent;
        return match(true) {
            $spent >= 50000 => 'PLATINUM',
            $spent >= 20000 => 'GOLD',
            $spent >= 5000  => 'SILVER',
            default         => 'BRONZE',
        };
    }
}

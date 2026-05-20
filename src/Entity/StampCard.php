<?php

namespace App\Entity;

use App\Enum\StampCardStatusEnum;
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
    new ORM\Index(columns: ['created_at']),
])]
class StampCard
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Merchant::class, inversedBy: 'stampCards')]
    #[ORM\JoinColumn(nullable: false)]
    private Merchant $merchant;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'stampCards')]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $cardNumber;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'integer', options: ['default' => 10])]
    private int $totalSlotsRequired = 10;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $currentStampCount = 0;

    #[ORM\Column(type: 'string', length: 20, enumType: StampCardStatusEnum::class, options: ['default' => 'ACTIVE'])]
    private StampCardStatusEnum $status = StampCardStatusEnum::ACTIVE;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastStampAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $bonusStamps = 0;

    #[ORM\Column(type: 'json')]
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

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getTotalSlotsRequired(): int
    {
        return $this->totalSlotsRequired;
    }

    public function setTotalSlotsRequired(int $totalSlotsRequired): self
    {
        $this->totalSlotsRequired = $totalSlotsRequired;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastStampAt(): ?\DateTimeImmutable
    {
        return $this->lastStampAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function getBonusStamps(): int
    {
        return $this->bonusStamps;
    }

    public function addBonusStamps(int $count): self
    {
        $this->bonusStamps += $count;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function isDigital(): bool
    {
        return $this->isDigital;
    }

    public function setIsDigital(bool $isDigital): self
    {
        $this->isDigital = $isDigital;
        return $this;
    }

    public function getStamps(): Collection
    {
        return $this->stamps;
    }

    public function getProgress(): float
    {
        if ($this->totalSlotsRequired === 0) {
            return 0.0;
        }
        return min(($this->currentStampCount / $this->totalSlotsRequired) * 100, 100.0);
    }
}

<?php

namespace App\Entity;

use App\Enum\StampStatusEnum;
use App\Repository\StampRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StampRepository::class)]
#[ORM\Table(name: 'stamps', indexes: [
    new ORM\Index(columns: ['stamp_card_id']),
    new ORM\Index(columns: ['customer_id']),
    new ORM\Index(columns: ['merchant_id']),
    new ORM\Index(columns: ['collected_at']),
    new ORM\Index(columns: ['expires_at']),
    new ORM\Index(columns: ['status']),
])]
class Stamp
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: StampCard::class, inversedBy: 'stamps')]
    #[ORM\JoinColumn(nullable: false)]
    private StampCard $stampCard;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Merchant $merchant;

    #[ORM\Column(type: 'integer')]
    private int $stampSequence;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isBonus = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $collectedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'string', length: 10, enumType: StampStatusEnum::class, options: ['default' => 'ACTIVE'])]
    private StampStatusEnum $status = StampStatusEnum::ACTIVE;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $transactionId = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->collectedAt = new \DateTimeImmutable();
        $this->expiresAt = (new \DateTimeImmutable())->modify('+1 year');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStampCard(): StampCard
    {
        return $this->stampCard;
    }

    public function setStampCard(StampCard $stampCard): self
    {
        $this->stampCard = $stampCard;
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

    public function getMerchant(): Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(Merchant $merchant): self
    {
        $this->merchant = $merchant;
        return $this;
    }

    public function getStampSequence(): int
    {
        return $this->stampSequence;
    }

    public function setStampSequence(int $stampSequence): self
    {
        $this->stampSequence = $stampSequence;
        return $this;
    }

    public function isBonus(): bool
    {
        return $this->isBonus;
    }

    public function setIsBonus(bool $isBonus): self
    {
        $this->isBonus = $isBonus;
        return $this;
    }

    public function getCollectedAt(): \DateTimeImmutable
    {
        return $this->collectedAt;
    }

    public function setCollectedAt(\DateTimeImmutable $collectedAt): self
    {
        $this->collectedAt = $collectedAt;
        return $this;
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

    public function getStatus(): StampStatusEnum
    {
        return $this->status;
    }

    public function setStatus(StampStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }
}

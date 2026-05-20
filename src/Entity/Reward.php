<?php

namespace App\Entity;

use App\Enum\RewardStatusEnum;
use App\Enum\RewardTypeEnum;
use App\Repository\RewardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RewardRepository::class)]
#[ORM\Table(name: 'rewards', indexes: [
    new ORM\Index(columns: ['merchant_id']),
    new ORM\Index(columns: ['status']),
    new ORM\Index(columns: ['expires_at']),
])]
class Reward
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Merchant::class, inversedBy: 'rewards')]
    #[ORM\JoinColumn(nullable: false)]
    private Merchant $merchant;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(type: 'string', length: 20, enumType: RewardTypeEnum::class)]
    private RewardTypeEnum $rewardType = RewardTypeEnum::DISCOUNT;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive]
    private string $value;

    #[ORM\Column(type: 'string', length: 3, options: ['default' => 'INR'])]
    private string $currency = 'INR';

    #[ORM\Column(type: 'integer', options: ['default' => 10])]
    #[Assert\Positive]
    private int $stampRequirement = 10;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxRedemptions = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $currentRedemptions = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $terms = null;

    #[ORM\Column(type: 'string', length: 20, enumType: RewardStatusEnum::class, options: ['default' => 'DRAFT'])]
    private RewardStatusEnum $status = RewardStatusEnum::DRAFT;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\OneToMany(targetEntity: RewardRedemption::class, mappedBy: 'reward')]
    private Collection $redemptions;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->expiresAt = (new \DateTimeImmutable())->modify('+3 months');
        $this->redemptions = new ArrayCollection();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRewardType(): RewardTypeEnum
    {
        return $this->rewardType;
    }

    public function setRewardType(RewardTypeEnum $rewardType): self
    {
        $this->rewardType = $rewardType;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStampRequirement(): int
    {
        return $this->stampRequirement;
    }

    public function setStampRequirement(int $stampRequirement): self
    {
        $this->stampRequirement = $stampRequirement;
        return $this;
    }

    public function getMaxRedemptions(): ?int
    {
        return $this->maxRedemptions;
    }

    public function setMaxRedemptions(?int $maxRedemptions): self
    {
        $this->maxRedemptions = $maxRedemptions;
        return $this;
    }

    public function getCurrentRedemptions(): int
    {
        return $this->currentRedemptions;
    }

    public function incrementCurrentRedemptions(): self
    {
        $this->currentRedemptions++;
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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): self
    {
        $this->terms = $terms;
        return $this;
    }

    public function getStatus(): RewardStatusEnum
    {
        return $this->status;
    }

    public function setStatus(RewardStatusEnum $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getRedemptions(): Collection
    {
        return $this->redemptions;
    }
}

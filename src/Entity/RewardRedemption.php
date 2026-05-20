<?php

namespace App\Entity;

use App\Enum\RewardRedemptionStatusEnum;
use App\Repository\RewardRedemptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RewardRedemptionRepository::class)]
#[ORM\Table(name: 'reward_redemptions', indexes: [
    new ORM\Index(columns: ['reward_id']),
    new ORM\Index(columns: ['customer_id']),
    new ORM\Index(columns: ['stamp_card_id']),
    new ORM\Index(columns: ['redeemed_at']),
    new ORM\Index(columns: ['status']),
])]
class RewardRedemption
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Reward::class, inversedBy: 'redemptions')]
    #[ORM\JoinColumn(nullable: false)]
    private Reward $reward;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'redemptions')]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\ManyToOne(targetEntity: StampCard::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?StampCard $stampCard = null;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Merchant $approvedByMerchant = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $redeemedAt;

    #[ORM\Column(type: 'string', length: 20, enumType: RewardRedemptionStatusEnum::class, options: ['default' => 'PENDING'])]
    private RewardRedemptionStatusEnum $status = RewardRedemptionStatusEnum::PENDING;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $redeemCode;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $voucherUrl = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $merchantApprovedAt = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->redeemedAt = new \DateTimeImmutable();
        $this->redeemCode = 'REDEEM-' . strtoupper(bin2hex(random_bytes(8)));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getReward(): Reward
    {
        return $this->reward;
    }

    public function setReward(Reward $reward): self
    {
        $this->reward = $reward;
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

    public function getStampCard(): ?StampCard
    {
        return $this->stampCard;
    }

    public function setStampCard(?StampCard $stampCard): self
    {
        $this->stampCard = $stampCard;
        return $this;
    }

    public function getApprovedByMerchant(): ?Merchant
    {
        return $this->approvedByMerchant;
    }

    public function approve(Merchant $merchant): self
    {
        $this->approvedByMerchant = $merchant;
        $this->merchantApprovedAt = new \DateTimeImmutable();
        $this->status = RewardRedemptionStatusEnum::COMPLETED;
        return $this;
    }

    public function getRedeemedAt(): \DateTimeImmutable
    {
        return $this->redeemedAt;
    }

    public function getStatus(): RewardRedemptionStatusEnum
    {
        return $this->status;
    }

    public function setStatus(RewardRedemptionStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getRedeemCode(): string
    {
        return $this->redeemCode;
    }

    public function getVoucherUrl(): ?string
    {
        return $this->voucherUrl;
    }

    public function setVoucherUrl(?string $voucherUrl): self
    {
        $this->voucherUrl = $voucherUrl;
        return $this;
    }

    public function getMerchantApprovedAt(): ?\DateTimeImmutable
    {
        return $this->merchantApprovedAt;
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

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
}

<?php

namespace App\Entity;

use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions', indexes: [
    new ORM\Index(columns: ['customer_id']),
    new ORM\Index(columns: ['merchant_id']),
    new ORM\Index(columns: ['created_at']),
    new ORM\Index(columns: ['transaction_type']),
])]
class Transaction
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 30, enumType: TransactionTypeEnum::class)]
    private TransactionTypeEnum $transactionType;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $stamps = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $referenceId = null;

    #[ORM\Column(type: 'string', length: 20, enumType: TransactionStatusEnum::class, options: ['default' => 'COMPLETED'])]
    private TransactionStatusEnum $status = TransactionStatusEnum::COMPLETED;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 500)]
    private string $description;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTransactionType(): TransactionTypeEnum
    {
        return $this->transactionType;
    }

    public function setTransactionType(TransactionTypeEnum $transactionType): self
    {
        $this->transactionType = $transactionType;
        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): self
    {
        $this->merchant = $merchant;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getStamps(): ?int
    {
        return $this->stamps;
    }

    public function setStamps(?int $stamps): self
    {
        $this->stamps = $stamps;
        return $this;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function setReferenceId(?string $referenceId): self
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getStatus(): TransactionStatusEnum
    {
        return $this->status;
    }

    public function setStatus(TransactionStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
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

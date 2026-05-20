<?php

namespace App\Entity;

use App\Enum\PaymentGatewayEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\PaymentTypeEnum;
use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments', indexes: [
    new ORM\Index(columns: ['customer_id']),
    new ORM\Index(columns: ['merchant_id']),
    new ORM\Index(columns: ['status']),
    new ORM\Index(columns: ['created_at']),
])]
class Payment
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $transactionId;

    #[ORM\Column(type: 'string', length: 20, enumType: PaymentGatewayEnum::class)]
    private PaymentGatewayEnum $paymentGateway;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $paymentGatewayId;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'string', length: 3, options: ['default' => 'INR'])]
    private string $currency = 'INR';

    #[ORM\Column(type: 'string', length: 30, enumType: PaymentTypeEnum::class)]
    private PaymentTypeEnum $paymentType;

    #[ORM\Column(type: 'string', length: 20, enumType: PaymentStatusEnum::class, options: ['default' => 'INITIATED'])]
    private PaymentStatusEnum $status = PaymentStatusEnum::INITIATED;

    #[ORM\Column(type: 'string', length: 20, enumType: PaymentMethodEnum::class, nullable: true)]
    private ?PaymentMethodEnum $paymentMethod = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $retryCount = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $webhookReceivedAt = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $receiptUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getPaymentGateway(): PaymentGatewayEnum
    {
        return $this->paymentGateway;
    }

    public function setPaymentGateway(PaymentGatewayEnum $paymentGateway): self
    {
        $this->paymentGateway = $paymentGateway;
        return $this;
    }

    public function getPaymentGatewayId(): string
    {
        return $this->paymentGatewayId;
    }

    public function setPaymentGatewayId(string $paymentGatewayId): self
    {
        $this->paymentGatewayId = $paymentGatewayId;
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

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPaymentType(): PaymentTypeEnum
    {
        return $this->paymentType;
    }

    public function setPaymentType(PaymentTypeEnum $paymentType): self
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    public function getStatus(): PaymentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PaymentStatusEnum $status): self
    {
        $this->status = $status;
        if ($status === PaymentStatusEnum::COMPLETED) {
            $this->processedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethodEnum
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethodEnum $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): self
    {
        $this->failureReason = $failureReason;
        return $this;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function incrementRetryCount(): self
    {
        $this->retryCount++;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function markWebhookReceived(): self
    {
        $this->webhookReceivedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getWebhookReceivedAt(): ?\DateTimeImmutable
    {
        return $this->webhookReceivedAt;
    }

    public function getReceiptUrl(): ?string
    {
        return $this->receiptUrl;
    }

    public function setReceiptUrl(?string $receiptUrl): self
    {
        $this->receiptUrl = $receiptUrl;
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

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }
}

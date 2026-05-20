<?php

namespace App\Entity;

use App\Repository\OtpTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: OtpTokenRepository::class)]
#[ORM\Table(name: 'otp_tokens', indexes: [
    new ORM\Index(columns: ['user_id']),
    new ORM\Index(columns: ['type']),
    new ORM\Index(columns: ['expires_at']),
    new ORM\Index(columns: ['created_at']),
])]
class OtpToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 10)]
    private string $type;

    #[ORM\Column(type: 'string', length: 64)]
    private string $codeHash;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, string $type, string $codeHash, \DateTimeImmutable $expiresAt)
    {
        $this->id        = Uuid::uuid4()->toString();
        $this->user      = $user;
        $this->type      = $type;
        $this->codeHash  = $codeHash;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getType(): string { return $this->type; }
    public function getCodeHash(): string { return $this->codeHash; }
    public function getAttempts(): int { return $this->attempts; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function getUsedAt(): ?\DateTimeImmutable { return $this->usedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }

    public function markUsed(): void
    {
        $this->usedAt = new \DateTimeImmutable();
    }

    public function incrementAttempts(): int
    {
        return ++$this->attempts;
    }
}

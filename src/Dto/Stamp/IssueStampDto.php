<?php

namespace App\Dto\Stamp;

use Symfony\Component\Validator\Constraints as Assert;

class IssueStampDto
{
    #[Assert\Uuid(message: 'customerId must be a valid UUID')]
    public ?string $customerId = null;

    #[Assert\Regex(pattern: '/^\+?[1-9]\d{1,14}$/', message: 'Invalid phone number format')]
    public ?string $customerPhone = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'count must be between 1 and 10')]
    public int $count = 1;

    #[Assert\Length(max: 100)]
    public ?string $transactionId = null;

    #[Assert\Length(max: 500)]
    public ?string $notes = null;

    public bool $isBonus = false;

    #[Assert\Date(message: 'cardExpiresAt must be a valid date (YYYY-MM-DD)')]
    public ?string $cardExpiresAt = null;
}

<?php

namespace App\Dto\Reward;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRewardDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    public string $title = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 2000)]
    public string $description = '';

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['DISCOUNT', 'FREE_ITEM', 'CASHBACK', 'EXPERIENCE'], message: 'rewardType must be one of: DISCOUNT, FREE_ITEM, CASHBACK, EXPERIENCE')]
    public string $rewardType = 'DISCOUNT';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'value must be a positive decimal number')]
    public string $value = '';

    #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'stampRequirement must be between 1 and 100')]
    public int $stampRequirement = 10;

    #[Assert\Positive]
    public ?int $maxRedemptions = null;

    #[Assert\NotBlank]
    public string $expiresAt = '';

    #[Assert\Length(max: 1000)]
    public ?string $terms = null;
}

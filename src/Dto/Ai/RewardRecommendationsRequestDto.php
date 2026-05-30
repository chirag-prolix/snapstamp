<?php

namespace App\Dto\Ai;

use Symfony\Component\Validator\Constraints as Assert;

class RewardRecommendationsRequestDto
{
    #[Assert\NotNull(message: 'Stamp cards data is required.')]
    #[Assert\Type('array')]
    public array $stampCards = [];

    #[Assert\NotNull(message: 'Available rewards data is required.')]
    #[Assert\Type('array')]
    public array $availableRewards = [];

    public string $tier = 'BRONZE';
}

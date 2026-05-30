<?php

namespace App\Dto\Ai;

use Symfony\Component\Validator\Constraints as Assert;

class RewardSuggestionRequestDto
{
    #[Assert\NotBlank(message: 'Business name is required.')]
    #[Assert\Length(max: 100)]
    public string $businessName = '';

    #[Assert\NotBlank(message: 'Business type is required.')]
    #[Assert\Length(max: 100)]
    public string $businessType = '';

    public string $businessDescription = '';
}

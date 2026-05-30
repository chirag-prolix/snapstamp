<?php

namespace App\Dto\Ai;

use Symfony\Component\Validator\Constraints as Assert;

class AnalyticsInsightsRequestDto
{
    #[Assert\NotNull(message: 'Stats data is required.')]
    #[Assert\Type('array')]
    public array $stats = [];
}

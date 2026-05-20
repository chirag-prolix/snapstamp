<?php

namespace App\Dto\Payment;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderDto
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['monthly', 'annual'], message: 'plan must be "monthly" or "annual"')]
    public string $plan = '';
}

<?php

namespace App\Dto\Verification;

use Symfony\Component\Validator\Constraints as Assert;

class RequestOtpDto
{
    #[Assert\NotBlank(message: 'Type is required')]
    #[Assert\Choice(choices: ['email', 'phone'], message: 'Type must be "email" or "phone"')]
    public string $type = '';
}

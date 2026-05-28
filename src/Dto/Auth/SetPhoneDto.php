<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class SetPhoneDto
{
    #[Assert\NotBlank(message: 'Phone number is required')]
    #[Assert\Regex(pattern: '/^\+?[1-9]\d{7,14}$/', message: 'Invalid phone number')]
    public string $phone = '';
}

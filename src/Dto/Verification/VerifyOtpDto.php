<?php

namespace App\Dto\Verification;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyOtpDto
{
    #[Assert\NotBlank(message: 'Type is required')]
    #[Assert\Choice(choices: ['email', 'phone'], message: 'Type must be "email" or "phone"')]
    public string $type = '';

    #[Assert\NotBlank(message: 'Code is required')]
    #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Code must be exactly 6 digits')]
    public string $code = '';
}

<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class LoginPhoneVerifyDto
{
    #[Assert\NotBlank(message: 'Phone number is required')]
    public string $phone = '';

    #[Assert\NotBlank(message: 'OTP code is required')]
    #[Assert\Length(exactly: 6, exactMessage: 'OTP must be 6 digits')]
    #[Assert\Regex(pattern: '/^\d{6}$/', message: 'OTP must contain only digits')]
    public string $code = '';
}

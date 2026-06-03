<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterCustomerDto
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email address')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters')]
    public string $password = '';

    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(max: 100)]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(max: 100)]
    public string $lastName = '';

    #[Assert\NotBlank(message: 'Phone is required')]
    #[Assert\Regex(pattern: '/^\+[1-9]\d{1,14}$/', message: 'Invalid phone number format (E.164 expected)')]
    public string $phone = '';

    #[Assert\Length(max: 20)]
    public ?string $referralCode = null;
}

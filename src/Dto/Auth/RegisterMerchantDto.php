<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterMerchantDto
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
    #[Assert\Regex(pattern: '/^\+?[1-9]\d{1,14}$/', message: 'Invalid phone number format (E.164 expected)')]
    public string $phone = '';

    #[Assert\NotBlank(message: 'Business name is required')]
    public string $businessName = '';

    #[Assert\NotBlank(message: 'City is required')]
    public string $city = '';

    #[Assert\NotBlank(message: 'State is required')]
    public string $state = '';

    #[Assert\NotBlank(message: 'Address is required')]
    public string $address = '';

    #[Assert\NotBlank(message: 'Business phone is required')]
    #[Assert\Regex(pattern: '/^\+?[1-9]\d{1,14}$/', message: 'Invalid phone number format')]
    public string $phoneForBusiness = '';

    #[Assert\NotBlank(message: 'Tax ID is required')]
    public string $taxId = '';

    #[Assert\NotBlank(message: 'Bank account number is required')]
    public string $bankAccountNumber = '';

    #[Assert\NotBlank(message: 'Bank IFSC code is required')]
    #[Assert\Regex(pattern: '/^[A-Z]{4}0[A-Z0-9]{6}$/', message: 'Invalid IFSC code format')]
    public string $bankIfscCode = '';

    #[Assert\NotBlank(message: 'Bank account holder name is required')]
    public string $bankAccountHolderName = '';

    #[Assert\IsTrue(message: 'You must accept the terms and conditions')]
    public bool $termsAccepted = false;
}

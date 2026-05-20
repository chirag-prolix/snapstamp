<?php

namespace App\Dto\Merchant;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateMerchantProfileDto
{
    public ?string $businessDescription = null;

    public ?string $city = null;

    public ?string $state = null;

    public ?string $address = null;

    public ?string $postalCode = null;

    public ?string $phoneForBusiness = null;

    #[Assert\Url]
    public ?string $website = null;

    #[Assert\Url]
    public ?string $webhookUrl = null;

    #[Assert\Type('array')]
    public ?array $verificationDocuments = null;

    #[Assert\Type('float')]
    public ?float $latitude = null;

    #[Assert\Type('float')]
    public ?float $longitude = null;
}

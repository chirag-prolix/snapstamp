<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class RejectMerchantDto
{
    #[Assert\NotBlank(message: 'A rejection reason is required.')]
    public ?string $reason = null;
}

<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class GoogleLoginDto
{
    #[Assert\NotBlank(message: 'ID token is required')]
    public string $idToken = '';
}

<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RefreshTokenDto
{
    #[Assert\NotBlank(message: 'Refresh token is required')]
    public string $refreshToken = '';
}

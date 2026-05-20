<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class LoginDto
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email address')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Password is required')]
    public string $password = '';
}

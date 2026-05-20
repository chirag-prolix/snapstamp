<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\UserStatusEnum;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // UUID format → JWT auth path (load by primary key)
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
            $user = $this->userRepository->find($identifier);
        } else {
            $user = $this->userRepository->findOneByEmail($identifier);
        }

        if ($user === null) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        if ($user->isDeleted()) {
            throw new UserNotFoundException('User account has been deleted.');
        }

        if (in_array($user->getStatus(), [UserStatusEnum::PENDING, UserStatusEnum::SUSPENDED, UserStatusEnum::INACTIVE], true)) {
            throw new UserNotFoundException('User account is not active.');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return is_a($class, User::class, true);
    }
}

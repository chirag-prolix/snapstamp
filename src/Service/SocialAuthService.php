<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client as GoogleClient;
use Psr\Log\LoggerInterface;

class SocialAuthService
{
    public function __construct(
        private readonly GoogleClient $googleClient,
        private readonly EntityManagerInterface $em,
        private readonly JwtService $jwtService,
        private readonly UserRepository $userRepository,
        private readonly AuthService $authService,
        private readonly LoggerInterface $logger,
    ) {}

    public function loginWithGoogle(string $idToken): array
    {
        $payload = $this->googleClient->verifyIdToken($idToken);

        if (!$payload) {
            throw new \DomainException('Invalid Google ID token.');
        }

        $googleId  = $payload['sub'];
        $email     = strtolower($payload['email'] ?? '');
        $firstName = $payload['given_name'] ?? 'User';
        $lastName  = $payload['family_name'] ?? '';
        $emailVerified = (bool) ($payload['email_verified'] ?? false);

        if (!$email) {
            throw new \DomainException('Google account has no email address.');
        }

        // Existing user matched by Google ID
        $user = $this->userRepository->findByGoogleId($googleId);

        if ($user === null) {
            // Existing user matched by email — link their Google account
            $user = $this->userRepository->findOneByEmail($email);
            if ($user !== null) {
                $user->setGoogleId($googleId);
                $this->em->flush();
                $this->logger->info('Google account linked to existing user', ['id' => $user->getId()]);
            }
        }

        if ($user === null) {
            // New user — auto-register as customer
            $user = new Customer();
            $user->setEmail($email)
                ->setFirstName($firstName)
                ->setLastName($lastName ?: $firstName)
                ->setGoogleId($googleId)
                ->setIsEmailVerified($emailVerified)
                ->setRoles(['ROLE_CUSTOMER'])
                ->setPassword(bin2hex(random_bytes(32)));

            $this->em->persist($user);
            $this->em->flush();
            $this->logger->info('New customer registered via Google', ['id' => $user->getId(), 'email' => $email]);
        }

        return [
            'accessToken'  => $this->jwtService->generateAccessToken($user),
            'refreshToken' => $this->jwtService->generateRefreshToken($user),
            'user'         => $this->authService->serializeUser($user),
        ];
    }
}

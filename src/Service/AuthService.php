<?php

namespace App\Service;

use App\Dto\Auth\LoginDto;
use App\Dto\Auth\RegisterCustomerDto;
use App\Dto\Auth\RegisterMerchantDto;
use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\User;
use App\Enum\UserStatusEnum;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use App\Service\OtpService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $em,
        private readonly JwtService $jwtService,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
        private readonly ReferralService $referralService,
        private readonly CustomerRepository $customerRepository,
        private readonly OtpService $otpService,
    ) {}

    public function registerCustomer(RegisterCustomerDto $dto): array
    {
        $this->assertEmailAvailable($dto->email);
        $this->assertPhoneAvailable($dto->phone);

        $customer = new Customer();
        $customer->setEmail($dto->email)
            ->setPhone($dto->phone)
            ->setFirstName($dto->firstName)
            ->setLastName($dto->lastName)
            ->setPassword($this->passwordHasher->hashPassword($customer, $dto->password))
            ->setRoles(['ROLE_CUSTOMER']);

        $code = $this->referralService->generateUniqueCode($customer);
        $customer->setReferralCode($code);

        $this->em->persist($customer);
        $this->em->flush();

        if ($dto->referralCode !== null) {
            $referrer = $this->customerRepository->findByReferralCode($dto->referralCode);
            if ($referrer !== null && $referrer->getId() !== $customer->getId()) {
                $this->referralService->processReferral($customer, $referrer);
                $this->em->flush();
            }
        }

        $this->logger->info('Customer registered', ['id' => $customer->getId(), 'email' => $customer->getEmail()]);

        return $this->buildTokenResponse($customer, 201);
    }

    public function registerMerchant(RegisterMerchantDto $dto): array
    {
        $this->assertEmailAvailable($dto->email);
        $this->assertPhoneAvailable($dto->phone);

        $merchant = new Merchant();
        $merchant->setEmail($dto->email)
            ->setPhone($dto->phone)
            ->setFirstName($dto->firstName)
            ->setLastName($dto->lastName)
            ->setPassword($this->passwordHasher->hashPassword($merchant, $dto->password))
            ->setRoles(['ROLE_MERCHANT'])
            ->setStatus(UserStatusEnum::PENDING)
            ->setBusinessName($dto->businessName)
            ->setCity($dto->city)
            ->setState($dto->state)
            ->setAddress($dto->address)
            ->setPhoneForBusiness($dto->phoneForBusiness)
            ->setTaxId($dto->taxId)
            ->setBankAccountNumber($dto->bankAccountNumber)
            ->setBankIfscCode($dto->bankIfscCode)
            ->setBankAccountHolderName($dto->bankAccountHolderName)
            ->setApiKey('pk_' . bin2hex(random_bytes(16)))
            ->setApiSecret('sk_' . bin2hex(random_bytes(32)));

        $this->em->persist($merchant);
        $this->em->flush();

        $this->logger->info('Merchant registered', ['id' => $merchant->getId(), 'email' => $merchant->getEmail()]);

        return [
            'success' => true,
            'message' => 'Registration successful. Your account is pending admin approval.',
            'data'    => ['id' => $merchant->getId()],
        ];
    }

    public function login(LoginDto $dto): array
    {
        $user = $this->userRepository->findOneByEmail($dto->email);

        if ($user === null || $user->isDeleted()) {
            throw new UserNotFoundException('Invalid credentials.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        if ($user->getStatus() === UserStatusEnum::PENDING) {
            throw new AuthenticationException('Account is pending approval.');
        }

        if ($user->getStatus() !== UserStatusEnum::ACTIVE) {
            throw new AuthenticationException('Account is not active.');
        }

        if ($user instanceof Customer) {
            $user->touchLastActive();
        } elseif ($user instanceof Merchant) {
            $user->touchApiAccess();
        }

        $this->em->flush();

        $this->logger->info('User logged in', ['id' => $user->getId()]);

        return $this->buildTokenResponse($user, 200);
    }

    public function setPhoneAndSendOtp(User $user, string $phone): void
    {
        $existing = $this->userRepository->findOneByPhone($phone);
        if ($existing !== null && $existing->getId() !== $user->getId()) {
            throw new \DomainException('Phone number is already registered to another account.');
        }

        if ($user->getPhone() !== $phone) {
            $user->setPhone($phone);
            $this->em->flush();
        }

        $this->otpService->requestPhoneOtp($user);
    }

    public function requestPhoneLoginOtp(string $phone): void
    {
        $user = $this->userRepository->findOneByPhone($phone);

        // Silent return when phone not found to prevent user enumeration
        if ($user === null || $user->isDeleted()) {
            return;
        }

        if ($user->getStatus() === UserStatusEnum::PENDING) {
            throw new AuthenticationException('Account is pending approval.');
        }

        if ($user->getStatus() !== UserStatusEnum::ACTIVE) {
            throw new AuthenticationException('Account is not active.');
        }

        $this->otpService->requestPhoneLoginOtp($user);
    }

    public function loginWithPhoneOtp(string $phone, string $code): array
    {
        $user = $this->userRepository->findOneByPhone($phone);

        if ($user === null || $user->isDeleted()) {
            throw new \DomainException('Invalid OTP code.');
        }

        if ($user->getStatus() === UserStatusEnum::PENDING) {
            throw new AuthenticationException('Account is pending approval.');
        }

        if ($user->getStatus() !== UserStatusEnum::ACTIVE) {
            throw new AuthenticationException('Account is not active.');
        }

        $this->otpService->verifyPhoneLoginOtp($user, $code);

        if ($user instanceof Customer) {
            $user->touchLastActive();
        } elseif ($user instanceof Merchant) {
            $user->touchApiAccess();
        }

        $this->em->flush();
        $this->logger->info('User logged in via phone OTP', ['id' => $user->getId()]);

        return $this->buildTokenResponse($user, 200);
    }

    public function logout(string $rawToken): void
    {
        try {
            $payload = $this->jwtService->decode($rawToken);
            $jti = $payload['jti'] ?? '';
            $exp = (int) ($payload['exp'] ?? 0);
            $remainingTtl = max(0, $exp - time());
            if ($jti) {
                $this->jwtService->blacklist($jti, $remainingTtl);
            }
            $this->logger->info('Token blacklisted', ['jti' => $jti]);
        } catch (\Exception $e) {
            $this->logger->info('Logout with undecodable token', ['error' => $e->getMessage()]);
        }
    }

    public function refreshToken(string $rawRefreshToken): array
    {
        try {
            $payload = $this->jwtService->decode($rawRefreshToken);
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid or expired refresh token.');
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            throw new AuthenticationException('Invalid token type.');
        }

        $jti = $payload['jti'] ?? '';
        if ($jti && $this->jwtService->isBlacklisted($jti)) {
            throw new AuthenticationException('Refresh token has been revoked.');
        }

        $user = $this->userRepository->find($payload['sub'] ?? '');
        if ($user === null || $user->isDeleted() || $user->getStatus() !== UserStatusEnum::ACTIVE) {
            throw new AuthenticationException('User not found or inactive.');
        }

        // Blacklist old refresh token
        if ($jti) {
            $remainingTtl = max(0, (int) ($payload['exp'] ?? 0) - time());
            $this->jwtService->blacklist($jti, $remainingTtl);
        }

        return $this->buildTokenResponse($user, 200);
    }

    public function serializeUser(User $user): array
    {
        $data = [
            'id'              => $user->getId(),
            'email'           => $user->getEmail(),
            'firstName'       => $user->getFirstName(),
            'lastName'        => $user->getLastName(),
            'phone'           => $user->getPhone(),
            'roles'           => $user->getRoles(),
            'isEmailVerified' => $user->isEmailVerified(),
            'isPhoneVerified' => $user->isPhoneVerified(),
            'createdAt'       => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($user instanceof Customer) {
            $data['type']         = 'customer';
            $data['displayName']  = $user->getDisplayName();
            $data['tier']         = $user->getTier();
            $data['referralCode'] = $user->getReferralCode();
        } elseif ($user instanceof Merchant) {
            $data['type']                   = 'merchant';
            $data['businessName']           = $user->getBusinessName();
            $data['onboardingStatus']       = $user->getOnboardingStatus()->value;
            $data['isVerified']             = $user->isVerified();
            $data['apiKey']                 = $user->getApiKey();
            $data['trialEndsAt']            = $user->getTrialEndsAt()->format(\DateTimeInterface::ATOM);
            $data['subscriptionExpiresAt']  = $user->getSubscriptionExpiresAt()?->format(\DateTimeInterface::ATOM);
            $data['hasActiveAccess']        = $user->hasActiveAccess();
        }

        return $data;
    }

    private function assertEmailAvailable(string $email): void
    {
        if ($this->userRepository->findOneByEmail($email) !== null) {
            throw new \DomainException('Email address is already registered.');
        }
    }

    private function assertPhoneAvailable(string $phone): void
    {
        if ($this->userRepository->findOneByPhone($phone) !== null) {
            throw new \DomainException('Phone number is already registered.');
        }
    }

    private function buildTokenResponse(User $user, int $status): array
    {
        return [
            'status'       => $status,
            'accessToken'  => $this->jwtService->generateAccessToken($user),
            'refreshToken' => $this->jwtService->generateRefreshToken($user),
            'user'         => $this->serializeUser($user),
        ];
    }
}

<?php

namespace App\Service;

use App\Entity\OtpToken;
use App\Entity\User;
use App\Message\SendEmailMessage;
use App\Message\SendSmsMessage;
use App\Repository\OtpTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class OtpService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OtpTokenRepository $otpTokenRepository,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
        private readonly int $otpTtl,
        private readonly int $otpLength,
        private readonly int $maxAttempts,
        private readonly int $resendCooldown,
    ) {}

    public function requestEmailOtp(User $user): void
    {
        $this->checkCooldown($user, 'email');
        $this->otpTokenRepository->invalidateAllForUser($user, 'email');

        $code  = $this->generateCode();
        $token = new OtpToken(
            $user,
            'email',
            $this->hashCode($code),
            (new \DateTimeImmutable())->modify("+{$this->otpTtl} seconds")
        );

        $this->em->persist($token);
        $this->em->flush();

        $this->sendEmailOtp($user, $code);
        $this->logger->info('Email OTP sent', ['user' => $user->getId()]);
    }

    public function requestPhoneOtp(User $user): void
    {
        $this->checkCooldown($user, 'phone');
        $this->otpTokenRepository->invalidateAllForUser($user, 'phone');

        $code  = $this->generateCode();
        $token = new OtpToken(
            $user,
            'phone',
            $this->hashCode($code),
            (new \DateTimeImmutable())->modify("+{$this->otpTtl} seconds")
        );

        $this->em->persist($token);
        $this->em->flush();

        $this->sendSmsOtp($user, $code);
        $this->logger->info('Phone OTP sent', ['user' => $user->getId()]);
    }

    public function requestPhoneLoginOtp(User $user): void
    {
        $this->checkCooldown($user, 'phone_login');
        $this->otpTokenRepository->invalidateAllForUser($user, 'phone_login');

        $code  = $this->generateCode();
        $token = new OtpToken(
            $user,
            'phone_login',
            $this->hashCode($code),
            (new \DateTimeImmutable())->modify("+{$this->otpTtl} seconds")
        );

        $this->em->persist($token);
        $this->em->flush();

        $this->sendSmsOtp($user, $code);
        $this->logger->info('Phone login OTP sent', ['user' => $user->getId()]);
    }

    public function verifyPhoneLoginOtp(User $user, string $plainCode): void
    {
        $token = $this->otpTokenRepository->findValidForUser($user, 'phone_login');

        if ($token === null) {
            throw new \DomainException('No active OTP found. Please request a new one.');
        }

        $attempts = $token->incrementAttempts();
        $this->em->flush();

        if ($attempts > $this->maxAttempts) {
            throw new \DomainException('Too many attempts. Please request a new OTP.');
        }

        if (!hash_equals($token->getCodeHash(), $this->hashCode($plainCode))) {
            throw new \DomainException('Invalid OTP code.');
        }

        $token->markUsed();
        $this->em->flush();

        $this->logger->info('Phone login OTP verified', ['user' => $user->getId()]);
    }

    public function verifyOtp(User $user, string $type, string $plainCode): void
    {
        $token = $this->otpTokenRepository->findValidForUser($user, $type);

        if ($token === null) {
            throw new \DomainException('No active OTP found. Please request a new one.');
        }

        $attempts = $token->incrementAttempts();
        $this->em->flush();

        if ($attempts > $this->maxAttempts) {
            throw new \DomainException('Too many attempts. Please request a new OTP.');
        }

        if (!hash_equals($token->getCodeHash(), $this->hashCode($plainCode))) {
            throw new \DomainException('Invalid OTP code.');
        }

        $token->markUsed();

        if ($type === 'email') {
            $user->setIsEmailVerified(true);
        } else {
            $user->setIsPhoneVerified(true);
        }

        $this->em->flush();
        $this->logger->info('OTP verified', ['user' => $user->getId(), 'type' => $type]);
    }

    private function checkCooldown(User $user, string $type): void
    {
        $latest = $this->otpTokenRepository->findLatestForUser($user, $type);
        if ($latest === null) {
            return;
        }

        $secondsAgo = time() - $latest->getCreatedAt()->getTimestamp();
        if ($secondsAgo < $this->resendCooldown) {
            $wait = $this->resendCooldown - $secondsAgo;
            throw new \DomainException("Please wait {$wait} seconds before requesting a new OTP.");
        }
    }

    private function generateCode(): string
    {
        $max = (int) pow(10, $this->otpLength) - 1;
        return str_pad((string) random_int(0, $max), $this->otpLength, '0', STR_PAD_LEFT);
    }

    private function hashCode(string $code): string
    {
        return hash('sha256', $code);
    }

    private function sendEmailOtp(User $user, string $code): void
    {
        $this->bus->dispatch(new SendEmailMessage(
            $user->getEmail(),
            'Your snapstamp verification code',
            "Hi {$user->getFirstName()},\n\n" .
            "Your verification code is: {$code}\n\n" .
            "This code expires in " . ($this->otpTtl / 60) . " minutes.\n\n" .
            "If you did not request this, please ignore this email."
        ));

        $this->logger->info('Email OTP queued (dev code)', ['code' => $code, 'to' => $user->getEmail()]);
    }

    private function sendSmsOtp(User $user, string $code): void
    {
        $this->bus->dispatch(new SendSmsMessage(
            $user->getPhone(),
            "Your snapstamp code: {$code}. Valid for " . ($this->otpTtl / 60) . " minutes."
        ));
    }
}

<?php

namespace App\Service;

use App\Dto\Merchant\UpdateMerchantProfileDto;
use App\Entity\Merchant;
use App\Enum\MerchantStatusEnum;
use App\Enum\UserStatusEnum;
use App\Message\SendEmailMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MerchantOnboardingService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {}

    public function approveMerchant(Merchant $merchant): void
    {
        if ($merchant->getOnboardingStatus() !== MerchantStatusEnum::PENDING) {
            throw new \DomainException('Only PENDING merchants can be approved.');
        }

        $merchant->setOnboardingStatus(MerchantStatusEnum::ACTIVE)
            ->setIsVerified(true)
            ->setStatus(UserStatusEnum::ACTIVE);

        $this->em->flush();

        $this->sendEmail(
            $merchant->getEmail(),
            'Your snapstamp merchant account has been approved',
            "Hi {$merchant->getFirstName()},\n\n" .
            "Congratulations! Your merchant account for {$merchant->getBusinessName()} has been approved.\n\n" .
            "You can now log in and start issuing stamps to your customers.\n\n" .
            "Welcome to snapstamp!"
        );

        $this->logger->info('Merchant approved', ['id' => $merchant->getId()]);
    }

    public function rejectMerchant(Merchant $merchant, string $reason): void
    {
        if ($merchant->getOnboardingStatus() !== MerchantStatusEnum::PENDING) {
            throw new \DomainException('Only PENDING merchants can be rejected.');
        }

        $merchant->setOnboardingStatus(MerchantStatusEnum::REJECTED);

        $this->em->flush();

        $this->sendEmail(
            $merchant->getEmail(),
            'Your snapstamp merchant application was not approved',
            "Hi {$merchant->getFirstName()},\n\n" .
            "Unfortunately, your merchant application for {$merchant->getBusinessName()} was not approved.\n\n" .
            "Reason: {$reason}\n\n" .
            "Please contact support if you have any questions."
        );

        $this->logger->info('Merchant rejected', ['id' => $merchant->getId(), 'reason' => $reason]);
    }

    public function updateProfile(Merchant $merchant, UpdateMerchantProfileDto $dto): void
    {
        if ($dto->businessDescription !== null) {
            $merchant->setBusinessDescription($dto->businessDescription);
        }
        if ($dto->city !== null) {
            $merchant->setCity($dto->city);
        }
        if ($dto->state !== null) {
            $merchant->setState($dto->state);
        }
        if ($dto->address !== null) {
            $merchant->setAddress($dto->address);
        }
        if ($dto->postalCode !== null) {
            $merchant->setPostalCode($dto->postalCode);
        }
        if ($dto->phoneForBusiness !== null) {
            $merchant->setPhoneForBusiness($dto->phoneForBusiness);
        }
        if ($dto->website !== null) {
            $merchant->setWebsite($dto->website);
        }
        if ($dto->webhookUrl !== null) {
            $merchant->setWebhookUrl($dto->webhookUrl);
        }
        if ($dto->verificationDocuments !== null) {
            $merchant->setVerificationDocuments($dto->verificationDocuments);
        }
        if ($dto->latitude !== null) {
            $merchant->setLatitude($dto->latitude);
        }
        if ($dto->longitude !== null) {
            $merchant->setLongitude($dto->longitude);
        }

        $this->em->flush();
        $this->logger->info('Merchant profile updated', ['id' => $merchant->getId()]);
    }

    public function serialize(Merchant $merchant, bool $includeSecret = false): array
    {
        $data = [
            'id'                    => $merchant->getId(),
            'email'                 => $merchant->getEmail(),
            'firstName'             => $merchant->getFirstName(),
            'lastName'              => $merchant->getLastName(),
            'phone'                 => $merchant->getPhone(),
            'businessName'          => $merchant->getBusinessName(),
            'businessDescription'   => $merchant->getBusinessDescription(),
            'city'                  => $merchant->getCity(),
            'state'                 => $merchant->getState(),
            'country'               => $merchant->getCountry(),
            'address'               => $merchant->getAddress(),
            'postalCode'            => $merchant->getPostalCode(),
            'phoneForBusiness'      => $merchant->getPhoneForBusiness(),
            'website'               => $merchant->getWebsite(),
            'taxId'                 => $merchant->getTaxId(),
            'bankAccountHolderName' => $merchant->getBankAccountHolderName(),
            'onboardingStatus'      => $merchant->getOnboardingStatus()->value,
            'isVerified'            => $merchant->isVerified(),
            'verificationDocuments' => $merchant->getVerificationDocuments(),
            'apiKey'                => $merchant->getApiKey(),
            'latitude'              => $merchant->getLatitude(),
            'longitude'             => $merchant->getLongitude(),
            'isEmailVerified'       => $merchant->isEmailVerified(),
            'isPhoneVerified'       => $merchant->isPhoneVerified(),
            'createdAt'             => $merchant->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($includeSecret) {
            $data['apiSecret'] = $merchant->getApiSecret();
        }

        return $data;
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        $this->bus->dispatch(new SendEmailMessage($to, $subject, $body));
    }
}

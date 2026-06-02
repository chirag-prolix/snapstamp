<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Notification;
use App\Entity\Transaction;
use App\Enum\NotificationTypeEnum;
use App\Enum\TransactionTypeEnum;
use App\Message\SendPushNotificationMessage;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ReferralService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomerRepository $customerRepository,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {}

    public function generateUniqueCode(Customer $customer): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $customer->getFirstName()), 0, 3));
        $prefix = str_pad($prefix, 3, 'X');

        do {
            $suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));
            $code   = $prefix . $suffix;
        } while ($this->customerRepository->findByReferralCode($code) !== null);

        return $code;
    }

    private const REFERRAL_BONUS_STAMPS = 5;

    public function processReferral(Customer $newCustomer, Customer $referrer): void
    {
        $newCustomer->setReferredBy($referrer);
        $referrer->incrementReferralCount();
        $referrer->incrementTotalStamps(self::REFERRAL_BONUS_STAMPS);

        $transaction = (new Transaction())
            ->setTransactionType(TransactionTypeEnum::REFERRAL_BONUS)
            ->setCustomer($referrer)
            ->setStamps(self::REFERRAL_BONUS_STAMPS)
            ->setReferenceId($newCustomer->getId())
            ->setDescription(sprintf(
                'Referral bonus: %s joined via your code — +%d bonus stamps awarded',
                $newCustomer->getFirstName(),
                self::REFERRAL_BONUS_STAMPS
            ));

        $this->em->persist($transaction);

        $notificationMessage = sprintf(
            '%s joined using your referral code. You\'ve earned %d bonus stamps!',
            $newCustomer->getFirstName(),
            self::REFERRAL_BONUS_STAMPS
        );

        $notification = (new Notification())
            ->setCustomer($referrer)
            ->setType(NotificationTypeEnum::SYSTEM)
            ->setTitle('New referral! +' . self::REFERRAL_BONUS_STAMPS . ' bonus stamps')
            ->setMessage($notificationMessage)
            ->setChannels(['PUSH']);

        $this->em->persist($notification);

        $this->bus->dispatch(new SendPushNotificationMessage(
            customerId: $referrer->getId(),
            title: 'New referral! +' . self::REFERRAL_BONUS_STAMPS . ' bonus stamps',
            body: $notificationMessage,
        ));

        $this->logger->info('Referral processed', [
            'referrer'    => $referrer->getId(),
            'newCustomer' => $newCustomer->getId(),
        ]);
    }

    public function getReferralStats(Customer $customer): array
    {
        $referrals = $this->customerRepository->findByReferrer($customer);

        return [
            'referralCode'        => $customer->getReferralCode(),
            'referralCount'       => $customer->getReferralCount(),
            'bonusStampsPerReferral' => self::REFERRAL_BONUS_STAMPS,
            'totalBonusStamps'    => $customer->getReferralCount() * self::REFERRAL_BONUS_STAMPS,
            'referrals'           => array_map(fn(Customer $r) => [
                'id'        => $r->getId(),
                'firstName' => $r->getFirstName(),
                'lastName'  => $r->getLastName(),
                'joinedAt'  => $r->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $referrals),
        ];
    }
}

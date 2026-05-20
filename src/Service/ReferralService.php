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

    public function processReferral(Customer $newCustomer, Customer $referrer): void
    {
        $newCustomer->setReferredBy($referrer);
        $referrer->incrementReferralCount();

        $transaction = (new Transaction())
            ->setTransactionType(TransactionTypeEnum::REFERRAL_BONUS)
            ->setCustomer($referrer)
            ->setReferenceId($newCustomer->getId())
            ->setDescription(sprintf(
                'Referral bonus: %s joined via your code',
                $newCustomer->getFirstName()
            ));

        $this->em->persist($transaction);

        $notification = (new Notification())
            ->setCustomer($referrer)
            ->setType(NotificationTypeEnum::SYSTEM)
            ->setTitle('New referral!')
            ->setMessage(sprintf('%s joined using your referral code.', $newCustomer->getFirstName()))
            ->setChannels(['PUSH']);

        $this->em->persist($notification);

        $this->bus->dispatch(new SendPushNotificationMessage(
            customerId: $referrer->getId(),
            title: 'New referral!',
            body: sprintf('%s joined using your referral code.', $newCustomer->getFirstName()),
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
            'referralCode'  => $customer->getReferralCode(),
            'referralCount' => $customer->getReferralCount(),
            'referrals'     => array_map(fn(Customer $r) => [
                'id'        => $r->getId(),
                'firstName' => $r->getFirstName(),
                'lastName'  => $r->getLastName(),
                'joinedAt'  => $r->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $referrals),
        ];
    }
}

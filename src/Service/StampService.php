<?php

namespace App\Service;

use App\Dto\Stamp\IssueStampDto;
use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\Notification;
use App\Entity\Stamp;
use App\Entity\StampCard;
use App\Entity\Transaction;
use App\Enum\NotificationTypeEnum;
use App\Enum\StampCardStatusEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Message\SendPushNotificationMessage;
use App\Repository\StampCardRepository;
use App\Repository\StampRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class StampService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StampCardRepository $stampCardRepository,
        private readonly StampRepository $stampRepository,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus,
    ) {}

    public function issueStamps(Merchant $merchant, IssueStampDto $dto): array
    {
        if (!$merchant->hasActiveAccess()) {
            throw new \DomainException('Trial expired. Please subscribe to continue issuing stamps.');
        }

        $customer = $this->resolveCustomer($dto);

        $stampCard = $this->getOrCreateStampCard($customer, $merchant, $dto);

        if ($stampCard->getStatus() === StampCardStatusEnum::COMPLETED) {
            throw new \DomainException('Stamp card is already completed.');
        }

        if ($stampCard->getStatus() !== StampCardStatusEnum::ACTIVE || $stampCard->isExpired()) {
            throw new \DomainException('Stamp card is not active or has expired.');
        }

        $nextSeq = $this->stampRepository->getNextSequenceForCard($stampCard);
        $stamps = [];

        for ($i = 0; $i < $dto->count; $i++) {
            $stamp = new Stamp();
            $stamp->setStampCard($stampCard)
                ->setCustomer($customer)
                ->setMerchant($merchant)
                ->setStampSequence($nextSeq + $i)
                ->setIsBonus($dto->isBonus)
                ->setTransactionId($dto->transactionId)
                ->setNotes($dto->notes);
            $this->em->persist($stamp);
            $stamps[] = $stamp;
        }

        $stampCard->incrementStampCount($dto->count);
        $customer->incrementTotalStamps($dto->count);
        $customer->touchLastActive();

        for ($i = 0; $i < $dto->count; $i++) {
            $merchant->incrementTotalStampsIssued();
        }
        $merchant->touchApiAccess();

        $this->createTransaction($customer, $merchant, $stampCard, $dto->count);
        $this->createNotification($customer, $merchant, $stampCard, $dto->count);

        $this->em->flush();

        $this->logger->info('Stamps issued', [
            'merchant' => $merchant->getId(),
            'customer' => $customer->getId(),
            'count'    => $dto->count,
            'card'     => $stampCard->getId(),
        ]);

        return [
            'stampCard' => $this->serializeStampCard($stampCard),
            'stamps'    => array_map(fn(Stamp $s) => $this->serializeStamp($s), $stamps),
        ];
    }

    public function getCustomerCard(Merchant $merchant, string $customerId): array
    {
        $customer = $this->userRepository->find($customerId);
        if (!$customer instanceof Customer) {
            throw new \DomainException('Customer not found.');
        }

        $card = $this->stampCardRepository->findActiveByCustomerAndMerchant($customer, $merchant);
        if ($card === null) {
            throw new \DomainException('No active stamp card found for this customer.');
        }

        $stamps = $this->stampRepository->findByStampCard($card);

        return [
            'stampCard' => $this->serializeStampCard($card),
            'stamps'    => array_map(fn(Stamp $s) => $this->serializeStamp($s), $stamps),
        ];
    }

    public function getCustomerCards(Customer $customer): array
    {
        $cards = $this->stampCardRepository->findByCustomer($customer);

        return array_map(function (StampCard $card) {
            return $this->serializeStampCard($card);
        }, $cards);
    }

    public function getCustomerCardDetail(Customer $customer, string $cardId): array
    {
        $card = $this->stampCardRepository->find($cardId);

        if ($card === null || $card->getCustomer()->getId() !== $customer->getId()) {
            throw new \DomainException('Stamp card not found.');
        }

        $stamps = $this->stampRepository->findByStampCard($card);

        return [
            'stampCard' => $this->serializeStampCard($card),
            'stamps'    => array_map(fn(Stamp $s) => $this->serializeStamp($s), $stamps),
        ];
    }

    public function serializeStampCard(StampCard $card): array
    {
        return [
            'id'                => $card->getId(),
            'cardNumber'        => $card->getCardNumber(),
            'displayName'       => $card->getDisplayName(),
            'status'            => $card->getStatus()->value,
            'currentStampCount' => $card->getCurrentStampCount(),
            'totalSlotsRequired'=> $card->getTotalSlotsRequired(),
            'progress'          => $card->getProgress(),
            'bonusStamps'       => $card->getBonusStamps(),
            'createdAt'         => $card->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lastStampAt'       => $card->getLastStampAt()?->format(\DateTimeInterface::ATOM),
            'completedAt'       => $card->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            'expiresAt'         => $card->getExpiresAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    public function serializeStamp(Stamp $stamp): array
    {
        return [
            'id'            => $stamp->getId(),
            'stampSequence' => $stamp->getStampSequence(),
            'isBonus'       => $stamp->isBonus(),
            'status'        => $stamp->getStatus()->value,
            'collectedAt'   => $stamp->getCollectedAt()->format(\DateTimeInterface::ATOM),
            'expiresAt'     => $stamp->getExpiresAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function resolveCustomer(IssueStampDto $dto): Customer
    {
        if ($dto->customerId !== null) {
            $user = $this->userRepository->find($dto->customerId);
        } elseif ($dto->customerPhone !== null) {
            $user = $this->userRepository->findOneByPhone($dto->customerPhone);
        } else {
            throw new \DomainException('Provide customerId or customerPhone.');
        }

        if (!$user instanceof Customer) {
            throw new \DomainException('Customer not found.');
        }

        return $user;
    }

    private function getOrCreateStampCard(Customer $customer, Merchant $merchant, IssueStampDto $dto): StampCard
    {
        $card = $this->stampCardRepository->findActiveByCustomerAndMerchant($customer, $merchant);

        if ($card === null) {
            $card = new StampCard();
            $card->setCustomer($customer)
                ->setMerchant($merchant)
                ->setCardNumber('SC-' . strtoupper(bin2hex(random_bytes(6))))
                ->setTotalSlotsRequired(10)
                ->setDisplayName($merchant->getBusinessName() . ' Card');
            $this->em->persist($card);
        }

        if ($dto->cardExpiresAt !== null) {
            $expiresAt = \DateTimeImmutable::createFromFormat('Y-m-d', $dto->cardExpiresAt);
            if ($expiresAt === false) {
                throw new \DomainException('Invalid cardExpiresAt format. Use YYYY-MM-DD.');
            }
            if ($expiresAt < new \DateTimeImmutable('today')) {
                throw new \DomainException('Card expiry date must be in the future.');
            }
            $card->setExpiresAt($expiresAt->setTime(23, 59, 59));
        }

        return $card;
    }

    private function createTransaction(Customer $customer, Merchant $merchant, StampCard $card, int $count): void
    {
        $tx = new Transaction();
        $tx->setTransactionType(TransactionTypeEnum::STAMP_ISSUED)
            ->setStatus(TransactionStatusEnum::COMPLETED)
            ->setCustomer($customer)
            ->setMerchant($merchant)
            ->setStamps($count)
            ->setReferenceId($card->getId())
            ->setDescription(sprintf('%d stamp(s) issued at %s', $count, $merchant->getBusinessName()));

        $this->em->persist($tx);
    }

    private function createNotification(Customer $customer, Merchant $merchant, StampCard $card, int $count): void
    {
        $completed = $card->getStatus() === StampCardStatusEnum::COMPLETED;
        $remaining = $card->getTotalSlotsRequired() - $card->getCurrentStampCount();

        $message = $completed
            ? sprintf('Your %s stamp card is complete! Claim your reward.', $merchant->getBusinessName())
            : sprintf(
                'Visit %s %d more time(s) to complete your card.',
                $merchant->getBusinessName(),
                $remaining
            );

        $notification = new Notification();
        $notification->setCustomer($customer)
            ->setMerchant($merchant)
            ->setType(NotificationTypeEnum::STAMP_RECEIVED)
            ->setTitle(sprintf('You got %d stamp%s!', $count, $count > 1 ? 's' : ''))
            ->setMessage($message)
            ->setChannels(['in-app']);

        $this->em->persist($notification);

        $this->bus->dispatch(new SendPushNotificationMessage(
            $customer->getId(),
            $notification->getTitle(),
            $notification->getMessage(),
            ['type' => NotificationTypeEnum::STAMP_RECEIVED->value]
        ));
    }
}

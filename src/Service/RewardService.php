<?php

namespace App\Service;

use App\Dto\Reward\CreateRewardDto;
use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\Notification;
use App\Entity\Reward;
use App\Entity\RewardRedemption;
use App\Entity\Transaction;
use App\Enum\NotificationTypeEnum;
use App\Enum\RewardStatusEnum;
use App\Enum\RewardTypeEnum;
use App\Enum\RewardRedemptionStatusEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Message\SendPushNotificationMessage;
use App\Repository\RewardRedemptionRepository;
use App\Repository\RewardRepository;
use App\Repository\StampCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class RewardService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RewardRepository $rewardRepository,
        private readonly RewardRedemptionRepository $redemptionRepository,
        private readonly StampCardRepository $stampCardRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus,
    ) {}

    public function createReward(Merchant $merchant, CreateRewardDto $dto): array
    {
        $reward = new Reward();
        $reward->setMerchant($merchant)
            ->setTitle($dto->title)
            ->setDescription($dto->description)
            ->setRewardType(RewardTypeEnum::from($dto->rewardType))
            ->setValue($dto->value)
            ->setStampRequirement($dto->stampRequirement)
            ->setMaxRedemptions($dto->maxRedemptions)
            ->setExpiresAt(new \DateTimeImmutable($dto->expiresAt))
            ->setTerms($dto->terms)
            ->setStatus(RewardStatusEnum::ACTIVE);

        $this->em->persist($reward);
        $this->em->flush();

        $this->logger->info('Reward created', [
            'merchant' => $merchant->getId(),
            'reward'   => $reward->getId(),
        ]);

        return $this->serializeReward($reward);
    }

    public function getMerchantRewards(Merchant $merchant): array
    {
        $rewards = $this->rewardRepository->findActiveByMerchant($merchant);
        return array_map(fn(Reward $r) => $this->serializeReward($r), $rewards);
    }

    public function listActiveRewards(
        ?string $merchantId,
        ?float  $lat    = null,
        ?float  $lon    = null,
        float   $radius = 10.0
    ): array {
        if ($lat !== null && $lon !== null) {
            $results = $this->rewardRepository->findNearbyWithDistance($lat, $lon, $radius);
            return array_map(fn(array $item) => array_merge(
                $this->serializeReward($item['reward']),
                ['distanceKm' => $item['distanceKm']]
            ), $results);
        }

        $filters = [];
        if ($merchantId !== null) {
            $filters['merchant_id'] = $merchantId;
        }
        $rewards = $this->rewardRepository->search($filters);
        return array_map(fn(Reward $r) => $this->serializeReward($r), $rewards);
    }

    public function redeemReward(Customer $customer, string $rewardId): array
    {
        $reward = $this->rewardRepository->find($rewardId);
        if ($reward === null) {
            throw new \DomainException('Reward not found.');
        }

        if ($reward->getStatus() !== RewardStatusEnum::ACTIVE) {
            throw new \DomainException('Reward is not available.');
        }

        if ($reward->isExpired()) {
            throw new \DomainException('Reward has expired.');
        }

        if ($reward->getMaxRedemptions() !== null && $reward->getCurrentRedemptions() >= $reward->getMaxRedemptions()) {
            throw new \DomainException('Reward redemption limit reached.');
        }

        $stampCard = $this->stampCardRepository->findCompletedByCustomerAndMerchant($customer, $reward->getMerchant());
        if ($stampCard === null) {
            throw new \DomainException('No completed stamp card found for this reward.');
        }

        if ($this->redemptionRepository->findPendingByStampCard($stampCard) !== null) {
            throw new \DomainException('A redemption for this card is already pending.');
        }

        $redemption = new RewardRedemption();
        $redemption->setReward($reward)
            ->setCustomer($customer)
            ->setStampCard($stampCard);

        $this->em->persist($redemption);
        $this->createRedemptionNotification($customer, $reward, $redemption);
        $this->em->flush();

        $this->logger->info('Reward redemption initiated', [
            'customer' => $customer->getId(),
            'reward'   => $reward->getId(),
            'code'     => $redemption->getRedeemCode(),
        ]);

        return $this->serializeRedemption($redemption);
    }

    public function approveRedemption(Merchant $merchant, string $redeemCode): array
    {
        $redemption = $this->redemptionRepository->findByRedeemCode($redeemCode);
        if ($redemption === null) {
            throw new \DomainException('Redemption not found.');
        }

        if ($redemption->getReward()->getMerchant()->getId() !== $merchant->getId()) {
            throw new \DomainException('Redemption does not belong to this merchant.');
        }

        if ($redemption->getStatus() !== RewardRedemptionStatusEnum::PENDING) {
            throw new \DomainException('Redemption is not pending.');
        }

        $customer = $redemption->getCustomer();
        $reward   = $redemption->getReward();

        $redemption->approve($merchant);
        $reward->incrementCurrentRedemptions();
        $customer->incrementTotalRewardsRedeemed();

        $this->createTransaction($customer, $merchant, $reward);
        $this->createApprovalNotification($customer, $merchant, $reward);
        $this->em->flush();

        $this->logger->info('Reward redemption approved', [
            'merchant' => $merchant->getId(),
            'customer' => $customer->getId(),
            'reward'   => $reward->getId(),
            'code'     => $redeemCode,
        ]);

        return $this->serializeRedemption($redemption);
    }

    public function getMerchantRedemptions(Merchant $merchant): array
    {
        $redemptions = $this->redemptionRepository->findByMerchant($merchant);
        return array_map(fn(RewardRedemption $r) => $this->serializeRedemption($r), $redemptions);
    }

    public function getCustomerRedemptions(Customer $customer): array
    {
        $redemptions = $this->redemptionRepository->findByCustomer($customer);
        return array_map(fn(RewardRedemption $r) => $this->serializeRedemption($r), $redemptions);
    }

    public function serializeReward(Reward $reward): array
    {
        return [
            'id'                  => $reward->getId(),
            'title'               => $reward->getTitle(),
            'description'         => $reward->getDescription(),
            'rewardType'          => $reward->getRewardType()->value,
            'value'               => $reward->getValue(),
            'currency'            => $reward->getCurrency(),
            'stampRequirement'    => $reward->getStampRequirement(),
            'maxRedemptions'      => $reward->getMaxRedemptions(),
            'currentRedemptions'  => $reward->getCurrentRedemptions(),
            'status'              => $reward->getStatus()->value,
            'expiresAt'           => $reward->getExpiresAt()->format(\DateTimeInterface::ATOM),
            'terms'               => $reward->getTerms(),
            'imageUrl'            => $reward->getImageUrl(),
            'createdAt'           => $reward->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    public function serializeRedemption(RewardRedemption $redemption): array
    {
        return [
            'id'                  => $redemption->getId(),
            'redeemCode'          => $redemption->getRedeemCode(),
            'status'              => $redemption->getStatus()->value,
            'redeemedAt'          => $redemption->getRedeemedAt()->format(\DateTimeInterface::ATOM),
            'merchantApprovedAt'  => $redemption->getMerchantApprovedAt()?->format(\DateTimeInterface::ATOM),
            'reward'              => $this->serializeReward($redemption->getReward()),
            'voucherUrl'          => $redemption->getVoucherUrl(),
            'notes'               => $redemption->getNotes(),
        ];
    }

    private function createRedemptionNotification(Customer $customer, Reward $reward, RewardRedemption $redemption): void
    {
        $notification = new Notification();
        $notification->setCustomer($customer)
            ->setMerchant($reward->getMerchant())
            ->setType(NotificationTypeEnum::REWARD_REDEEMED)
            ->setTitle('Redemption initiated!')
            ->setMessage(sprintf(
                'Show code %s at %s to claim: %s.',
                $redemption->getRedeemCode(),
                $reward->getMerchant()->getBusinessName(),
                $reward->getTitle()
            ))
            ->setChannels(['in-app']);

        $this->em->persist($notification);

        $this->bus->dispatch(new SendPushNotificationMessage(
            $customer->getId(),
            $notification->getTitle(),
            $notification->getMessage(),
            ['type' => NotificationTypeEnum::REWARD_REDEEMED->value]
        ));
    }

    private function createApprovalNotification(Customer $customer, Merchant $merchant, Reward $reward): void
    {
        $notification = new Notification();
        $notification->setCustomer($customer)
            ->setMerchant($merchant)
            ->setType(NotificationTypeEnum::REWARD_REDEEMED)
            ->setTitle('Reward claimed!')
            ->setMessage(sprintf(
                'Your reward "%s" at %s has been confirmed.',
                $reward->getTitle(),
                $merchant->getBusinessName()
            ))
            ->setChannels(['in-app']);

        $this->em->persist($notification);

        $this->bus->dispatch(new SendPushNotificationMessage(
            $customer->getId(),
            $notification->getTitle(),
            $notification->getMessage(),
            ['type' => NotificationTypeEnum::REWARD_REDEEMED->value]
        ));
    }

    private function createTransaction(Customer $customer, Merchant $merchant, Reward $reward): void
    {
        $tx = new Transaction();
        $tx->setTransactionType(TransactionTypeEnum::REWARD_REDEEMED)
            ->setStatus(TransactionStatusEnum::COMPLETED)
            ->setCustomer($customer)
            ->setMerchant($merchant)
            ->setDescription(sprintf('"%s" redeemed at %s', $reward->getTitle(), $merchant->getBusinessName()));

        $this->em->persist($tx);
    }
}

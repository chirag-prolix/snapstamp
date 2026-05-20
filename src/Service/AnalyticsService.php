<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Repository\RewardRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TransactionRepository $transactionRepository,
        private readonly RewardRepository $rewardRepository,
    ) {}

    public function getMerchantStats(Merchant $merchant): array
    {
        $since30 = new \DateTimeImmutable('-30 days');

        $topRewards = $this->rewardRepository->getTopByMerchant($merchant, 5);

        return [
            'totals' => [
                'stampsIssued'    => $merchant->getTotalStampsIssued(),
                'rewardsRedeemed' => $merchant->getTotalRewardsGiven(),
                'customers'       => $merchant->getTotalCustomers(),
            ],
            'last30Days' => [
                'stampsIssued'    => $this->transactionRepository->sumStampsByMerchantSince($merchant, $since30),
                'redemptions'     => $this->transactionRepository->countRedemptionsByMerchantSince($merchant, $since30),
                'activeCustomers' => $this->transactionRepository->countDistinctCustomersByMerchantSince($merchant, $since30),
            ],
            'topRewards' => array_map(fn($r) => [
                'id'          => $r->getId(),
                'title'       => $r->getTitle(),
                'redemptions' => $r->getCurrentRedemptions(),
            ], $topRewards),
        ];
    }

    public function getAdminStats(): array
    {
        $since30 = new \DateTimeImmutable('-30 days');

        $customerCount = (int) $this->em->createQuery(
            'SELECT COUNT(u.id) FROM App\Entity\Customer u WHERE u.deletedAt IS NULL'
        )->getSingleScalarResult();

        $merchantCount = (int) $this->em->createQuery(
            'SELECT COUNT(u.id) FROM App\Entity\Merchant u WHERE u.deletedAt IS NULL'
        )->getSingleScalarResult();

        $newCustomers30 = (int) $this->em->createQuery(
            'SELECT COUNT(u.id) FROM App\Entity\Customer u WHERE u.createdAt >= :since'
        )->setParameter('since', $since30)->getSingleScalarResult();

        $newMerchants30 = (int) $this->em->createQuery(
            'SELECT COUNT(u.id) FROM App\Entity\Merchant u WHERE u.createdAt >= :since'
        )->setParameter('since', $since30)->getSingleScalarResult();

        $platformTotals = $this->transactionRepository->getPlatformTotals();

        return [
            'totals' => [
                'customers'       => $customerCount,
                'merchants'       => $merchantCount,
                'activeRewards'   => $this->rewardRepository->countActive(),
                'stampsIssued'    => $platformTotals['stampsIssued'],
                'rewardsRedeemed' => $platformTotals['redemptions'],
            ],
            'last30Days' => [
                'newCustomers' => $newCustomers30,
                'newMerchants' => $newMerchants30,
            ],
        ];
    }
}

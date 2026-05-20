<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\Transaction;
use App\Enum\TransactionTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByCustomer(Customer $customer, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByMerchant(Merchant $merchant, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.merchant = :merchant')
            ->setParameter('merchant', $merchant)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function sumStampsByMerchantSince(Merchant $merchant, \DateTimeImmutable $since): int
    {
        return (int) ($this->createQueryBuilder('t')
            ->select('SUM(t.stamps)')
            ->where('t.merchant = :merchant')
            ->andWhere('t.transactionType = :type')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('merchant', $merchant)
            ->setParameter('type', TransactionTypeEnum::STAMP_ISSUED)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    public function countRedemptionsByMerchantSince(Merchant $merchant, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.merchant = :merchant')
            ->andWhere('t.transactionType = :type')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('merchant', $merchant)
            ->setParameter('type', TransactionTypeEnum::REWARD_REDEEMED)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDistinctCustomersByMerchantSince(Merchant $merchant, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.customer)')
            ->where('t.merchant = :merchant')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('merchant', $merchant)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getPlatformTotals(): array
    {
        $stampsIssued = (int) ($this->createQueryBuilder('t')
            ->select('SUM(t.stamps)')
            ->where('t.transactionType = :type')
            ->setParameter('type', TransactionTypeEnum::STAMP_ISSUED)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        $redemptions = (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.transactionType = :type')
            ->setParameter('type', TransactionTypeEnum::REWARD_REDEEMED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'stampsIssued' => $stampsIssued,
            'redemptions'  => $redemptions,
        ];
    }
}

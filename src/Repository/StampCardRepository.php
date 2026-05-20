<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\StampCard;
use App\Enum\StampCardStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StampCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StampCard::class);
    }

    public function findActiveByCustomerAndMerchant(Customer $customer, Merchant $merchant): ?StampCard
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.customer = :customer')
            ->andWhere('sc.merchant = :merchant')
            ->andWhere('sc.status = :status')
            ->setParameter('customer', $customer)
            ->setParameter('merchant', $merchant)
            ->setParameter('status', StampCardStatusEnum::ACTIVE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('sc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedByCustomerAndMerchant(Customer $customer, Merchant $merchant): ?StampCard
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.customer = :customer')
            ->andWhere('sc.merchant = :merchant')
            ->andWhere('sc.status = :status')
            ->setParameter('customer', $customer)
            ->setParameter('merchant', $merchant)
            ->setParameter('status', StampCardStatusEnum::COMPLETED)
            ->orderBy('sc.completedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpired(): array
    {
        return $this->createQueryBuilder('sc')
            ->where('sc.expiresAt < :now')
            ->andWhere('sc.status = :status')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', StampCardStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();
    }
}

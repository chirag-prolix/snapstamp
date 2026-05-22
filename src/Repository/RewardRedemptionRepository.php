<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\RewardRedemption;
use App\Entity\StampCard;
use App\Enum\RewardRedemptionStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RewardRedemptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RewardRedemption::class);
    }

    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('rr')
            ->where('rr.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('rr.redeemedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPending(): array
    {
        return $this->createQueryBuilder('rr')
            ->where('rr.status = :status')
            ->setParameter('status', RewardRedemptionStatusEnum::PENDING)
            ->orderBy('rr.redeemedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByRedeemCode(string $code): ?RewardRedemption
    {
        return $this->findOneBy(['redeemCode' => $code]);
    }

    public function findPendingByStampCard(StampCard $card): ?RewardRedemption
    {
        return $this->createQueryBuilder('rr')
            ->where('rr.stampCard = :card')
            ->andWhere('rr.status = :status')
            ->setParameter('card', $card)
            ->setParameter('status', RewardRedemptionStatusEnum::PENDING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('rr')
            ->where('rr.customer = :customer')
            ->andWhere('rr.status = :status')
            ->setParameter('customer', $customer)
            ->setParameter('status', RewardRedemptionStatusEnum::PENDING)
            ->getQuery()
            ->getResult();
    }

    public function findByMerchant(Merchant $merchant): array
    {
        return $this->createQueryBuilder('rr')
            ->join('rr.reward', 'r')
            ->where('r.merchant = :merchant')
            ->setParameter('merchant', $merchant)
            ->orderBy('rr.redeemedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

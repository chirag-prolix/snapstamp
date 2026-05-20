<?php

namespace App\Repository;

use App\Entity\Merchant;
use App\Entity\Reward;
use App\Enum\RewardStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reward::class);
    }

    public function findActiveByMerchant(Merchant $merchant): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.merchant = :merchant')
            ->andWhere('r.status = :status')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('merchant', $merchant)
            ->setParameter('status', RewardStatusEnum::ACTIVE)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNearby(float $latitude, float $longitude, float $radiusKm = 5): array
    {
        $sql = '
            SELECT r.id
            FROM rewards r
            JOIN users m ON r.merchant_id = m.id
            WHERE r.status = \'ACTIVE\'
              AND r.expires_at > NOW()
              AND m.is_verified = 1
              AND (6371 * ACOS(
                      COS(RADIANS(:lat)) * COS(RADIANS(m.latitude)) *
                      COS(RADIANS(m.longitude) - RADIANS(:lon)) +
                      SIN(RADIANS(:lat)) * SIN(RADIANS(m.latitude))
                  )) < :radius
        ';

        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchAllAssociative($sql, [
            'lat'    => $latitude,
            'lon'    => $longitude,
            'radius' => $radiusKm,
        ]);

        $ids = array_column($rows, 'id');
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function findNearbyWithDistance(float $lat, float $lon, float $radiusKm = 10.0): array
    {
        $sql = "
            SELECT r.id,
                   ROUND(6371 * ACOS(
                       LEAST(1.0, GREATEST(-1.0,
                           COS(RADIANS(:lat)) * COS(RADIANS(m.latitude)) *
                           COS(RADIANS(m.longitude) - RADIANS(:lon)) +
                           SIN(RADIANS(:lat)) * SIN(RADIANS(m.latitude))
                       ))
                   ), 2) AS distance_km
            FROM rewards r
            JOIN users m ON r.merchant_id = m.id
            WHERE r.status = 'ACTIVE'
              AND r.expires_at > NOW()
              AND m.is_verified = TRUE
              AND (6371 * ACOS(LEAST(1.0, GREATEST(-1.0,
                      COS(RADIANS(:lat)) * COS(RADIANS(m.latitude)) *
                      COS(RADIANS(m.longitude) - RADIANS(:lon)) +
                      SIN(RADIANS(:lat)) * SIN(RADIANS(m.latitude))
                  )))) <= :radius
            ORDER BY distance_km ASC
        ";

        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchAllAssociative($sql, [
            'lat'    => $lat,
            'lon'    => $lon,
            'radius' => $radiusKm,
        ]);

        if (empty($rows)) {
            return [];
        }

        $ids       = array_column($rows, 'id');
        $distances = array_column($rows, 'distance_km', 'id');

        $rewards = $this->createQueryBuilder('r')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        usort($rewards, fn(Reward $a, Reward $b) => $distances[$a->getId()] <=> $distances[$b->getId()]);

        return array_map(fn(Reward $r) => [
            'reward'     => $r,
            'distanceKm' => (float) $distances[$r->getId()],
        ], $rewards);
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('status', RewardStatusEnum::ACTIVE)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTopByMerchant(Merchant $merchant, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.merchant = :merchant')
            ->setParameter('merchant', $merchant)
            ->orderBy('r.currentRedemptions', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function search(array $filters): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('status', RewardStatusEnum::ACTIVE)
            ->setParameter('now', new \DateTimeImmutable());

        if (!empty($filters['type'])) {
            $qb->andWhere('r.rewardType = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['max_stamps'])) {
            $qb->andWhere('r.stampRequirement <= :maxStamps')
               ->setParameter('maxStamps', $filters['max_stamps']);
        }

        if (!empty($filters['merchant_id'])) {
            $qb->andWhere('r.merchant = :merchant')
               ->setParameter('merchant', $filters['merchant_id']);
        }

        return $qb->orderBy('r.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}

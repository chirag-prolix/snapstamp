<?php

namespace App\Repository;

use App\Entity\Merchant;
use App\Enum\MerchantStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MerchantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Merchant::class);
    }

    public function findVerified(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isVerified = true')
            ->andWhere('m.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findNearby(float $latitude, float $longitude, float $radiusKm = 5): array
    {
        $sql = '
            SELECT m.id,
                   (6371 * ACOS(
                       COS(RADIANS(:lat)) * COS(RADIANS(m.latitude)) *
                       COS(RADIANS(m.longitude) - RADIANS(:lon)) +
                       SIN(RADIANS(:lat)) * SIN(RADIANS(m.latitude))
                   )) AS distance
            FROM users m
            WHERE m.user_type = \'merchant\'
              AND m.is_verified = 1
              AND m.deleted_at IS NULL
            HAVING distance < :radius
            ORDER BY distance ASC
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

        return $this->createQueryBuilder('m')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function findByApiKey(string $apiKey): ?Merchant
    {
        return $this->findOneBy(['apiKey' => $apiKey]);
    }

    public function findByStatus(MerchantStatusEnum $status): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.onboardingStatus = :status')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('status', $status->value)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPending(): array
    {
        return $this->findByStatus(MerchantStatusEnum::PENDING);
    }
}

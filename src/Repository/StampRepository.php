<?php

namespace App\Repository;

use App\Entity\Stamp;
use App\Entity\StampCard;
use App\Enum\StampStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StampRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stamp::class);
    }

    public function findByStampCard(StampCard $stampCard): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.stampCard = :stampCard')
            ->setParameter('stampCard', $stampCard)
            ->orderBy('s.stampSequence', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findExpiredStamps(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.expiresAt < :now')
            ->andWhere('s.status = :status')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', StampStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function getNextSequenceForCard(StampCard $stampCard): int
    {
        $result = $this->createQueryBuilder('s')
            ->select('MAX(s.stampSequence)')
            ->where('s.stampCard = :stampCard')
            ->setParameter('stampCard', $stampCard)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result + 1;
    }
}

<?php

namespace App\Repository;

use App\Entity\OtpToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OtpTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OtpToken::class);
    }

    public function findLatestForUser(User $user, string $type): ?OtpToken
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->andWhere('o.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findValidForUser(User $user, string $type): ?OtpToken
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->andWhere('o.type = :type')
            ->andWhere('o.usedAt IS NULL')
            ->andWhere('o.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function invalidateAllForUser(User $user, string $type): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.usedAt', ':now')
            ->where('o.user = :user')
            ->andWhere('o.type = :type')
            ->andWhere('o.usedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}

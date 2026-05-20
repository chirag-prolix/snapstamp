<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function findByReferralCode(string $code): ?Customer
    {
        return $this->findOneBy(['referralCode' => $code]);
    }

    public function findByReferrer(Customer $referrer): array
    {
        return $this->findBy(['referredBy' => $referrer], ['createdAt' => 'DESC']);
    }

    public function findActiveCustomers(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.deletedAt IS NULL')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}

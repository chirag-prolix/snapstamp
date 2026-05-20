<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findUnreadByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.customer = :customer')
            ->andWhere('n.isRead = false')
            ->setParameter('customer', $customer)
            ->orderBy('n.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCustomer(Customer $customer, int $limit = 30): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('n.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnread(Customer $customer): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.customer = :customer')
            ->andWhere('n.isRead = false')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

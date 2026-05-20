<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Enum\PaymentStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->findOneBy(['transactionId' => $transactionId]);
    }

    public function findByGatewayId(string $gatewayId): ?Payment
    {
        return $this->findOneBy(['paymentGatewayId' => $gatewayId]);
    }

    public function findFailedRetryable(int $maxRetries = 3): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.retryCount < :maxRetries')
            ->setParameter('status', PaymentStatusEnum::FAILED)
            ->setParameter('maxRetries', $maxRetries)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

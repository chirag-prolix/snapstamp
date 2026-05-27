<?php

namespace App\Factory;

use App\Entity\Transaction;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Transaction>
 *
 * Pass `transactionType`, `customer`, `merchant`, `amount`, `stamps` as overrides.
 */
final class TransactionFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Transaction::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'transactionType' => self::faker()->randomElement(TransactionTypeEnum::cases()),
            'status'          => TransactionStatusEnum::COMPLETED,
            'description'     => self::faker()->sentence(),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}

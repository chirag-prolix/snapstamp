<?php

namespace App\Factory;

use App\Entity\RewardRedemption;
use App\Enum\RewardRedemptionStatusEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<RewardRedemption>
 *
 * Requires `reward`, `customer` as overrides.
 * Pass `stampCard` and `approvedByMerchant` when relevant.
 */
final class RewardRedemptionFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return RewardRedemption::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'status' => RewardRedemptionStatusEnum::PENDING,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}

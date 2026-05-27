<?php

namespace App\Factory;

use App\Entity\Reward;
use App\Enum\RewardStatusEnum;
use App\Enum\RewardTypeEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Reward>
 *
 * Requires `merchant` to be passed as an override.
 */
final class RewardFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Reward::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title'            => ucwords(self::faker()->words(3, true)),
            'description'      => self::faker()->sentence(),
            'rewardType'       => self::faker()->randomElement(RewardTypeEnum::cases()),
            'value'            => (string) self::faker()->randomFloat(2, 50, 1000),
            'stampRequirement' => 10,
            'maxRedemptions'   => self::faker()->optional(0.5)->numberBetween(50, 500),
            'status'           => RewardStatusEnum::ACTIVE,
            'expiresAt'        => new \DateTimeImmutable('+6 months'),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}

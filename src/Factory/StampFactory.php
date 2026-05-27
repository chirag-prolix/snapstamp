<?php

namespace App\Factory;

use App\Entity\Stamp;
use App\Enum\StampStatusEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Stamp>
 *
 * Requires `stampCard`, `customer`, `merchant`, and `stampSequence` as overrides.
 */
final class StampFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Stamp::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'stampSequence' => 1,
            'isBonus'       => false,
            'collectedAt'   => new \DateTimeImmutable(),
            'status'        => StampStatusEnum::ACTIVE,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}

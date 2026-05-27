<?php

namespace App\Factory;

use App\Entity\StampCard;
use App\Enum\StampCardStatusEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<StampCard>
 *
 * Requires `merchant` and `customer` to be passed as overrides.
 */
final class StampCardFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return StampCard::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'cardNumber'         => 'SC-' . self::faker()->unique()->numerify('######'),
            'totalSlotsRequired' => 10,
            'displayName'        => ucwords(self::faker()->words(2, true)) . ' Card',
            'status'             => StampCardStatusEnum::ACTIVE,
            'isDigital'          => true,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}

<?php

namespace App\Factory;

use App\Entity\MerchantCategory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<MerchantCategory>
 */
final class MerchantCategoryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return MerchantCategory::class;
    }

    protected function defaults(): array|callable
    {
        $name = ucwords(self::faker()->unique()->words(2, true));

        return [
            'name'        => $name,
            'description' => self::faker()->sentence(),
            'icon'        => self::faker()->word(),
            'color'       => self::faker()->hexColor(),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}

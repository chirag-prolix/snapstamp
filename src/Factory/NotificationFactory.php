<?php

namespace App\Factory;

use App\Entity\Notification;
use App\Enum\NotificationTypeEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Notification>
 *
 * Requires `customer` as override. Pass `type`, `title`, `message`, `merchant` as needed.
 * Use markAsRead() after createOne() for read notifications — no setIsRead() setter exists.
 */
final class NotificationFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Notification::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'type'     => self::faker()->randomElement(NotificationTypeEnum::cases()),
            'title'    => self::faker()->sentence(4),
            'message'  => self::faker()->sentence(),
            'channels' => ['in-app'],
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}

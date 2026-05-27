<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Enum\NotificationTypeEnum;
use App\Factory\NotificationFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds 11 in-app notifications across 3 types, mirroring stamp and reward activity.
 *
 *  Type              | Count | Read | Notes
 *  ----------------- | ----- | ---- | ------------------------------------
 *  STAMP_RECEIVED    |   6   |  3   | One per customer, most recent merchant
 *  REWARD_AVAILABLE  |   3   |  2   | One per completed stamp card
 *  REWARD_REDEEMED   |   2   |  2   | Completed redemptions only
 *
 * Run with:  bin/console doctrine:fixtures:load --group=notification
 */
class NotificationFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    // [customerIndex, merchantIndex, read]
    private const STAMP_NOTIFICATIONS = [
        ['customerIndex' => 1, 'merchantIndex' => 1, 'merchantName' => 'Snap Coffee House',    'read' => true],
        ['customerIndex' => 2, 'merchantIndex' => 5, 'merchantName' => 'TechZone Electronics', 'read' => true],
        ['customerIndex' => 3, 'merchantIndex' => 2, 'merchantName' => 'Spice Garden',         'read' => true],
        ['customerIndex' => 4, 'merchantIndex' => 4, 'merchantName' => 'Fresh Bakes Bakery',   'read' => false],
        ['customerIndex' => 5, 'merchantIndex' => 5, 'merchantName' => 'TechZone Electronics', 'read' => false],
        ['customerIndex' => 6, 'merchantIndex' => 2, 'merchantName' => 'Spice Garden',         'read' => false],
    ];

    // [customerIndex, merchantIndex, rewardTitle, read]
    private const REWARD_AVAILABLE_NOTIFICATIONS = [
        ['customerIndex' => 1, 'merchantIndex' => 1, 'rewardTitle' => 'Free Coffee',         'read' => true],
        ['customerIndex' => 3, 'merchantIndex' => 2, 'rewardTitle' => '20% Off Your Bill',   'read' => true],
        ['customerIndex' => 5, 'merchantIndex' => 6, 'rewardTitle' => 'Free 30-min Massage', 'read' => false],
    ];

    // [customerIndex, merchantIndex, rewardTitle]  — all read
    private const REWARD_REDEEMED_NOTIFICATIONS = [
        ['customerIndex' => 1, 'merchantIndex' => 1, 'rewardTitle' => 'Free Coffee'],
        ['customerIndex' => 3, 'merchantIndex' => 2, 'rewardTitle' => '20% Off Your Bill'],
    ];

    public static function getGroups(): array
    {
        return ['notification'];
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::STAMP_NOTIFICATIONS as $data) {
            /** @var Customer $customer */
            $customer = $this->getReference(
                UserFixtures::CUSTOMER_REFERENCE . '.' . $data['customerIndex'],
                Customer::class
            );

            /** @var Merchant $merchant */
            $merchant = $this->getReference(
                UserFixtures::MERCHANT_REFERENCE . '.' . $data['merchantIndex'],
                Merchant::class
            );

            $notification = NotificationFactory::createOne([
                'customer' => $customer,
                'merchant' => $merchant,
                'type'     => NotificationTypeEnum::STAMP_RECEIVED,
                'title'    => 'Stamp collected!',
                'message'  => 'You just earned a stamp at ' . $data['merchantName'] . '. Keep collecting to unlock your reward.',
            ]);

            if ($data['read']) {
                $notification->markAsRead();
                $manager->persist($notification);
            }
        }

        foreach (self::REWARD_AVAILABLE_NOTIFICATIONS as $data) {
            /** @var Customer $customer */
            $customer = $this->getReference(
                UserFixtures::CUSTOMER_REFERENCE . '.' . $data['customerIndex'],
                Customer::class
            );

            /** @var Merchant $merchant */
            $merchant = $this->getReference(
                UserFixtures::MERCHANT_REFERENCE . '.' . $data['merchantIndex'],
                Merchant::class
            );

            $notification = NotificationFactory::createOne([
                'customer' => $customer,
                'merchant' => $merchant,
                'type'     => NotificationTypeEnum::REWARD_AVAILABLE,
                'title'    => 'Your reward is ready!',
                'message'  => 'Congratulations! You\'ve earned "' . $data['rewardTitle'] . '". Show this to the merchant to redeem.',
            ]);

            if ($data['read']) {
                $notification->markAsRead();
                $manager->persist($notification);
            }
        }

        foreach (self::REWARD_REDEEMED_NOTIFICATIONS as $data) {
            /** @var Customer $customer */
            $customer = $this->getReference(
                UserFixtures::CUSTOMER_REFERENCE . '.' . $data['customerIndex'],
                Customer::class
            );

            /** @var Merchant $merchant */
            $merchant = $this->getReference(
                UserFixtures::MERCHANT_REFERENCE . '.' . $data['merchantIndex'],
                Merchant::class
            );

            $notification = NotificationFactory::createOne([
                'customer' => $customer,
                'merchant' => $merchant,
                'type'     => NotificationTypeEnum::REWARD_REDEEMED,
                'title'    => 'Reward redeemed successfully!',
                'message'  => 'Your reward "' . $data['rewardTitle'] . '" has been redeemed. Thank you for being a loyal customer!',
            ]);

            $notification->markAsRead();
            $manager->persist($notification);
        }

        $manager->flush();
    }
}

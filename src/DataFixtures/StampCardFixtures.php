<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Factory\StampCardFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds 15 stamp cards across 6 customers and 6 merchants.
 *
 *  Ref                    | Customer        | Merchant               | Slots
 *  ---------------------- | --------------- | ---------------------- | -----
 *  fixture.stamp_card.1   | Amit Kumar      | Snap Coffee House      | 10
 *  fixture.stamp_card.2   | Amit Kumar      | Spice Garden           | 10
 *  fixture.stamp_card.3   | Amit Kumar      | The Book Nook          |  8
 *  fixture.stamp_card.4   | Divya Singh     | Snap Coffee House      | 10
 *  fixture.stamp_card.5   | Divya Singh     | Fresh Bakes Bakery     | 10
 *  fixture.stamp_card.6   | Divya Singh     | TechZone Electronics   | 10
 *  fixture.stamp_card.7   | Rohit Verma     | Spice Garden           | 10
 *  fixture.stamp_card.8   | Rohit Verma     | The Book Nook          |  8
 *  fixture.stamp_card.9   | Ananya Gupta    | Fresh Bakes Bakery     | 10
 *  fixture.stamp_card.10  | Ananya Gupta    | Wellness Studio        | 10
 *  fixture.stamp_card.11  | Karan Joshi     | TechZone Electronics   | 10
 *  fixture.stamp_card.12  | Karan Joshi     | Snap Coffee House      | 10
 *  fixture.stamp_card.13  | Karan Joshi     | Wellness Studio        | 10
 *  fixture.stamp_card.14  | Pooja Desai     | Spice Garden           | 10
 *  fixture.stamp_card.15  | Pooja Desai     | The Book Nook          |  8
 *
 * Run with:  bin/console doctrine:fixtures:load --group=stamp_card
 */
class StampCardFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const REF_PREFIX = 'fixture.stamp_card.';

    // [customerIndex, merchantIndex, cardNumber, totalSlots, displayName]
    private const STAMP_CARDS = [
        [1, 1, 'SC-000001', 10, 'Snap Coffee Loyalty'],
        [1, 2, 'SC-000002', 10, 'Spice Garden Rewards'],
        [1, 3, 'SC-000003',  8, 'Book Nook Reader Card'],
        [2, 1, 'SC-000004', 10, 'Snap Coffee Loyalty'],
        [2, 4, 'SC-000005', 10, 'Fresh Bakes Card'],
        [2, 5, 'SC-000006', 10, 'TechZone Points'],
        [3, 2, 'SC-000007', 10, 'Spice Garden Rewards'],
        [3, 3, 'SC-000008',  8, 'Book Nook Reader Card'],
        [4, 4, 'SC-000009', 10, 'Fresh Bakes Card'],
        [4, 6, 'SC-000010', 10, 'Wellness Loyalty'],
        [5, 5, 'SC-000011', 10, 'TechZone Points'],
        [5, 1, 'SC-000012', 10, 'Snap Coffee Loyalty'],
        [5, 6, 'SC-000013', 10, 'Wellness Loyalty'],
        [6, 2, 'SC-000014', 10, 'Spice Garden Rewards'],
        [6, 3, 'SC-000015',  8, 'Book Nook Reader Card'],
    ];

    public static function getGroups(): array
    {
        return ['stamp_card'];
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::STAMP_CARDS as $index => $data) {
            [$customerIdx, $merchantIdx, $cardNumber, $totalSlots, $displayName] = $data;

            $customer = $this->getReference(UserFixtures::CUSTOMER_REFERENCE . '.' . $customerIdx, Customer::class);
            $merchant = $this->getReference(UserFixtures::MERCHANT_REFERENCE . '.' . $merchantIdx, Merchant::class);

            $card = StampCardFactory::createOne([
                'merchant'           => $merchant,
                'customer'           => $customer,
                'cardNumber'         => $cardNumber,
                'totalSlotsRequired' => $totalSlots,
                'displayName'        => $displayName,
            ]);

            $this->addReference(self::REF_PREFIX . ($index + 1), $card);
        }
    }
}

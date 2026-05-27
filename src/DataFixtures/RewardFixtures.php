<?php

namespace App\DataFixtures;

use App\Entity\Merchant;
use App\Enum\RewardStatusEnum;
use App\Enum\RewardTypeEnum;
use App\Factory\RewardFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds one ACTIVE reward per merchant (6 total), each matching the merchant's
 * stamp card slot requirement.
 *
 *  Ref                  | Merchant              | Type        | Value   | Stamps
 *  -------------------- | --------------------- | ----------- | ------- | ------
 *  fixture.reward.1     | Snap Coffee House     | FREE_ITEM   | ₹150    | 10
 *  fixture.reward.2     | Spice Garden          | DISCOUNT    |  20%    | 10
 *  fixture.reward.3     | The Book Nook         | CASHBACK    | ₹100    |  8
 *  fixture.reward.4     | Fresh Bakes Bakery    | FREE_ITEM   | ₹250    | 10
 *  fixture.reward.5     | TechZone Electronics  | CASHBACK    | ₹500    | 10
 *  fixture.reward.6     | Wellness Studio       | EXPERIENCE  | ₹800    | 10
 *
 * Run with:  bin/console doctrine:fixtures:load --group=reward
 */
class RewardFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const REF_PREFIX = 'fixture.reward.';

    // [merchantIndex, title, description, type, value, stampRequirement, terms]
    private const REWARDS = [
        [
            'merchantIndex'    => 1,
            'title'            => 'Free Coffee',
            'description'      => 'Enjoy a free coffee of your choice — any size, any flavour.',
            'rewardType'       => RewardTypeEnum::FREE_ITEM,
            'value'            => '150.00',
            'stampRequirement' => 10,
            'terms'            => 'Valid on dine-in only. Not transferable. One per customer per visit.',
        ],
        [
            'merchantIndex'    => 2,
            'title'            => '20% Off Your Bill',
            'description'      => 'Get 20% discount on your total food bill at Spice Garden.',
            'rewardType'       => RewardTypeEnum::DISCOUNT,
            'value'            => '20.00',
            'stampRequirement' => 10,
            'terms'            => 'Valid on orders above ₹500. Cannot be combined with other offers.',
        ],
        [
            'merchantIndex'    => 3,
            'title'            => '₹100 Off Any Book',
            'description'      => '₹100 instant cashback on any single book purchase.',
            'rewardType'       => RewardTypeEnum::CASHBACK,
            'value'            => '100.00',
            'stampRequirement' => 8,
            'terms'            => 'Valid on books priced ₹300 or above. One redemption per card.',
        ],
        [
            'merchantIndex'    => 4,
            'title'            => 'Free Pastry Box',
            'description'      => 'Complimentary box of 6 assorted freshly baked pastries.',
            'rewardType'       => RewardTypeEnum::FREE_ITEM,
            'value'            => '250.00',
            'stampRequirement' => 10,
            'terms'            => 'Subject to availability. Must be redeemed same day as issuance.',
        ],
        [
            'merchantIndex'    => 5,
            'title'            => '₹500 Cashback',
            'description'      => '₹500 cashback credited on any electronics purchase above ₹2,000.',
            'rewardType'       => RewardTypeEnum::CASHBACK,
            'value'            => '500.00',
            'stampRequirement' => 10,
            'terms'            => 'Cashback processed within 3 business days. Valid on in-store purchases only.',
        ],
        [
            'merchantIndex'    => 6,
            'title'            => 'Free 30-min Massage',
            'description'      => 'Complimentary 30-minute relaxation massage session at Wellness Studio.',
            'rewardType'       => RewardTypeEnum::EXPERIENCE,
            'value'            => '800.00',
            'stampRequirement' => 10,
            'terms'            => 'Appointment required. Valid Monday–Friday only. Non-transferable.',
        ],
    ];

    public static function getGroups(): array
    {
        return ['reward'];
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::REWARDS as $index => $data) {
            $merchant = $this->getReference(
                UserFixtures::MERCHANT_REFERENCE . '.' . $data['merchantIndex'],
                Merchant::class
            );

            $reward = RewardFactory::createOne([
                'merchant'         => $merchant,
                'title'            => $data['title'],
                'description'      => $data['description'],
                'rewardType'       => $data['rewardType'],
                'value'            => $data['value'],
                'stampRequirement' => $data['stampRequirement'],
                'status'           => RewardStatusEnum::ACTIVE,
                'terms'            => $data['terms'],
                'expiresAt'        => new \DateTimeImmutable('+6 months'),
            ]);

            $this->addReference(self::REF_PREFIX . ($index + 1), $reward);
        }
    }
}

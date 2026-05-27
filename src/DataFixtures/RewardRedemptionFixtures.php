<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Entity\Reward;
use App\Entity\StampCard;
use App\Enum\RewardRedemptionStatusEnum;
use App\Factory\RewardRedemptionFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds 3 redemptions — one per completed stamp card.
 *
 *  Card  | Customer      | Merchant           | Reward                | Status
 *  ----- | ------------- | ------------------ | --------------------- | ---------
 *  1     | Amit Kumar    | Snap Coffee House  | Free Coffee           | COMPLETED
 *  7     | Rohit Verma   | Spice Garden       | 20% Off Your Bill     | COMPLETED
 *  13    | Karan Joshi   | Wellness Studio    | Free 30-min Massage   | PENDING
 *
 * Completed redemptions also call approve() and incrementCurrentRedemptions()
 * to keep Reward and RewardRedemption state consistent.
 *
 * Run with:  bin/console doctrine:fixtures:load --group=reward_redemption
 */
class RewardRedemptionFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    // [cardIndex, customerIndex, merchantIndex, rewardIndex, status]
    private const REDEMPTIONS = [
        [
            'cardIndex'     => 1,
            'customerIndex' => 1,
            'merchantIndex' => 1,
            'rewardIndex'   => 1,
            'status'        => RewardRedemptionStatusEnum::COMPLETED,
        ],
        [
            'cardIndex'     => 7,
            'customerIndex' => 3,
            'merchantIndex' => 2,
            'rewardIndex'   => 2,
            'status'        => RewardRedemptionStatusEnum::COMPLETED,
        ],
        [
            'cardIndex'     => 13,
            'customerIndex' => 5,
            'merchantIndex' => 6,
            'rewardIndex'   => 6,
            'status'        => RewardRedemptionStatusEnum::PENDING,
        ],
    ];

    public static function getGroups(): array
    {
        return ['reward_redemption'];
    }

    public function getDependencies(): array
    {
        return [RewardFixtures::class, StampFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::REDEMPTIONS as $data) {
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

            /** @var Reward $reward */
            $reward = $this->getReference(
                RewardFixtures::REF_PREFIX . $data['rewardIndex'],
                Reward::class
            );

            /** @var StampCard $stampCard */
            $stampCard = $this->getReference(
                StampCardFixtures::REF_PREFIX . $data['cardIndex'],
                StampCard::class
            );

            $redemption = RewardRedemptionFactory::createOne([
                'reward'    => $reward,
                'customer'  => $customer,
                'stampCard' => $stampCard,
                'status'    => $data['status'],
            ]);

            if ($data['status'] === RewardRedemptionStatusEnum::COMPLETED) {
                $redemption->approve($merchant);
                $reward->incrementCurrentRedemptions();
                $customer->incrementTotalRewardsRedeemed();

                $manager->persist($redemption);
                $manager->persist($reward);
                $manager->persist($customer);
            }
        }

        $manager->flush();
    }
}

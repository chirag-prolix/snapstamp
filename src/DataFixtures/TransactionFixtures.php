<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Merchant;
use App\Enum\TransactionTypeEnum;
use App\Factory\TransactionFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds 23 transactions mirroring stamp, reward-redemption, and payment events.
 *
 *  Type               | Count | Notes
 *  ------------------ | ----- | -----------------------------------------
 *  STAMP_ISSUED       |  15   | One per stamp card; stamps = card count
 *  REWARD_REDEEMED    |   3   | One per completed redemption (2 COMPLETED + 1 PENDING)
 *  PAYMENT_RECEIVED   |   5   | Completed subscription payments only
 *
 * Run with:  bin/console doctrine:fixtures:load --group=transaction
 */
class TransactionFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    // [customerIndex, merchantIndex, stamps, merchantName]
    private const STAMP_TRANSACTIONS = [
        ['customerIndex' => 1, 'merchantIndex' => 1, 'stamps' => 10, 'merchantName' => 'Snap Coffee House'],
        ['customerIndex' => 1, 'merchantIndex' => 2, 'stamps' => 7,  'merchantName' => 'Spice Garden'],
        ['customerIndex' => 1, 'merchantIndex' => 3, 'stamps' => 3,  'merchantName' => 'The Book Nook'],
        ['customerIndex' => 2, 'merchantIndex' => 1, 'stamps' => 5,  'merchantName' => 'Snap Coffee House'],
        ['customerIndex' => 2, 'merchantIndex' => 4, 'stamps' => 2,  'merchantName' => 'Fresh Bakes Bakery'],
        ['customerIndex' => 2, 'merchantIndex' => 5, 'stamps' => 8,  'merchantName' => 'TechZone Electronics'],
        ['customerIndex' => 3, 'merchantIndex' => 2, 'stamps' => 10, 'merchantName' => 'Spice Garden'],
        ['customerIndex' => 3, 'merchantIndex' => 3, 'stamps' => 6,  'merchantName' => 'The Book Nook'],
        ['customerIndex' => 4, 'merchantIndex' => 4, 'stamps' => 4,  'merchantName' => 'Fresh Bakes Bakery'],
        ['customerIndex' => 4, 'merchantIndex' => 6, 'stamps' => 1,  'merchantName' => 'Wellness Studio'],
        ['customerIndex' => 5, 'merchantIndex' => 5, 'stamps' => 9,  'merchantName' => 'TechZone Electronics'],
        ['customerIndex' => 5, 'merchantIndex' => 1, 'stamps' => 3,  'merchantName' => 'Snap Coffee House'],
        ['customerIndex' => 5, 'merchantIndex' => 6, 'stamps' => 10, 'merchantName' => 'Wellness Studio'],
        ['customerIndex' => 6, 'merchantIndex' => 2, 'stamps' => 7,  'merchantName' => 'Spice Garden'],
        ['customerIndex' => 6, 'merchantIndex' => 3, 'stamps' => 2,  'merchantName' => 'The Book Nook'],
    ];

    // [customerIndex, merchantIndex, rewardTitle, amount]
    private const REWARD_TRANSACTIONS = [
        ['customerIndex' => 1, 'merchantIndex' => 1, 'rewardTitle' => 'Free Coffee',          'amount' => '150.00'],
        ['customerIndex' => 3, 'merchantIndex' => 2, 'rewardTitle' => '20% Off Your Bill',    'amount' => '20.00'],
        ['customerIndex' => 5, 'merchantIndex' => 6, 'rewardTitle' => 'Free 30-min Massage',  'amount' => '800.00'],
    ];

    // [merchantIndex, plan, amount, referenceId]
    private const PAYMENT_TRANSACTIONS = [
        ['merchantIndex' => 1, 'plan' => 'monthly', 'amount' => '999.00',  'referenceId' => 'TXN-20260226-00001'],
        ['merchantIndex' => 2, 'plan' => 'annual',  'amount' => '9999.00', 'referenceId' => 'TXN-20251126-00002'],
        ['merchantIndex' => 3, 'plan' => 'monthly', 'amount' => '999.00',  'referenceId' => 'TXN-20260426-00003'],
        ['merchantIndex' => 4, 'plan' => 'monthly', 'amount' => '999.00',  'referenceId' => 'TXN-20260326-00004'],
        ['merchantIndex' => 5, 'plan' => 'monthly', 'amount' => '999.00',  'referenceId' => 'TXN-20260517-00006'],
    ];

    public static function getGroups(): array
    {
        return ['transaction'];
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::STAMP_TRANSACTIONS as $data) {
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

            TransactionFactory::createOne([
                'transactionType' => TransactionTypeEnum::STAMP_ISSUED,
                'customer'        => $customer,
                'merchant'        => $merchant,
                'stamps'          => $data['stamps'],
                'description'     => $data['stamps'] . ' stamps issued at ' . $data['merchantName'],
            ]);
        }

        foreach (self::REWARD_TRANSACTIONS as $data) {
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

            TransactionFactory::createOne([
                'transactionType' => TransactionTypeEnum::REWARD_REDEEMED,
                'customer'        => $customer,
                'merchant'        => $merchant,
                'amount'          => $data['amount'],
                'description'     => 'Reward redeemed: ' . $data['rewardTitle'],
            ]);
        }

        foreach (self::PAYMENT_TRANSACTIONS as $data) {
            /** @var Merchant $merchant */
            $merchant = $this->getReference(
                UserFixtures::MERCHANT_REFERENCE . '.' . $data['merchantIndex'],
                Merchant::class
            );

            TransactionFactory::createOne([
                'transactionType' => TransactionTypeEnum::PAYMENT_RECEIVED,
                'merchant'        => $merchant,
                'amount'          => $data['amount'],
                'referenceId'     => $data['referenceId'],
                'description'     => 'Subscription payment received: ' . $data['plan'] . ' plan',
            ]);
        }
    }
}

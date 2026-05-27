<?php

namespace App\DataFixtures;

use App\Entity\Merchant;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Factory\PaymentFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds 7 Razorpay subscription payments across 6 merchants.
 *
 *  #  | Merchant              | Plan    | Amount   | Status    | Method
 *  -- | --------------------- | ------- | -------- | --------- | ------------
 *  1  | Snap Coffee House     | monthly | ₹999     | COMPLETED | UPI
 *  2  | Spice Garden          | annual  | ₹9,999   | COMPLETED | CARD
 *  3  | The Book Nook         | monthly | ₹999     | COMPLETED | BANK_TRANSFER
 *  4  | Fresh Bakes Bakery    | monthly | ₹999     | COMPLETED | CARD
 *  5  | TechZone Electronics  | monthly | ₹999     | FAILED    | —
 *  6  | TechZone Electronics  | monthly | ₹999     | COMPLETED | UPI (retry)
 *  7  | Wellness Studio       | annual  | ₹9,999   | INITIATED | —
 *
 * COMPLETED payments also call markWebhookReceived() and setReceiptUrl().
 * FAILED payments call setFailureReason() and incrementRetryCount().
 *
 * Run with:  bin/console doctrine:fixtures:load --group=payment
 */
class PaymentFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private const PAYMENTS = [
        [
            'merchantIndex'    => 1,
            'plan'             => 'monthly',
            'amount'           => '999.00',
            'status'           => PaymentStatusEnum::COMPLETED,
            'paymentMethod'    => PaymentMethodEnum::UPI,
            'transactionId'    => 'TXN-20260226-00001',
            'paymentGatewayId' => 'pay_QkJmN8A3vX1pLr2T',
            'receiptUrl'       => 'https://dashboard.razorpay.com/app/payments/pay_QkJmN8A3vX1pLr2T',
            'ipAddress'        => '103.21.58.112',
        ],
        [
            'merchantIndex'    => 2,
            'plan'             => 'annual',
            'amount'           => '9999.00',
            'status'           => PaymentStatusEnum::COMPLETED,
            'paymentMethod'    => PaymentMethodEnum::CARD,
            'transactionId'    => 'TXN-20251126-00002',
            'paymentGatewayId' => 'pay_PmWsD5rF2yK9nQ4V',
            'receiptUrl'       => 'https://dashboard.razorpay.com/app/payments/pay_PmWsD5rF2yK9nQ4V',
            'ipAddress'        => '49.36.72.195',
        ],
        [
            'merchantIndex'    => 3,
            'plan'             => 'monthly',
            'amount'           => '999.00',
            'status'           => PaymentStatusEnum::COMPLETED,
            'paymentMethod'    => PaymentMethodEnum::BANK_TRANSFER,
            'transactionId'    => 'TXN-20260426-00003',
            'paymentGatewayId' => 'pay_RnXtE6sG3zL0oR5W',
            'receiptUrl'       => 'https://dashboard.razorpay.com/app/payments/pay_RnXtE6sG3zL0oR5W',
            'ipAddress'        => '122.176.34.78',
        ],
        [
            'merchantIndex'    => 4,
            'plan'             => 'monthly',
            'amount'           => '999.00',
            'status'           => PaymentStatusEnum::COMPLETED,
            'paymentMethod'    => PaymentMethodEnum::CARD,
            'transactionId'    => 'TXN-20260326-00004',
            'paymentGatewayId' => 'pay_SoYuF7tH4aM1pS6X',
            'receiptUrl'       => 'https://dashboard.razorpay.com/app/payments/pay_SoYuF7tH4aM1pS6X',
            'ipAddress'        => '115.240.89.23',
        ],
        [
            'merchantIndex'    => 5,
            'plan'             => 'monthly',
            'amount'           => '999.00',
            'status'           => PaymentStatusEnum::FAILED,
            'paymentMethod'    => null,
            'transactionId'    => 'TXN-20260516-00005',
            'paymentGatewayId' => 'order_TpZvG8uI5bN2qT7Y',
            'receiptUrl'       => null,
            'ipAddress'        => '59.88.112.54',
            'failureReason'    => 'Payment declined by bank. Please try a different payment method or contact your bank.',
        ],
        [
            'merchantIndex'    => 5,
            'plan'             => 'monthly',
            'amount'           => '999.00',
            'status'           => PaymentStatusEnum::COMPLETED,
            'paymentMethod'    => PaymentMethodEnum::UPI,
            'transactionId'    => 'TXN-20260517-00006',
            'paymentGatewayId' => 'pay_UqAwH9vJ6cO3rU8Z',
            'receiptUrl'       => 'https://dashboard.razorpay.com/app/payments/pay_UqAwH9vJ6cO3rU8Z',
            'ipAddress'        => '59.88.112.54',
        ],
        [
            'merchantIndex'    => 6,
            'plan'             => 'annual',
            'amount'           => '9999.00',
            'status'           => PaymentStatusEnum::INITIATED,
            'paymentMethod'    => null,
            'transactionId'    => 'TXN-20260526-00007',
            'paymentGatewayId' => 'order_VrBxI0wK7dP4sV9A',
            'receiptUrl'       => null,
            'ipAddress'        => null,
        ],
    ];

    public static function getGroups(): array
    {
        return ['payment'];
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::PAYMENTS as $data) {
            /** @var Merchant $merchant */
            $merchant = $this->getReference(
                UserFixtures::MERCHANT_REFERENCE . '.' . $data['merchantIndex'],
                Merchant::class
            );

            $payment = PaymentFactory::createOne([
                'merchant'         => $merchant,
                'transactionId'    => $data['transactionId'],
                'paymentGatewayId' => $data['paymentGatewayId'],
                'status'           => $data['status'],
                'amount'           => $data['amount'],
                'paymentMethod'    => $data['paymentMethod'],
                'ipAddress'        => $data['ipAddress'],
                'metadata'         => ['plan' => $data['plan']],
            ]);

            if ($data['status'] === PaymentStatusEnum::COMPLETED) {
                $payment->markWebhookReceived();
                $payment->setReceiptUrl($data['receiptUrl']);
                $manager->persist($payment);
            }

            if ($data['status'] === PaymentStatusEnum::FAILED) {
                $payment->setFailureReason($data['failureReason']);
                $payment->incrementRetryCount();
                $manager->persist($payment);
            }
        }

        $manager->flush();
    }
}

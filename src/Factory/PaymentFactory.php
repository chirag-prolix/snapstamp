<?php

namespace App\Factory;

use App\Entity\Payment;
use App\Enum\PaymentGatewayEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\PaymentTypeEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Payment>
 *
 * Requires `merchant` to be passed as an override.
 * Pass `transactionId`, `paymentGatewayId`, `status`, `amount`, `paymentMethod` for deterministic fixture data.
 */
final class PaymentFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Payment::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'transactionId'    => 'TXN-' . self::faker()->unique()->numerify('########'),
            'paymentGateway'   => PaymentGatewayEnum::RAZORPAY,
            'paymentGatewayId' => 'order_' . self::faker()->unique()->bothify('??????????##'),
            'paymentType'      => PaymentTypeEnum::DEPOSIT,
            'status'           => PaymentStatusEnum::COMPLETED,
            'amount'           => '999.00',
            'paymentMethod'    => self::faker()->randomElement(PaymentMethodEnum::cases()),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}

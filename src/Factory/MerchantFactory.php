<?php

namespace App\Factory;

use App\Entity\Merchant;
use App\Enum\MerchantStatusEnum;
use App\Enum\UserStatusEnum;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Merchant>
 *
 * Default password for all generated merchants: "password"
 */
final class MerchantFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Merchant::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'email'                  => self::faker()->unique()->safeEmail(),
            'phone'                  => '+91' . self::faker()->numerify('##########'),
            'firstName'              => self::faker()->firstName(),
            'lastName'               => self::faker()->lastName(),
            'status'                 => UserStatusEnum::ACTIVE,
            'isEmailVerified'        => true,
            'isPhoneVerified'        => true,
            'businessName'           => self::faker()->company(),
            'businessDescription'    => self::faker()->sentence(),
            'city'                   => self::faker()->city(),
            'state'                  => self::faker()->state(),
            'country'                => 'IN',
            'address'                => self::faker()->address(),
            'postalCode'             => self::faker()->postcode(),
            'phoneForBusiness'       => '+91' . self::faker()->numerify('##########'),
            'taxId'                  => strtoupper(self::faker()->unique()->bothify('??##??####?')),
            'bankAccountNumber'      => self::faker()->numerify('####################'),
            'bankIfscCode'           => self::faker()->regexify('[A-Z]{4}0[A-Z0-9]{6}'),
            'bankAccountHolderName'  => self::faker()->name(),
            'apiKey'                 => self::faker()->unique()->uuid(),
            'apiSecret'              => bin2hex(random_bytes(32)),
            'isVerified'             => true,
            'onboardingStatus'       => MerchantStatusEnum::ACTIVE,
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Merchant $merchant): void {
            $merchant->setPassword(
                $this->passwordHasher->hashPassword($merchant, 'password')
            );
        });
    }
}

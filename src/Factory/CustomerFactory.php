<?php

namespace App\Factory;

use App\Entity\Customer;
use App\Enum\GenderEnum;
use App\Enum\UserStatusEnum;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Customer>
 *
 * Default password for all generated customers: "password"
 */
final class CustomerFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return Customer::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'email'            => self::faker()->unique()->safeEmail(),
            'phone'            => '+91' . self::faker()->numerify('##########'),
            'firstName'        => self::faker()->firstName(),
            'lastName'         => self::faker()->lastName(),
            'status'           => UserStatusEnum::ACTIVE,
            'isEmailVerified'  => true,
            'isPhoneVerified'  => true,
            'displayName'      => self::faker()->name(),
            'city'             => self::faker()->city(),
            'state'            => self::faker()->state(),
            'country'          => 'IN',
            'gender'           => self::faker()->randomElement(GenderEnum::cases()),
            'preferredLanguage' => 'en',
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Customer $customer): void {
            $customer->setPassword(
                $this->passwordHasher->hashPassword($customer, 'password')
            );
        });
    }
}

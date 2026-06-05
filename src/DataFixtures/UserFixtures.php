<?php

namespace App\DataFixtures;

use App\Entity\MerchantCategory;
use App\Factory\CustomerFactory;
use App\Factory\MerchantFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Loads seed users (1 admin + 6 merchants + 6 customers):
 *
 *  Role          | Email                        | Password
 *  ------------- | ---------------------------- | --------
 *  ROLE_ADMIN    | admin@snapstamp.com          | password
 *  ROLE_MERCHANT | merchant@snapstamp.com       | password
 *  ROLE_MERCHANT | merchant2@snapstamp.com      | password
 *  ROLE_MERCHANT | merchant3@snapstamp.com      | password
 *  ROLE_MERCHANT | merchant4@snapstamp.com      | password
 *  ROLE_MERCHANT | merchant5@snapstamp.com      | password
 *  ROLE_MERCHANT | merchant6@snapstamp.com      | password
 *  ROLE_CUSTOMER | customer@snapstamp.com       | password
 *  ROLE_CUSTOMER | customer2@snapstamp.com      | password
 *  ROLE_CUSTOMER | customer3@snapstamp.com      | password
 *  ROLE_CUSTOMER | customer4@snapstamp.com      | password
 *  ROLE_CUSTOMER | customer5@snapstamp.com      | password
 *  ROLE_CUSTOMER | customer6@snapstamp.com      | password
 *
 * Run with:  bin/console doctrine:fixtures:load --group=user
 */
class UserFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const ADMIN_REFERENCE    = 'fixture.user.admin';
    public const MERCHANT_REFERENCE = 'fixture.user.merchant';
    public const CUSTOMER_REFERENCE = 'fixture.user.customer';

    private const MERCHANTS = [
        ['email' => 'merchant@snapstamp.com',  'phone' => '+919000000002', 'firstName' => 'Ravi',    'lastName' => 'Sharma',   'businessName' => 'Snap Coffee House',       'city' => 'Mumbai',    'state' => 'Maharashtra', 'address' => '12, MG Road, Bandra West',       'postalCode' => '400050', 'categorySlug' => 'food-beverage'],
        ['email' => 'merchant2@snapstamp.com', 'phone' => '+919000000004', 'firstName' => 'Priya',   'lastName' => 'Mehta',    'businessName' => 'Spice Garden Restaurant', 'city' => 'Delhi',     'state' => 'Delhi',       'address' => '45, Connaught Place',            'postalCode' => '110001', 'categorySlug' => 'food-beverage'],
        ['email' => 'merchant3@snapstamp.com', 'phone' => '+919000000005', 'firstName' => 'Arjun',   'lastName' => 'Patel',    'businessName' => 'The Book Nook',           'city' => 'Bengaluru', 'state' => 'Karnataka',   'address' => '8, Brigade Road',                'postalCode' => '560025', 'categorySlug' => 'books-stationery'],
        ['email' => 'merchant4@snapstamp.com', 'phone' => '+919000000006', 'firstName' => 'Sneha',   'lastName' => 'Reddy',    'businessName' => 'Fresh Bakes Bakery',      'city' => 'Hyderabad', 'state' => 'Telangana',   'address' => '22, Jubilee Hills Road No. 36',  'postalCode' => '500033', 'categorySlug' => 'bakery-desserts'],
        ['email' => 'merchant5@snapstamp.com', 'phone' => '+919000000007', 'firstName' => 'Vikram',  'lastName' => 'Nair',     'businessName' => 'TechZone Electronics',    'city' => 'Chennai',   'state' => 'Tamil Nadu',  'address' => '5, Anna Salai',                  'postalCode' => '600002', 'categorySlug' => 'electronics'],
        ['email' => 'merchant6@snapstamp.com', 'phone' => '+919000000008', 'firstName' => 'Meera',   'lastName' => 'Iyer',     'businessName' => 'Wellness Studio',         'city' => 'Pune',      'state' => 'Maharashtra', 'address' => '17, FC Road, Deccan Gymkhana',   'postalCode' => '411004', 'categorySlug' => 'health-wellness'],
    ];

    private const CUSTOMERS = [
        ['email' => 'customer@snapstamp.com',  'phone' => '+919000000003', 'firstName' => 'Amit',    'lastName' => 'Kumar',    'city' => 'Mumbai',    'state' => 'Maharashtra'],
        ['email' => 'customer2@snapstamp.com', 'phone' => '+919000000009', 'firstName' => 'Divya',   'lastName' => 'Singh',    'city' => 'Delhi',     'state' => 'Delhi'],
        ['email' => 'customer3@snapstamp.com', 'phone' => '+919000000010', 'firstName' => 'Rohit',   'lastName' => 'Verma',    'city' => 'Bengaluru', 'state' => 'Karnataka'],
        ['email' => 'customer4@snapstamp.com', 'phone' => '+919000000011', 'firstName' => 'Ananya',  'lastName' => 'Gupta',    'city' => 'Hyderabad', 'state' => 'Telangana'],
        ['email' => 'customer5@snapstamp.com', 'phone' => '+919000000012', 'firstName' => 'Karan',   'lastName' => 'Joshi',    'city' => 'Chennai',   'state' => 'Tamil Nadu'],
        ['email' => 'customer6@snapstamp.com', 'phone' => '+919000000013', 'firstName' => 'Pooja',   'lastName' => 'Desai',    'city' => 'Pune',      'state' => 'Maharashtra'],
    ];

    public static function getGroups(): array
    {
        return ['user'];
    }

    public function getDependencies(): array
    {
        return [MerchantCategoryFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $admin = CustomerFactory::createOne([
            'email'     => 'admin@snapstamp.com',
            'phone'     => '+919000000001',
            'firstName' => 'Admin',
            'lastName'  => 'User',
            'displayName' => 'Admin User',
            'roles'     => ['ROLE_ADMIN'],
        ]);

        $this->addReference(self::ADMIN_REFERENCE, $admin);

        foreach (self::MERCHANTS as $index => $data) {
            $category = $this->getReference(
                MerchantCategoryFixtures::REF_PREFIX . $data['categorySlug'],
                MerchantCategory::class
            );

            $merchant = MerchantFactory::createOne(
                array_merge(array_diff_key($data, ['categorySlug' => '']), ['category' => $category])
            );

            $this->addReference(self::MERCHANT_REFERENCE . '.' . ($index + 1), $merchant);
        }

        foreach (self::CUSTOMERS as $index => $data) {
            $customer = CustomerFactory::createOne([
                ...$data,
                'displayName' => $data['firstName'] . ' ' . $data['lastName'],
            ]);
            $this->addReference(self::CUSTOMER_REFERENCE . '.' . ($index + 1), $customer);
        }
    }
}

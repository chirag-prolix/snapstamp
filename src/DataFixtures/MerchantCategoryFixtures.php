<?php

namespace App\DataFixtures;

use App\Entity\MerchantCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds 10 merchant categories.
 * References are keyed as: fixture.merchant_category.<slug>
 *
 *  Slug                  | Name
 *  --------------------- | -------------------------
 *  food-beverage         | Food & Beverage
 *  bakery-desserts       | Bakery & Desserts
 *  books-stationery      | Books & Stationery
 *  electronics           | Electronics
 *  health-wellness       | Health & Wellness
 *  fashion-apparel       | Fashion & Apparel
 *  grocery-supermarket   | Grocery & Supermarket
 *  beauty-salon          | Beauty & Salon
 *  sports-fitness        | Sports & Fitness
 *  travel-hospitality    | Travel & Hospitality
 *
 * Run with:  bin/console doctrine:fixtures:load --group=merchant_category
 */
class MerchantCategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public const REF_PREFIX = 'fixture.merchant_category.';

    private const CATEGORIES = [
        [
            'name'        => 'Food & Beverage',
            'description' => 'Restaurants, cafés, bars, and all food outlets',
            'icon'        => 'utensils',
            'color'       => '#FF6B35',
        ],
        [
            'name'        => 'Bakery & Desserts',
            'description' => 'Bakeries, patisseries, ice cream and sweet shops',
            'icon'        => 'cake',
            'color'       => '#F7B731',
        ],
        [
            'name'        => 'Books & Stationery',
            'description' => 'Bookstores, libraries, and stationery shops',
            'icon'        => 'book-open',
            'color'       => '#4A90E2',
        ],
        [
            'name'        => 'Electronics',
            'description' => 'Electronics, gadgets, computers, and accessories',
            'icon'        => 'cpu',
            'color'       => '#5C6BC0',
        ],
        [
            'name'        => 'Health & Wellness',
            'description' => 'Gyms, spas, yoga studios, and wellness centres',
            'icon'        => 'heart',
            'color'       => '#26C6DA',
        ],
        [
            'name'        => 'Fashion & Apparel',
            'description' => 'Clothing, footwear, and fashion accessories',
            'icon'        => 'shirt',
            'color'       => '#EC407A',
        ],
        [
            'name'        => 'Grocery & Supermarket',
            'description' => 'Supermarkets, grocery stores, and daily essentials',
            'icon'        => 'shopping-basket',
            'color'       => '#66BB6A',
        ],
        [
            'name'        => 'Beauty & Salon',
            'description' => 'Hair salons, beauty parlours, and cosmetics',
            'icon'        => 'scissors',
            'color'       => '#AB47BC',
        ],
        [
            'name'        => 'Sports & Fitness',
            'description' => 'Sportswear, equipment, and fitness centres',
            'icon'        => 'dumbbell',
            'color'       => '#FF7043',
        ],
        [
            'name'        => 'Travel & Hospitality',
            'description' => 'Hotels, travel agencies, and hospitality services',
            'icon'        => 'plane',
            'color'       => '#26A69A',
        ],
    ];

    public static function getGroups(): array
    {
        return ['merchant_category', 'user'];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $data) {
            $category = new MerchantCategory();
            $category
                ->setName($data['name'])
                ->setDescription($data['description'])
                ->setIcon($data['icon'])
                ->setColor($data['color']);

            $manager->persist($category);

            $this->addReference(self::REF_PREFIX . $category->getSlug(), $category);
        }

        $manager->flush();
    }
}

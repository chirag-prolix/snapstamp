<?php

namespace App\DataFixtures;

use App\Entity\StampCard;
use App\Factory\StampFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds individual stamps for each stamp card, then drives card state
 * (currentStampCount, lastStampAt, completedAt, status) via incrementStampCount().
 *
 *  Card  | Customer     | Merchant              | Stamps | State
 *  ----- | ------------ | --------------------- | ------ | -----------
 *  1     | Amit Kumar   | Snap Coffee House     | 10/10  | COMPLETED
 *  2     | Amit Kumar   | Spice Garden          |  7/10  | active
 *  3     | Amit Kumar   | The Book Nook         |  3/8   | active
 *  4     | Divya Singh  | Snap Coffee House     |  5/10  | active
 *  5     | Divya Singh  | Fresh Bakes Bakery    |  2/10  | active
 *  6     | Divya Singh  | TechZone Electronics  |  8/10  | active
 *  7     | Rohit Verma  | Spice Garden          | 10/10  | COMPLETED
 *  8     | Rohit Verma  | The Book Nook         |  6/8   | active
 *  9     | Ananya Gupta | Fresh Bakes Bakery    |  4/10  | active
 *  10    | Ananya Gupta | Wellness Studio       |  1/10  | active
 *  11    | Karan Joshi  | TechZone Electronics  |  9/10  | active
 *  12    | Karan Joshi  | Snap Coffee House     |  3/10  | active
 *  13    | Karan Joshi  | Wellness Studio       | 10/10  | COMPLETED
 *  14    | Pooja Desai  | Spice Garden          |  7/10  | active
 *  15    | Pooja Desai  | The Book Nook         |  2/8   | active
 *
 * Run with:  bin/console doctrine:fixtures:load --group=stamp
 */
class StampFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    // card index => number of stamps to create
    private const STAMPS_PER_CARD = [
        1  => 10,
        2  => 7,
        3  => 3,
        4  => 5,
        5  => 2,
        6  => 8,
        7  => 10,
        8  => 6,
        9  => 4,
        10 => 1,
        11 => 9,
        12 => 3,
        13 => 10,
        14 => 7,
        15 => 2,
    ];

    public static function getGroups(): array
    {
        return ['stamp'];
    }

    public function getDependencies(): array
    {
        return [StampCardFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::STAMPS_PER_CARD as $cardIndex => $stampCount) {
            /** @var StampCard $card */
            $card = $this->getReference(StampCardFixtures::REF_PREFIX . $cardIndex, StampCard::class);

            for ($seq = 1; $seq <= $stampCount; $seq++) {
                // Spread stamps over the past N days — oldest stamp first
                $daysAgo = $stampCount - $seq;
                $collectedAt = new \DateTimeImmutable("-{$daysAgo} days");

                StampFactory::createOne([
                    'stampCard'     => $card,
                    'customer'      => $card->getCustomer(),
                    'merchant'      => $card->getMerchant(),
                    'stampSequence' => $seq,
                    'collectedAt'   => $collectedAt,
                    'isBonus'       => false,
                ]);

                $card->incrementStampCount();
            }

            $manager->persist($card);
        }

        $manager->flush();
    }
}

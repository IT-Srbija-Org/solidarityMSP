<?php

namespace App\DataFixtures;

use App\Entity\DamagedEducatorPeriod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class DamagedEducatorPeriodFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        for ($x = 5; $x >= 1; --$x) {
            $date = date('Y-m-d', strtotime("-$x month"));
            $isActive = (1 === $x);

            foreach ([true, false] as $isFirstHalf) {
                $educatorPeriod = new DamagedEducatorPeriod();
                $educatorPeriod->setMonth(date('m', strtotime($date)));
                $educatorPeriod->setYear(date('Y', strtotime($date)));
                $educatorPeriod->setFirstHalf($isFirstHalf);
                $educatorPeriod->setActive($isActive);

                $this->entityManager->persist($educatorPeriod);
            }
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [1];
    }
}

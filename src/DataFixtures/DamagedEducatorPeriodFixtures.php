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

            $month = date('m', strtotime($date));
            $year = date('Y', strtotime($date));

            if ($month == date('m', strtotime('-2 month'))) {
                $this->createPeriod($month, $year, $isActive, DamagedEducatorPeriod::TYPE_FIRST_HALF);
                $this->createPeriod($month, $year, $isActive, DamagedEducatorPeriod::TYPE_SECOND_HALF);
                continue;
            }

            $this->createPeriod($month, $year, $isActive, DamagedEducatorPeriod::TYPE_FULL);
        }

        $manager->flush();
    }

    private function createPeriod(int $month, int $year, bool $isActive, string $type): void
    {
        $educatorPeriod = new DamagedEducatorPeriod();
        $educatorPeriod->setMonth($month);
        $educatorPeriod->setYear($year);
        $educatorPeriod->setActive($isActive);
        $educatorPeriod->setType($type);

        $this->entityManager->persist($educatorPeriod);
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [1];
    }
}

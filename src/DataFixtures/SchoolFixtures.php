<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Schools;
use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class SchoolFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    private function determineSchoolType(string $schoolName): string
    {
        if (str_contains($schoolName, 'Osnovna škola')) {
            return 'Osnovna škola';
        }

        if (str_contains($schoolName, 'Gimnazija')) {
            return 'Gimnazija';
        }

        if (str_contains($schoolName, 'fakultet')) {
            return 'Univerzitet';
        }

        return 'Srednja stručna škola';
    }

    public function load(ObjectManager $manager): void
    {
        $schoolsData = ['schoolsMap' => Schools::getSchoolsMap()];

        foreach ($schoolsData['schoolsMap'] as $cityName => $schools) {
            $city = $this->entityManager->getRepository(City::class)->findOneBy(['name' => $cityName]);

            if (!$city) {
                continue;
            }

            foreach ($schools as $schoolName) {
                $school = new School();
                $school->setName($schoolName);
                $school->setCity($city);

                $typeName = $this->determineSchoolType($schoolName);
                $type = $this->entityManager->getRepository(SchoolType::class)->findOneBy(['name' => $typeName]);

                if (!$type) {
                    continue;
                }

                $school->setType($type);
                $manager->persist($school);
            }
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [2];
    }
}

<?php

namespace App\DataFixtures;

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

    public function load(ObjectManager $manager): void
    {
        $schools = [[
            'name' => 'Medicinska škola Beograd',
            'city' => 'Beograd',
            'type' => 'Srednja škola',
        ], [
            'name' => 'Osnovna škola Oslobodioci Beograda',
            'city' => 'Beograd',
            'type' => 'Osnovna škola',
        ], ];

        foreach ($schools as $schoolData) {
            $school = new School();
            $school->setName($schoolData['name']);

            $city = $this->entityManager->getRepository(City::class)->findOneBy(['name' => $schoolData['city']]);
            $school->setCity($city);

            $type = $this->entityManager->getRepository(SchoolType::class)->findOneBy(['name' => $schoolData['type']]);
            $school->setType($type);

            $manager->persist($school);
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

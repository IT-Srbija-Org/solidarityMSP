<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Schools;
use App\Entity\SchoolType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class SchoolTypeFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $types = Schools::getSchoolTypes();

        // Check if the types array is not empty
        if (!empty($types)) {
            foreach ($types as $type) {
                // Check if the school type already exists
                $existingSchoolType = $manager->getRepository(SchoolType::class)->findOneBy(['name' => $type]);

                if (!$existingSchoolType) {
                    // If school type doesn't exist, create and persist it
                    $schoolType = new SchoolType();
                    $schoolType->setName($type);
                    $manager->persist($schoolType);
                }
            }

            // Flush all changes to the database
            $manager->flush();
        }
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [1];
    }
}

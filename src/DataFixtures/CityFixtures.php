<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Schools;
use App\Entity\City;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CityFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get city names from Schools data class
        $cityNames = array_keys(Schools::getSchoolsMap());

        // Check if the city names array is not empty
        if (!empty($cityNames)) {
            // Sort cities alphabetically
            sort($cityNames);

            foreach ($cityNames as $name) {
                // Check if city already exists
                $existingCity = $manager->getRepository(City::class)->findOneBy(['name' => $name]);

                if (!$existingCity) {
                    // If city doesn't exist, create and persist it
                    $city = new City();
                    $city->setName($name);
                    $manager->persist($city);
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

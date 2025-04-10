<?php

namespace App\DataFixtures;

use App\Entity\School;
use App\Entity\User;
use App\Entity\UserDelegateSchool;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class UserDelegateSchoolFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $items = [[
            'email' => 'delegat@gmail.com',
            'schoolName' => 'Medicinska škola Beograd',
        ], ];

        foreach ($items as $item) {
            $userDelegateSchool = new UserDelegateSchool();

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $item['email']]);
            $userDelegateSchool->setUser($user);

            $school = $this->entityManager->getRepository(School::class)->findOneBy(['name' => $item['schoolName']]);
            $userDelegateSchool->setSchool($school);

            $manager->persist($userDelegateSchool);
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

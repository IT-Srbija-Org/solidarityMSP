<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Names;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // Set fixed seed for deterministic results
        mt_srand(1234);

        // Core test users from README.md
        $coreUsers = [
            [
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => 'korisnik@gmail.com',
                'role' => ['ROLE_USER'],
            ],
            [
                'firstName' => 'Test',
                'lastName' => 'Delegate',
                'email' => 'delegat@gmail.com',
                'role' => ['ROLE_USER', 'ROLE_DELEGATE'],
            ],
            [
                'firstName' => 'Test',
                'lastName' => 'Admin',
                'email' => 'admin@gmail.com',
                'role' => ['ROLE_USER', 'ROLE_ADMIN'],
            ],
        ];

        // Generate additional random users
        $additionalUsers = [];
        for ($i = 1; $i <= 50; ++$i) {
            $additionalUsers[] = [
                'firstName' => Names::getFirstNames()[array_rand(Names::getFirstNames())],
                'lastName' => Names::getLastNames()[array_rand(Names::getLastNames())],
                'email' => "user{$i}@example.com",
                'role' => ['ROLE_USER'],
            ];
        }

        $users = array_merge($coreUsers, $additionalUsers);

        foreach ($users as $userData) {
            $user = new User();
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setEmail($userData['email']);
            $user->setRoles($userData['role']);
            $user->setIsEmailVerified(true);
            $manager->persist($user);
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

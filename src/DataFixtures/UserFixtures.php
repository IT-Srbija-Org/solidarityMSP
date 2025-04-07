<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [[
            'firstName' => 'Marko',
            'lastName' => 'Markovic',
            'email' => 'korisnik@gmail.com',
            'role' => ['ROLE_USER']
        ], [
            'firstName' => 'Dragan',
            'lastName' => 'Pavlovic',
            'email' => 'delegat@gmail.com',
            'role' => ['ROLE_USER', 'ROLE_DELEGATE']
        ], [
            'firstName' => 'Jovan',
            'lastName' => 'Knezevic',
            'email' => 'admin@gmail.com',
            'role' => ['ROLE_USER', 'ROLE_ADMIN']
        ]];

        foreach ($users as $userData) {
            $user = new User();
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setEmail($userData['email']);
            $user->setPassword($this->passwordHasher->hashPassword($user, '12345678'));
            $user->setRoles($userData['role']);
            $user->setIsVerified(true);
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

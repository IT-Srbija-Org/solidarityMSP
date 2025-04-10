<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserDonor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class UserDonorFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [[
            'email' => 'korisnik@gmail.com',
            'isMonthly' => true,
            'amount' => 5000,
            'comment' => 'Podrzka za prosvetare!',
        ], [
            'email' => 'admin@gmail.com',
            'isMonthly' => false,
            'amount' => 3000,
            'comment' => null,
        ], ];

        foreach ($users as $userData) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);

            $userDonor = new UserDonor();
            $userDonor->setUser($user);
            $userDonor->setIsMonthly($userData['isMonthly']);
            $userDonor->setAmount($userData['amount']);
            $userDonor->setComment($userData['comment']);

            $manager->persist($userDonor);
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

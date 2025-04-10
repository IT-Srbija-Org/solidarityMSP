<?php

namespace App\DataFixtures;

use App\Entity\School;
use App\Entity\User;
use App\Entity\UserDelegateRequest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class UserDelegateRequestFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $items = [[
            'email' => 'korisnik2@gmail.com',
            'schoolName' => 'Medicinska škola Beograd',
        ], ];

        foreach ($items as $item) {
            $userDelegateRequest = new UserDelegateRequest();

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $item['email']]);
            $userDelegateRequest->setUser($user);

            $userDelegateRequest->setPhone('0654545656');
            $school = $this->entityManager->getRepository(School::class)->findOneBy(['name' => $item['schoolName']]);

            $userDelegateRequest->setSchoolType($school->getType());
            $userDelegateRequest->setCity($school->getCity());
            $userDelegateRequest->setSchool($school);
            $userDelegateRequest->setTotalEducators(100);
            $userDelegateRequest->setTotalBlockedEducators(50);

            $manager->persist($userDelegateRequest);
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [3];
    }
}

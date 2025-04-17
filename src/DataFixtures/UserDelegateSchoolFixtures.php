<?php

namespace App\DataFixtures;

use App\Entity\School;
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
        // Set fixed seed for deterministic results
        mt_srand(1234);

        // Get all delegates with confirmed delegate requests
        $confirmedRequests = $this->entityManager->getRepository(\App\Entity\UserDelegateRequest::class)
            ->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', \App\Entity\UserDelegateRequest::STATUS_CONFIRMED)
            ->getQuery()
            ->getResult();

        $delegates = [];
        foreach ($confirmedRequests as $request) {
            $delegates[] = $request->getUser();
        }

        // Get all schools
        $schools = $this->entityManager->getRepository(School::class)->findAll();

        foreach ($delegates as $delegate) {
            // Each delegate gets 1-3 random schools
            $schoolCount = mt_rand(1, 3);
            $randomSchools = (array) array_rand($schools, $schoolCount);

            foreach ($randomSchools as $schoolIndex) {
                $userDelegateSchool = new UserDelegateSchool();
                $userDelegateSchool->setUser($delegate);
                $userDelegateSchool->setSchool($schools[$schoolIndex]);
                $manager->persist($userDelegateSchool);
            }
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

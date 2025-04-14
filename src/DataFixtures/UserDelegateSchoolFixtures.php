<?php

namespace App\DataFixtures;

use App\Entity\School;
use App\Entity\User;
use App\Entity\UserDelegateRequest;
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

        // Get all confirmed delegates (delegates with confirmed requests)
        $confirmedDelegates = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->join('u.userDelegateRequest', 'dr')
            ->where('u.roles LIKE :role')
            ->andWhere('dr.status = :status')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->setParameter('status', UserDelegateRequest::STATUS_CONFIRMED)
            ->getQuery()
            ->getResult();

        // Get all schools
        $schools = $this->entityManager->getRepository(School::class)->findAll();

        foreach ($confirmedDelegates as $delegate) {
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

        // Flush all changes to the database
        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [3]; // After UserDelegateRequest fixtures
    }
}

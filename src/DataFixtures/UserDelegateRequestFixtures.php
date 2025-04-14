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
        // Set fixed seed for deterministic results
        mt_srand(1234);

        // Find regular users (not admin, not delegate)
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->andWhere('u.roles NOT LIKE :admin')
            ->andWhere('u.roles NOT LIKE :delegate')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('delegate', '%ROLE_DELEGATE%')
            ->setMaxResults(5) // Get 5 random users
            ->getQuery()
            ->getResult();

        // Get all schools
        $schools = $this->entityManager->getRepository(School::class)->findAll();

        foreach ($users as $user) {
            $userDelegateRequest = new UserDelegateRequest();
            $userDelegateRequest->setUser($user);

            // Mobile operator prefixes
            $prefixes = ['061', '062', '063', '064', '065', '066'];
            $prefix = $prefixes[array_rand($prefixes)];
            $number = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            $userDelegateRequest->setPhone($prefix.$number);

            // Check if the schools array is not empty
            if (!empty($schools)) {
                // Pick random school
                $school = $schools[array_rand($schools)];
                $userDelegateRequest->setSchoolType($school->getType());
                $userDelegateRequest->setCity($school->getCity());
                $userDelegateRequest->setSchool($school);
            }

            // Random educator counts
            $total = mt_rand(50, 200);
            $blocked = (int) round($total * (mt_rand(30, 70) / 100)); // 30-70% of total
            $userDelegateRequest->setTotalEducators($total);
            $userDelegateRequest->setTotalBlockedEducators($blocked);

            // Set status: 50% confirmed, 25% new, 25% rejected
            $rand = mt_rand(1, 100);
            $status = match (true) {
                $rand <= 50 => UserDelegateRequest::STATUS_CONFIRMED,
                $rand <= 75 => UserDelegateRequest::STATUS_NEW,
                default => UserDelegateRequest::STATUS_REJECTED,
            };
            $userDelegateRequest->setStatus($status);

            // If request is confirmed, add ROLE_DELEGATE to the user
            if (UserDelegateRequest::STATUS_CONFIRMED === $status) {
                $user->addRole('ROLE_DELEGATE');
                $manager->persist($user);
            }

            $manager->persist($userDelegateRequest);
        }

        // Flush all changes to the database
        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [2]; // Run with Schools, before UserDelegateSchool assignments
    }
}

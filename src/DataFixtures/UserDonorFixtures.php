<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Amounts;
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

    private array $comments = [
        'Podrška za prosvetare!',
        'Svaka čast na hrabrosti!',
        'Solidarnost je naša snaga!',
        'Zajedno smo jači!',
        null,
    ];

    public function load(ObjectManager $manager): void
    {
        // Set fixed seed for deterministic results
        mt_srand(1234);

        // Get all regular users (not admin, not delegate)
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->andWhere('u.roles NOT LIKE :admin')
            ->andWhere('u.roles NOT LIKE :delegate')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('delegate', '%ROLE_DELEGATE%')
            ->getQuery()
            ->getResult();

        // Randomly select 70% of users
        shuffle($users);
        $selectedCount = (int) ceil(count($users) * 0.7);
        $selectedUsers = array_slice($users, 0, $selectedCount);

        foreach ($selectedUsers as $user) {
            $userDonor = new UserDonor();
            $userDonor->setUser($user);
            $userDonor->setIsMonthly((bool) mt_rand(0, 1));

            // Generate amount between 500 and 100000, clustering around 5000
            $userDonor->setAmount(Amounts::generate(5000, null, 500, 100000));
            $userDonor->setComment($this->comments[array_rand($this->comments)]);

            $manager->persist($userDonor);
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [4];
    }
}

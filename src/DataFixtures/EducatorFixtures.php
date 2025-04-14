<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Amounts;
use App\DataFixtures\Data\Names;
use App\Entity\Educator;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserDelegateRequest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class EducatorFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    private function generateName(): string
    {
        $firstName = Names::getFirstNames()[array_rand(Names::getFirstNames())];
        $lastName = Names::getLastNames()[array_rand(Names::getLastNames())];

        return $firstName.' '.$lastName;
    }

    private function generateAccountNumber(): string
    {
        // Generate base number (160 is bank code for Banca Intesa)
        // We need a 16-digit number (18 total - 2 control digits)
        $base = '160'.str_pad(mt_rand(0, 9999999999), 13, '0', STR_PAD_LEFT);

        // Calculate control digits using MOD97
        $controlNumber = 98 - ((int) $base % 97);

        // Format final number with control digits
        return $base.str_pad($controlNumber, 2, '0', STR_PAD_LEFT);
    }

    public function load(ObjectManager $manager): void
    {
        // Set fixed seed for deterministic results
        mt_srand(1234);

        // Get all confirmed delegates
        $delegates = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->join('u.userDelegateRequest', 'dr')
            ->where('u.roles LIKE :role')
            ->andWhere('dr.status = :status')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->setParameter('status', UserDelegateRequest::STATUS_CONFIRMED)
            ->getQuery()
            ->getResult();

        if (empty($delegates)) {
            throw new \RuntimeException('No confirmed delegates found!');
        }

        $schools = $this->entityManager->getRepository(School::class)->findAll();

        foreach ($schools as $school) {
            // Generate 1-30 educators per school
            $count = mt_rand(1, 30);

            for ($i = 0; $i < $count; ++$i) {
                $educator = new Educator();
                $educator->setName($this->generateName());
                $educator->setSchool($school);
                $educator->setAmount(Amounts::generate(30000, null, 15, 50000));
                $educator->setAccountNumber($this->generateAccountNumber());
                // Pick random confirmed delegate
                $delegate = $delegates[array_rand($delegates)];
                $educator->setCreatedBy($delegate);

                $manager->persist($educator);
            }
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [5]; // After UserDelegateSchool and UserDonor fixtures
    }
}

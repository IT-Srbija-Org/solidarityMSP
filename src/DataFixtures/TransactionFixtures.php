<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Amounts;
use App\Entity\Educator;
use App\Entity\Transaction;
use App\Entity\UserDonor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Set fixed seed for deterministic results
        mt_srand(1234);

        // Get all donors and educators
        $donors = $this->entityManager->getRepository(UserDonor::class)->findAll();
        $educators = $this->entityManager->getRepository(Educator::class)->findAll();

        if (empty($donors) || empty($educators)) {
            return; // Skip if no donors or educators exist yet
        }

        // Each donor will make 1-5 transactions
        foreach ($donors as $donor) {
            $transactionCount = mt_rand(1, 5);

            for ($i = 0; $i < $transactionCount; ++$i) {
                $transaction = new Transaction();
                $transaction->setUser($donor->getUser());

                // Pick random educator
                $educator = $educators[array_rand($educators)];
                $transaction->setEducator($educator);

                // Generate amount based on donor's monthly amount
                $transaction->setAmount(Amounts::generate($donor->getAmount(), null, 1000, $donor->getAmount() * 2));

                // Use educator's account number
                $transaction->setAccountNumber($educator->getAccountNumber());

                $manager->persist($transaction);
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
        return [6]; // Run last, after all other fixtures
    }
}

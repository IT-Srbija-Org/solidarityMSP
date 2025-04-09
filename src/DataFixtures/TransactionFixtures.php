<?php

namespace App\DataFixtures;

use App\Entity\Educator;
use App\Entity\Transaction;
use App\Entity\User;
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
        $items = [[
            'donorEmail' => 'korisnik@gmail.com',
            'amount' => 1200,
        ]];

        foreach ($items as $item) {
            $transaction = new Transaction();

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $item['donorEmail']]);
            $transaction->setUser($user);

            $educator = $this->entityManager->getRepository(Educator::class)->findOneBy(['accountNumber' => '165000112012133333']);
            $transaction->setEducator($educator);

            $transaction->setAmount($item['amount']);
            $manager->persist($transaction);
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

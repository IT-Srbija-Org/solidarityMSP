<?php

namespace App\DataFixtures;

use App\Entity\Educator;
use App\Entity\School;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class EducatorFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $items = [[
            'name' => 'Nikola Pavlovic',
            'schoolName' => 'Medicinska škola Beograd',
            'amount' => 5000,
            'accountNumber' => 165000112012133333,
            'delegatEmail' => 'delegat@gmail.com',
        ], [
            'name' => 'Dragan Kokic',
            'schoolName' => 'Medicinska škola Beograd',
            'amount' => 45000,
            'accountNumber' => 160000112012132222,
            'delegatEmail' => 'delegat@gmail.com',
        ], ];

        foreach ($items as $item) {
            $educator = new Educator();
            $educator->setName($item['name']);

            $school = $this->entityManager->getRepository(School::class)->findOneBy(['name' => $item['schoolName']]);
            $educator->setSchool($school);

            $educator->setAmount($item['amount']);
            $educator->setAccountNumber((string) $item['accountNumber']);

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $item['delegatEmail']]);
            $educator->setCreatedBy($user);

            $manager->persist($educator);
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

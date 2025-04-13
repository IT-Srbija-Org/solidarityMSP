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
    private array $delegateComments = [
        'Predstavnik sam grupe profesora u našoj školi. U stalnoj sam komunikaciji sa kolegama i aktivno učestvujem u organizaciji.',
        'Kao vođa sindikalne organizacije u školi, zadužen sam za koordinaciju između uprave i nastavnog osoblja.',
        'Izabran sam od strane kolega da predstavljam interese nastavnog kolektiva naše škole.',
    ];

    private array $confirmedComments = [
        'Prihvaćen zahtev nakon provere dokumentacije.',
        'Uspešna verifikacija škole i broja nastavnika.',
        'Podaci potvrđeni od strane uprave škole.',
        'Verifikovan identitet podnosioca zahteva.',
        'Potvrđena saradnja sa školom.',
    ];

    private array $rejectedComments = [
        'Odbijen zbog nedovoljnog broja aktivnih nastavnika.',
        'Potrebna dodatna dokumentacija.',
        'Neuspešna verifikacija podataka škole.',
        'Nepotpuna dokumentacija o broju nastavnika.',
        'Nije moguće potvrditi identitet podnosioca.',
    ];

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
            $userDelegateRequest->setComment($this->delegateComments[array_rand($this->delegateComments)]);

            // Pick random school
            $school = $schools[array_rand($schools)];
            $userDelegateRequest->setSchoolType($school->getType());
            $userDelegateRequest->setCity($school->getCity());
            $userDelegateRequest->setSchool($school);

            // Random educator counts
            $total = mt_rand(50, 200);
            $blocked = (int) round($total * (mt_rand(30, 70) / 100)); // 30-70% of total
            $userDelegateRequest->setTotalEducators($total);
            $userDelegateRequest->setTotalBlockedEducators($blocked);

            // Set status: evenly distributed (33% each)
            $rand = mt_rand(1, 100);
            $status = match (true) {
                $rand <= 33 => UserDelegateRequest::STATUS_CONFIRMED,
                $rand <= 66 => UserDelegateRequest::STATUS_NEW,
                default => UserDelegateRequest::STATUS_REJECTED,
            };
            $userDelegateRequest->setStatus($status);

            // Add admin comment for confirmed or rejected requests
            if (UserDelegateRequest::STATUS_CONFIRMED === $status) {
                $userDelegateRequest->setAdminComment($this->confirmedComments[array_rand($this->confirmedComments)]);
            } elseif (UserDelegateRequest::STATUS_REJECTED === $status) {
                $userDelegateRequest->setAdminComment($this->rejectedComments[array_rand($this->rejectedComments)]);
            }

            // If request is confirmed, add ROLE_DELEGATE to the user
            if (UserDelegateRequest::STATUS_CONFIRMED === $status) {
                $user->addRole('ROLE_DELEGATE');
                $manager->persist($user);
            }

            $manager->persist($userDelegateRequest);
        }

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

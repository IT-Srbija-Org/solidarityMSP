<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Names;
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

        // Create new delegate users with random names
        $users = [];
        for ($i = 1; $i <= 10; ++$i) {
            $user = new User();
            $firstName = Names::getFirstNames()[array_rand(Names::getFirstNames())];
            $lastName = Names::getLastNames()[array_rand(Names::getLastNames())];
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail("delegat{$i}@example.com");
            $user->setRoles(['ROLE_USER', 'ROLE_DELEGATE']);
            $user->setIsVerified(true);
            $this->entityManager->persist($user);
            $users[] = $user;
        }

        // Also add the core 'delegat@gmail.com' user as a confirmed delegate
        $coreDelegate = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'delegat@gmail.com']);
        if ($coreDelegate) {
            $coreDelegate->setRoles(['ROLE_USER', 'ROLE_DELEGATE']);
            $coreDelegate->setIsVerified(true);
            $this->entityManager->persist($coreDelegate);
            $users[] = $coreDelegate;
        }

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

            // For the core 'delegat@gmail.com', always set status to CONFIRMED
            if ('delegat@gmail.com' === $user->getEmail()) {
                $status = UserDelegateRequest::STATUS_CONFIRMED;
            } else {
                // Set status: evenly distributed (33% each)
                $rand = mt_rand(1, 100);
                $status = match (true) {
                    $rand <= 33 => UserDelegateRequest::STATUS_CONFIRMED,
                    $rand <= 66 => UserDelegateRequest::STATUS_NEW,
                    default => UserDelegateRequest::STATUS_REJECTED,
                };
            }

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
        return [2];
    }
}

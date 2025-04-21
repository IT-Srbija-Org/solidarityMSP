<?php

namespace App\Command;

use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType;
use App\Entity\User;
use App\Entity\UserDelegateRequest;
use App\Entity\UserDelegateSchool;
use App\Entity\UserDonor;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:database-migration',
    description: 'Migrate old data to new',
)]
class DatabaseMigrationCommand extends Command
{
    private Connection $oldConnection;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->oldConnection = $this->getOldConnection();

        $io->writeln('Migration started at ' . date('Y-m-d H:i:s'));

        $this->syncCities($io);
        $this->syncUsers($io);
        $this->syncSchoolTypes($io);
        $this->syncSchool($io);
        $this->syncDonors($io);
        $this->syncDelegate($io);

        $io->success('Migration completed at ' . date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function syncUsers(SymfonyStyle $io): void
    {
        $io->writeln('Syncing users...');
        $items = $this->oldConnection->executeQuery('SELECT * FROM user')->iterateAssociative();
        $count = 0;

        foreach ($items as $item) {
            $entity = new User();
            $entity->setFirstName($item['firstName']);
            $entity->setLastName($item['lastName']);
            $entity->setEmail($item['email']);
            $entity->addRole('ROLE_ADMIN');
            $entity->setIsEmailVerified(true);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->updateDates('user', $entity->getId(), $item['createdAt'], $item['updatedAt']);

            $count++;
            if ($count % 25 == 0) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d users', $count));
    }

    public function syncCities(SymfonyStyle $io): void
    {
        $io->writeln('Syncing cities...');
        $items = $this->oldConnection->executeQuery('SELECT * FROM city')->iterateAssociative();
        $count = 0;

        foreach ($items as $item) {
            $entity = new City();
            $entity->setName($item['name']);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->updateDates('city', $entity->getId(), $item['createdAt'], $item['updatedAt']);

            $count++;
            if ($count % 25 == 0) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d cities', $count));
    }

    public function syncSchoolTypes(SymfonyStyle $io): void
    {
        $io->writeln('Syncing school types...');
        $items = $this->oldConnection->executeQuery('SELECT * FROM schoolType')->iterateAssociative();

        $count = 0;
        foreach ($items as $item) {
            $entity = new SchoolType();
            $entity->setName($item['name']);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->updateDates('school_type', $entity->getId(), $item['createdAt'], $item['updatedAt']);

            $count++;
            if ($count % 25 == 0) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d school types', $count));
    }

    public function syncDelegate(SymfonyStyle $io): void
    {
        $io->writeln('Syncing delegates...');
        $items = $this->oldConnection->executeQuery('SELECT * FROM delegate')->iterateAssociative();

        $count = 0;
        foreach ($items as $item) {
            $city = $this->entityManager->getRepository(City::class)->findOneBy(['name' => $item['city']]);
            if (empty($city)) {
                continue;
            }

            $school = $this->entityManager->getRepository(School::class)->findOneBy([
                'city' => $city,
                'name' => $item['schoolName']
            ]);

            if (empty($school)) {
                continue;
            }

            $explodedName = explode(' ', $item['name']);

            $givenName = $explodedName[0];
            unset($explodedName[0]);

            $lastName = implode(' ', $explodedName);

            $entity = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $item['email']]);
            if (empty($entity)) {
                $entity = new User();
                $entity->setEmail($item['email']);
                $entity->setIsEmailVerified(true);
            }

            $entity->setFirstName(empty($givenName) ? null : $givenName);
            $entity->setLastName(empty($lastName) ? null : $lastName);

            if ($item['status'] == 2) {
                $entity->addRole('ROLE_DELEGATE');
            }

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->updateDates('user', $entity->getId(), $item['createdAt'], $item['updatedAt']);

            // Create delegate request
            $delegateRequest = $this->entityManager->getRepository(UserDelegateRequest::class)->findOneBy(['user' => $entity]);
            if (empty($delegateRequest)) {
                $delegateRequest = new UserDelegateRequest();
                $delegateRequest->setUser($entity);

                if ($this->validateSerbianPhoneNumber($item['phone'])) {
                    $delegateRequest->setPhone($item['phone']);
                }

                $delegateRequest->setCity($school->getCity());
                $delegateRequest->setSchoolType($school->getType());
                $delegateRequest->setSchool($school);
                $delegateRequest->setComment($item['comment'] ?? null);

                if ($item['status'] == 1) {
                    $delegateRequest->setStatus(UserDelegateRequest::STATUS_NEW);
                } elseif ($item['status'] == 2) {
                    $delegateRequest->setStatus(UserDelegateRequest::STATUS_CONFIRMED);
                    $delegateRequest->setAdminComment($item['verifiedBy'] ?? null);
                } else {
                    $delegateRequest->setStatus(UserDelegateRequest::STATUS_REJECTED);
                    $delegateRequest->setAdminComment($item['verifiedBy'] ?? null);
                }

                $totalEducators = empty((int)$item['count']) ? null : (int)$item['count'];
                $totalBlockedEducators = empty((int)$item['countBlocking']) ? null : (int)$item['countBlocking'];

                if ($totalBlockedEducators > $totalEducators) {
                    $totalBlockedEducators = $totalEducators;
                }

                if ($totalBlockedEducators && $totalEducators) {
                    $delegateRequest->setTotalEducators($totalEducators);
                    $delegateRequest->setTotalBlockedEducators($totalBlockedEducators);
                }

                $this->entityManager->persist($delegateRequest);
            }

            // Create connection between Delegate and School if approved
            if ($item['status'] == 2) {
                $userDelegateSchool = new UserDelegateSchool();
                $userDelegateSchool->setUser($entity);
                $userDelegateSchool->setSchool($school);
                $this->entityManager->persist($userDelegateSchool);
                $this->entityManager->flush();

                $this->updateDates('user_delegate_school', $userDelegateSchool->getId(), $item['createdAt'],
                    $item['updatedAt']);
            }

            $count++;
            if ($count % 25 == 0) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d delegates', $count));
    }

    public function syncSchool(SymfonyStyle $io): void
    {
        $io->writeln('Syncing schools...');
        $count = 0;

        $query = $this->oldConnection->executeQuery('
            SELECT s.name, c.name AS city, st.name AS type, s.createdAt, s.updatedAt
            FROM school AS s
             INNER JOIN city AS c ON c.id = s.cityId
             INNER JOIN schoolType AS st ON st.id = s.typeId
        ');

        $items = $query->iterateAssociative();
        foreach ($items as $item) {
            $entity = new School();
            $entity->setName($item['name']);

            $city = $this->entityManager->getRepository(City::class)->findOneBy(['name' => $item['city']]);
            $entity->setCity($city);

            $type = $this->entityManager->getRepository(SchoolType::class)->findOneBy(['name' => $item['type']]);
            $entity->setType($type);

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->updateDates('school', $entity->getId(), $item['createdAt'], $item['updatedAt']);

            $count++;
            if ($count % 25 == 0) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d schools', $count));
    }

    public function syncDonors(SymfonyStyle $io): void
    {
        $io->writeln('Syncing donors...');
        $items = $this->oldConnection->executeQuery('SELECT * FROM donor WHERE status = 1')->iterateAssociative();
        $count = 0;

        foreach ($items as $item) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $item['email']]);

            if (!$user) {
                $user = new User();
                $user->setEmail($item['email']);
                $user->setIsEmailVerified(true);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->updateDates('user', $user->getId(), $item['createdAt'], $item['updatedAt']);
            }

            if ($item['amount'] >= 500) {
                $userDonor = new UserDonor();
                $userDonor->setUser($user);
                $userDonor->setAmount($item['amount']);
                $userDonor->setIsMonthly((bool)$item['monthly']);
                $this->entityManager->persist($userDonor);
                $this->entityManager->flush();

                $this->updateDates('user_donor', $userDonor->getId(), $item['createdAt'], $item['updatedAt']);
            }

            $count++;
            if ($count % 25 == 0) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d donors', $count));
    }

    private function getOldConnection(): Connection
    {
        $connectionParams = [
            'url' => $_ENV['OLD_DATABASE_URL'],
        ];

        return DriverManager::getConnection($connectionParams);
    }

    public function updateDates($tableName, $id, $createdAt, $updatedAt): void
    {
        $this->entityManager->getConnection()->executeQuery('
            UPDATE ' . $tableName . ' SET created_at = :createdAt, updated_at = :updatedAt WHERE id = :id
        ', [
            'id' => $id,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt,
        ]);
    }

    private function validateSerbianPhoneNumber(string $phoneNumber): bool
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            // Pre-validate: must be 9 or 10 digits starting with 0
            if (!preg_match('/^0\d{8,9}$/', $phoneNumber)) {
                return false;
            }

            // Convert to international format for libphonenumber
            $phoneNumber = '+381' . substr($phoneNumber, 1);

            $numberProto = $phoneUtil->parse($phoneNumber, 'RS');

            return $phoneUtil->isValidNumberForRegion($numberProto, 'RS');
        } catch (NumberParseException) {
            return false;
        }
    }
}

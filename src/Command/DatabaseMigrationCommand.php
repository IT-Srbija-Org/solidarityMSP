<?php

namespace App\Command;

use App\Entity\City;
use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\School;
use App\Entity\SchoolType;
use App\Entity\Transaction;
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
use Symfony\Component\Process\Process;

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

        $io->writeln('Migration started at '.date('Y-m-d H:i:s'));

        $this->clearDatabases();
        $this->createPeriod($io);
        $this->syncCities($io);
        $this->syncUsers($io);
        $this->syncSchoolTypes($io);
        $this->syncSchool($io);
        $this->syncDonors($io);
        $this->syncDelegate($io);
        $this->syncDamagedEducators($io);
        $this->syncTransactions($io);

        $io->success('Migration completed at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function clearDatabases(): void
    {
        $commands = [
            ['php', 'bin/console', 'doctrine:schema:drop', '--force'],
            ['php', 'bin/console', 'doctrine:schema:update', '--force'],
        ];

        foreach ($commands as $command) {
            $process = new Process($command);
            $process->run();
        }
    }

    public function createPeriod(SymfonyStyle $io): void
    {
        $io->writeln('Creating period...');

        $entity = new DamagedEducatorPeriod();
        $entity->setMonth(2);
        $entity->setYear(2025);
        $entity->setType(DamagedEducatorPeriod::TYPE_FIRST_HALF);
        $entity->setActive(false);
        $this->entityManager->persist($entity);

        $entity = new DamagedEducatorPeriod();
        $entity->setMonth(2);
        $entity->setYear(2025);
        $entity->setType(DamagedEducatorPeriod::TYPE_SECOND_HALF);
        $entity->setActive(true);
        $this->entityManager->persist($entity);

        $this->entityManager->flush();

        $io->writeln('Period created');
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

            ++$count;
            if (0 == $count % 25) {
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

            ++$count;
            if (0 == $count % 25) {
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

            ++$count;
            if (0 == $count % 25) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d school types', $count));
    }

    public function syncDelegate(SymfonyStyle $io): void
    {
        $io->writeln('Syncing delegates...');
        $items = $this->oldConnection->executeQuery('
            SELECT d.name, d.email, d.status, s.name AS school, c.name AS city,
             d.createdAt, d.updatedAt, d.phone, d.comment, d.verifiedBy, d.countBlocking, d.count
            FROM delegate AS d
             INNER JOIN school AS s ON s.id = d.schoolId
             INNER JOIN city AS c ON c.id = s.cityId
            ')->iterateAssociative();

        $count = 0;
        foreach ($items as $item) {
            $city = $this->entityManager->getRepository(City::class)->findOneBy(['name' => $item['city']]);
            $school = $this->entityManager->getRepository(School::class)->findOneBy([
                'city' => $city,
                'name' => $item['school'],
            ]);

            $explodedName = explode(' ', $item['name']);

            $firstName = trim($explodedName[0]);
            unset($explodedName[0]);

            $lastName = trim(implode(' ', $explodedName));

            $entity = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $item['email']]);
            if (empty($entity)) {
                $entity = new User();
                $entity->setEmail($item['email']);
                $entity->setIsEmailVerified(true);
            }

            $entity->setFirstName(empty($firstName) ? null : $firstName);
            $entity->setLastName(empty($lastName) ? null : $lastName);

            if (2 == $item['status']) {
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
                $delegateRequest->setFirstName($firstName);
                $delegateRequest->setLastName($lastName);

                if ($this->validateSerbianPhoneNumber($item['phone'])) {
                    $delegateRequest->setPhone($item['phone']);
                }

                $delegateRequest->setCity($school->getCity());
                $delegateRequest->setSchoolType($school->getType());
                $delegateRequest->setSchool($school);
                $delegateRequest->setComment($item['comment'] ?? null);

                if (1 == $item['status']) {
                    $delegateRequest->setStatus(UserDelegateRequest::STATUS_NEW);
                } elseif (2 == $item['status']) {
                    $delegateRequest->setStatus(UserDelegateRequest::STATUS_CONFIRMED);
                    $delegateRequest->setAdminComment($item['verifiedBy'] ?? null);
                } else {
                    $delegateRequest->setStatus(UserDelegateRequest::STATUS_REJECTED);
                    $delegateRequest->setAdminComment($item['verifiedBy'] ?? null);
                }

                $totalEducators = empty((int) $item['count']) ? null : (int) $item['count'];
                $totalBlockedEducators = empty((int) $item['countBlocking']) ? null : (int) $item['countBlocking'];

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
            if (2 == $item['status']) {
                $userDelegateSchool = new UserDelegateSchool();
                $userDelegateSchool->setUser($entity);
                $userDelegateSchool->setSchool($school);
                $this->entityManager->persist($userDelegateSchool);
                $this->entityManager->flush();

                $this->updateDates('user_delegate_school', $userDelegateSchool->getId(), $item['createdAt'],
                    $item['updatedAt']);
            }

            ++$count;
            if (0 == $count % 25) {
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

            ++$count;
            if (0 == $count % 25) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d schools', $count));
    }

    public function syncDamagedEducators(SymfonyStyle $io): void
    {
        $io->writeln('Syncing damaged educators...');
        $count = 0;

        foreach ([DamagedEducatorPeriod::TYPE_FIRST_HALF, DamagedEducatorPeriod::TYPE_SECOND_HALF] as $type) {
            $roundId = 1;
            if (DamagedEducatorPeriod::TYPE_SECOND_HALF == $type) {
                $roundId = 2;
            }

            $smtp = $this->oldConnection->prepare('
                SELECT e.name, e.accountNumber, er.amount, c.name AS city, s.name AS school, e.createdAt, e.updatedAt, e.status, e.comment
                FROM educator_roundImport AS er
                 INNER JOIN educatorImport AS e ON e.id = er.educatorId
                 INNER JOIN school AS s ON s.id = e.schoolId
                 INNER JOIN city AS c ON c.id = s.cityId
                WHERE er.roundId = :roundId
                ');
            $items = $smtp->executeQuery(['roundId' => $roundId])->fetchAllAssociative();

            $period = $this->entityManager->getRepository(DamagedEducatorPeriod::class)->findOneBy([
                'month' => 2,
                'year' => 2025,
                'type' => $type,
            ]);

            foreach ($items as $item) {
                $status = match ($item['status']) {
                    1 => DamagedEducator::STATUS_NEW,
                    2 => DamagedEducator::STATUS_NEW,
                    3 => DamagedEducator::STATUS_NEW,
                    4 => DamagedEducator::STATUS_DELETED,
                    5 => DamagedEducator::STATUS_NEW,
                    6 => DamagedEducator::STATUS_DELETED,
                    7 => DamagedEducator::STATUS_DELETED,
                };

                $entity = new DamagedEducator();
                $entity->setName($item['name']);
                $entity->setAccountNumber($item['accountNumber']);
                $entity->setAmount($item['amount']);
                $entity->setPeriod($period);
                $entity->setStatus($status);

                if (DamagedEducator::STATUS_DELETED == $status) {
                    $entity->setStatusComment($item['comment']);
                }

                $city = $this->entityManager->getRepository(City::class)->findOneBy(['name' => $item['city']]);
                $school = $this->entityManager->getRepository(School::class)->findOneBy([
                    'city' => $city,
                    'name' => $item['school'],
                ]);

                $entity->setSchool($school);

                $this->entityManager->persist($entity);
                $this->entityManager->flush();

                $this->updateDates('damaged_educator', $entity->getId(), $item['createdAt'], $item['updatedAt']);

                ++$count;
                if (0 == $count % 25) {
                    $this->entityManager->clear();

                    $period = $this->entityManager->getRepository(DamagedEducatorPeriod::class)->findOneBy([
                        'month' => 2,
                        'year' => 2025,
                        'type' => $type,
                    ]);
                }
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d damaged educators', $count));
    }

    public function syncTransactions(SymfonyStyle $io): void
    {
        $io->writeln('Syncing transactions...');
        $count = 0;

        foreach ([DamagedEducatorPeriod::TYPE_FIRST_HALF, DamagedEducatorPeriod::TYPE_SECOND_HALF] as $type) {
            $roundId = 1;
            if (DamagedEducatorPeriod::TYPE_SECOND_HALF == $type) {
                $roundId = 2;
            }

            $smtp = $this->oldConnection->prepare('
                SELECT *
                FROM transactionImport
                WHERE roundId = :roundId
                ');
            $items = $smtp->executeQuery(['roundId' => $roundId])->fetchAllAssociative();

            $period = $this->entityManager->getRepository(DamagedEducatorPeriod::class)->findOneBy([
                'month' => 2,
                'year' => 2025,
                'type' => $type,
            ]);

            foreach ($items as $item) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $item['email']]);
                if (empty($user)) {
                    // Create user
                    $user = new User();
                    $user->setEmail($item['email']);
                    $user->setIsEmailVerified(true);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }

                $damagedEducator = $this->entityManager->getRepository(DamagedEducator::class)->findOneBy([
                    'period' => $period,
                    'accountNumber' => $item['accountNumber'],
                ]);

                if (empty($damagedEducator)) {
                    continue;
                }

                $status = match ($item['status']) {
                    1 => Transaction::STATUS_WAITING_CONFIRMATION,
                    2 => Transaction::STATUS_WAITING_CONFIRMATION,
                    3 => Transaction::STATUS_CONFIRMED,
                    4 => Transaction::STATUS_CANCELLED,
                    default => Transaction::STATUS_WAITING_CONFIRMATION,
                };

                $entity = new Transaction();
                $entity->setUser($user);
                $entity->setDamagedEducator($damagedEducator);
                $entity->setAccountNumber($item['accountNumber']);
                $entity->setAmount($item['amount']);
                $entity->setStatus($status);

                $this->entityManager->persist($entity);
                $this->entityManager->flush();

                $this->updateDates('transaction', $entity->getId(), $item['createdAt'], $item['updatedAt']);

                ++$count;
                if (0 == $count % 10) {
                    $this->entityManager->clear();

                    $period = $this->entityManager->getRepository(DamagedEducatorPeriod::class)->findOneBy([
                        'month' => 2,
                        'year' => 2025,
                        'type' => $type,
                    ]);
                }
            }
        }

        $this->entityManager->clear();
        $io->writeln(sprintf('Synced %d transactions', $count));
    }

    public function syncDonors(SymfonyStyle $io): void
    {
        $io->writeln('Syncing donors...');
        $items = $this->oldConnection->executeQuery('SELECT * FROM donor')->iterateAssociative();
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

            if (3 != $item['status'] && $item['amount'] >= 500) {
                $userDonor = new UserDonor();
                $userDonor->setUser($user);
                $userDonor->setAmount($item['amount']);
                $userDonor->setIsMonthly((bool) $item['monthly']);
                $this->entityManager->persist($userDonor);
                $this->entityManager->flush();

                $this->updateDates('user_donor', $userDonor->getId(), $item['createdAt'], $item['updatedAt']);
            }

            ++$count;
            if (0 == $count % 25) {
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
            UPDATE '.$tableName.' SET created_at = :createdAt, updated_at = :updatedAt WHERE id = :id
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
            $phoneNumber = '+381'.substr($phoneNumber, 1);

            $numberProto = $phoneUtil->parse($phoneNumber, 'RS');

            return $phoneUtil->isValidNumberForRegion($numberProto, 'RS');
        } catch (NumberParseException) {
            return false;
        }
    }
}

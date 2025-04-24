<?php

namespace App\Command;

use App\Entity\DamagedEducator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:convert-eurobank-accounts',
    description: 'Converts all Eurobank (150*) account numbers in DamagedEducator to new numbers in AIK Bank using the official mapping API',
)]
class ConvertEurobankAccountsCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Fetching DamagedEducator entities with accountNumber starting with 150...');

        $educators = $this->entityManager->getRepository(DamagedEducator::class)
            ->createQueryBuilder('e')
            ->where('e.accountNumber LIKE :prefix')
            ->setParameter('prefix', '150%')
            ->getQuery()
            ->getResult();

        if (empty($educators)) {
            $io->success('No DamagedEducator entities found with accountNumber starting with 150.');

            return Command::SUCCESS;
        }

        $updated = 0;
        $failed = 0;

        foreach ($educators as $educator) {
            // After testing, 1 second delay is added to avoid overwhelming the API
            // If delay is less than 1 second, the API may ban the IP
            sleep(1);

            $oldAccount = $educator->getAccountNumber();
            $io->text(sprintf('Processing Educator ID: %d, Name: %s, Old Account: %s', $educator->getId(), $educator->getName(), $oldAccount));

            $apiUrl = 'https://novibrojracuna-api.aikbanka.rs/api/rebranding/get-mapped-account?account='.urlencode($oldAccount);

            $response = @file_get_contents($apiUrl);

            if (false === $response) {
                $io->error('API request failed for account: '.$oldAccount);
                ++$failed;
                continue;
            }

            $data = json_decode($response, true);

            if (!is_array($data) || !isset($data['isValid']) || !$data['isValid'] || empty($data['account'])) {
                $io->warning('API did not return a valid mapping for account: '.$oldAccount);
                ++$failed;
                continue;
            }

            $newAccount = $data['account'];

            $educator->setAccountNumber($newAccount);
            $this->entityManager->persist($educator);

            $io->success(sprintf('Updated Educator ID: %d, Name: %s, New Account: %s', $educator->getId(), $educator->getName(), $newAccount));
            ++$updated;
        }

        $this->entityManager->flush();

        $io->section('Summary');
        $io->success(sprintf('Updated %d account(s).', $updated));
        if ($failed > 0) {
            $io->warning(sprintf('Failed to update %d account(s).', $failed));
        }

        return Command::SUCCESS;
    }
}

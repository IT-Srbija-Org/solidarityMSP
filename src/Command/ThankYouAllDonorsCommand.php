<?php

namespace App\Command;

use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:thank-you-all-donors',
    description: 'Send trank you email to all donors who have paid transactions'
)]
class ThankYouAllDonorsCommand extends Command
{
    private int $lastId = 0;

    public function __construct(private EntityManagerInterface $entityManager, private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = new FlockStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock($this->getName(), 0);
        if (!$lock->acquire()) {
            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        while (true) {
            $donorEmails = $this->getDonorEmails();
            if (empty($donorEmails)) {
                break;
            }

            foreach ($donorEmails as $donorEmail) {
                $output->writeln('Send email to '.$donorEmail);
                $this->sendEmail($donorEmail);
            }
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function sendEmail(string $email): void
    {
        $message = (new TemplatedEmail())
            ->to($email)
            ->from(new Address('donatori@mrezasolidarnosti.org', 'Mreža Solidarnosti'))
            ->subject('Zajedno menjamo stvari – hvala za donaciju')
            ->htmlTemplate('email/thank-you-all-donor.html.twig');

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
        }
    }

    public function getDonorEmails(): array
    {
        $stmt = $this->entityManager->getConnection()->executeQuery('
            SELECT u.id, u.email
            FROM transaction AS t
             INNER JOIN user AS u ON u.id = t.user_id
            WHERE t.user_id > :lastId
             AND t.status = :status
            GROUP BY u.id
            ORDER BY u.id ASC
            ', [
            'lastId' => $this->lastId,
            'status' => Transaction::STATUS_CONFIRMED,
        ]);

        $items = [];
        while ($row = $stmt->fetchAssociative()) {
            $this->lastId = $row['id'];
            $items[] = $row['email'];
        }

        return $items;
    }
}

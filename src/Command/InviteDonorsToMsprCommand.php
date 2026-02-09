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
    name: 'app:invite-donors-to-mspr',
    description: 'Send invite to donors on msp, to register for mspr as well'
)]
class InviteDonorsToMsprCommand extends Command
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
                $donorEmail = 'djavolak@mail.ru';

                $output->writeln('Send email to '.$donorEmail);
                $this->sendEmail($donorEmail);

                die('done');
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
            ->subject('Vaša solidarna podrška je danas važnija nego ikad, jer se represija pojačava')
            ->htmlTemplate('email/invite-to-mspr.html.twig');

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
        }
    }

    public function getDonorEmails(): array
    {
        $stmt = $this->entityManager->getConnection()->executeQuery('
            SELECT u.id, u.email
            FROM user_donor ud JOIN user u ON (ud.user_id = u.id)
            WHERE u.id > :lastId
            ORDER BY u.id ASC
            ', [
            'lastId' => $this->lastId
        ]);

        $items = [];
        while ($row = $stmt->fetchAssociative()) {
            $this->lastId = $row['id'];
            $items[] = $row['email'];
        }

        return $items;
    }
}

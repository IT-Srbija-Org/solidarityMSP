<?php

namespace App\Command;

use App\Entity\UserDonor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:send-email-to-donors',
    description: 'Send email to donors',
)]
class SendEmailToDonorsCommand extends Command
{
    private int $lastId = 0;

    public function __construct(private EntityManagerInterface $entityManager, private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        while (true) {
            $items = $this->getItems();
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $output->writeln('Send email to: '.$item->getUser()->getEmail().' at '.date('Y-m-d H:i:s'));

                $message = (new TemplatedEmail())
                    ->to($item->getUser()->getEmail())
                    ->subject('Imamo važne vesti')
                    ->htmlTemplate('email/donor_new_website.html.twig')
                    ->context(['user' => $item]);

                $this->mailer->send($message);
            }

            $this->entityManager->flush();
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function getItems(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('ud')
            ->from(UserDonor::class, 'ud')
            ->andWhere('ud.id > :lastId')
            ->setParameter('lastId', $this->lastId)
            ->orderBy('ud.id', 'ASC')
            ->setMaxResults(100);

        $results = $qb->getQuery()->getResult();
        if (!empty($results)) {
            $last = end($results);
            $this->lastId = $last->getId();
        }

        return $results;
    }
}

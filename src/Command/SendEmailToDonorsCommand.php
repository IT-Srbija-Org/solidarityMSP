<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\UserDonor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected function configure(): void
    {
        $this
            ->addOption('lastId', null, InputOption::VALUE_REQUIRED)
            ->addArgument('test-email', InputArgument::OPTIONAL, 'Email address to send test email instead of real donors');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        $this->lastId = $input->getOption('lastId') ? $input->getOption('lastId') : 0;
        $testEmail = $input->getArgument('test-email');

        if ($testEmail) {
            $output->writeln('Sending TEST email to: '.$testEmail.' at '.date('Y-m-d H:i:s'));

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $testEmail]);
            if (empty($user)) {
                $output->writeln('User not found');

                return Command::FAILURE;
            }

            $message = (new TemplatedEmail())
                ->to($testEmail)
                ->subject('Imamo važne vesti')
                ->htmlTemplate('email/donor_new_website.html.twig')
                ->context(['user' => $user]);

            $this->mailer->send($message);

            return Command::SUCCESS;
        }

        while (true) {
            $items = $this->getItems();
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $output->writeln('ID: '.$item->getId().' | Send email to: '.$item->getUser()->getEmail().' at '.date('Y-m-d H:i:s'));

                $message = (new TemplatedEmail())
                    ->to($item->getUser()->getEmail())
                    ->subject('Imamo važne vesti')
                    ->htmlTemplate('email/donor_new_website.html.twig')
                    ->context(['user' => $item]);

                try {
                    $this->mailer->send($message);
                } catch (\Exception $exception) {
                    if (preg_match('/suppressed/i', $exception->getMessage())) {
                        $output->writeln('ERROR (suppressed) for '.$item->getUser()->getEmail().' | Message: '.$exception->getMessage());
                    } else {
                        $output->writeln('ERROR for '.$item->getUser()->getEmail().' | Message: '.$exception->getMessage());
                    }
                }
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
